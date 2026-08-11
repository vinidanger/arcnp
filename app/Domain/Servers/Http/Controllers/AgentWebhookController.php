<?php

namespace App\Domain\Servers\Http\Controllers;

use App\Domain\Hosting\Models\AppInstallation;
use App\Domain\Hosting\Models\Domain;
use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Models\HostingBackup;
use App\Domain\Hosting\Models\ImageOptimization;
use App\Domain\Hosting\Models\MalwareScan;
use App\Domain\Hosting\Services\FolderProtectionService;
use App\Domain\Hosting\Services\HostingAccountProvisioningService;
use App\Domain\Hosting\Services\HotlinkProtectionService;
use App\Domain\Hosting\Services\MalwareScanService;
use App\Domain\Hosting\Services\MimeTypeService;
use App\Domain\Hosting\Services\SiteRedirectService;
use App\Domain\Servers\Models\AgentCredential;
use App\Domain\Servers\Models\AgentJob;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AgentTaskFailedNotification;
use App\Notifications\MalwareFoundNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class AgentWebhookController extends Controller
{
    /**
     * Recebe o resultado de uma ação assíncrona que o Agent processou.
     * O "correlation_id" enviado é o uuid do nosso AgentJob local,
     * gravado no momento do dispatch (ver AgentHttpClient).
     */
    public function callback(Request $request)
    {
        /** @var AgentCredential $credential */
        $credential = $request->attributes->get('agent_credential');

        $data = $request->validate([
            'job_uuid' => ['required', 'string'],
            'correlation_id' => ['nullable', 'string'],
            'action' => ['required', 'string'],
            'status' => ['required', 'string'],
            'result' => ['nullable', 'array'],
            'error' => ['nullable', 'string'],
        ]);

        $job = AgentJob::where('server_id', $credential->server_id)
            ->where('uuid', $data['correlation_id'] ?? $data['job_uuid'])
            ->first();

        if (! $job) {
            return response()->json(['ok' => false, 'message' => 'Job não encontrado.'], 404);
        }

        $job->update([
            'remote_job_id' => $data['job_uuid'],
            'status' => $data['status'],
            'result' => $data['result'] ?? null,
            'error' => $data['error'] ?? null,
            'completed_at' => now(),
        ]);

        if ($job->action === 'ssl.issue_certificate') {
            $this->applySslResult($job);
        }

        if ($job->action === 'backup.create') {
            $this->applyBackupResult($job);
        }

        if ($job->action === 'app.install_wordpress') {
            $this->applyAppInstallResult($job);
        }

        if ($job->action === 'security.scan_account') {
            $this->applyMalwareScanResult($job);
        }

        if ($job->action === 'web.optimize_images') {
            $this->applyImageOptimizationResult($job);
        }

        if ($job->action === 'ssl.renew_all' && $job->status === 'completed') {
            $this->applySslRenewResult($job);
        }

        if ($job->status === 'failed' && in_array($job->action, ['ssl.renew_all', 'backup.create'], true)) {
            $this->notifyAdminsOfFailure($job);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Só pras tarefas que rodam sozinhas em background (renovação de
     * SSL, backup agendado) — ninguém está olhando a tela quando
     * falham, diferente de uma ação que o admin/cliente acabou de
     * clicar (essa já mostra erro na hora, não precisa de e-mail).
     */
    private function notifyAdminsOfFailure(AgentJob $job): void
    {
        $label = match ($job->action) {
            'ssl.renew_all' => "Renovação de SSL ({$job->server->name})",
            'backup.create' => 'Backup agendado',
            default => $job->action,
        };

        $context = match ($job->action) {
            'ssl.renew_all' => "Servidor: {$job->server->name}",
            'backup.create' => 'Conta: '.(HostingBackup::find($job->payload['backup_id'] ?? null)?->hostingAccount?->primary_domain ?? 'desconhecida'),
            default => '',
        };

        $admins = User::where('type', 'admin')->get();

        Notification::send($admins, new AgentTaskFailedNotification($label, $context, $job->error ?? 'Erro desconhecido.'));
    }

    /**
     * Não há vínculo direto agent_jobs -> hosting_accounts (agent_jobs
     * é por servidor, não por conta) — encontra pelo domínio enviado no
     * payload original do dispatch. Testa domínio adicional/subdomínio
     * primeiro (mais específico) e cai para o domínio principal da conta.
     */
    private function applySslResult(AgentJob $job): void
    {
        $domainName = $job->payload['domain'] ?? null;

        if (! $domainName) {
            return;
        }

        $target = Domain::where('domain', $domainName)->first()
            ?? HostingAccount::where('primary_domain', $domainName)->first();

        if (! $target) {
            return;
        }

        if ($job->status === 'completed') {
            $expiresAt = $job->result['expires_at'] ?? null;

            $target->update([
                'ssl_status' => 'active',
                'ssl_error' => null,
                'ssl_issued_at' => now(),
                'ssl_expires_at' => $expiresAt ? Carbon::parse($expiresAt) : null,
            ]);

            // SSL reescreve o vhost inteiro a partir do stub (ver
            // IssueSslCertificateAction) — sem isso, uma pasta com
            // senha configurada ANTES da emissão perderia a proteção
            // silenciosamente assim que o certificado ficasse pronto.
            $account = $target instanceof HostingAccount ? $target : $target->hostingAccount;
            app(FolderProtectionService::class)->resyncIfNeeded($account, $domainName);
            app(SiteRedirectService::class)->resyncIfNeeded($account, $domainName);
            app(HotlinkProtectionService::class)->resyncIfNeeded($account, $domainName);
            app(MimeTypeService::class)->resyncIfNeeded($account, $domainName);
        } elseif ($job->status === 'failed') {
            $target->update(['ssl_status' => 'failed', 'ssl_error' => $job->error]);
        }
    }

    /**
     * `ssl.renew_all` roda uma vez por dia pra TODO servidor (ver
     * comando agendado `ssl:renew`) e o Agent devolve a expiração de
     * cada certificado que existe nele — atualiza `ssl_expires_at` de
     * cada domínio/conta encontrado, mesma lógica de correlação por
     * nome de domínio que applySslResult() já usa (domínio adicional
     * primeiro, cai pro domínio principal da conta).
     */
    private function applySslRenewResult(AgentJob $job): void
    {
        foreach ($job->result['certificates'] ?? [] as $cert) {
            $domainName = $cert['domain'] ?? null;
            $expiresAt = $cert['expires_at'] ?? null;

            if (! $domainName || ! $expiresAt) {
                continue;
            }

            $target = Domain::where('domain', $domainName)->first()
                ?? HostingAccount::where('primary_domain', $domainName)->first();

            $target?->update(['ssl_expires_at' => Carbon::parse($expiresAt)]);
        }
    }

    /**
     * Ao contrário do SSL (correlaciona pelo domínio), aqui usa o id do
     * HostingBackup embutido no payload original do dispatch — mais
     * direto, e um domínio nem sempre identifica a conta sem ambiguidade
     * quando o que se quer é achar UM backup específico entre vários.
     */
    private function applyBackupResult(AgentJob $job): void
    {
        $backupId = $job->payload['backup_id'] ?? null;

        if (! $backupId) {
            return;
        }

        $backup = HostingBackup::find($backupId);

        if (! $backup) {
            return;
        }

        if ($job->status === 'completed') {
            $backup->update(['status' => 'completed', 'files' => $job->result['files'] ?? []]);
        } elseif ($job->status === 'failed') {
            $backup->update(['status' => 'failed', 'error' => $job->error]);
        }
    }

    /**
     * Usa o id do AppInstallation embutido no payload original do
     * dispatch (mesmo padrão de applyBackupResult()/backup_id). Em
     * caso de falha, além de marcar o erro, apaga o banco de dados
     * órfão recém-criado — um WordPress meio-instalado com um banco
     * pra trás só vira lixo confuso pro cliente encontrar depois.
     */
    private function applyAppInstallResult(AgentJob $job): void
    {
        $installationId = $job->payload['app_installation_id'] ?? null;

        if (! $installationId) {
            return;
        }

        $installation = AppInstallation::find($installationId);

        if (! $installation) {
            return;
        }

        if ($job->status === 'completed') {
            $installation->update([
                'status' => 'active',
                'admin_username' => $job->payload['admin_user'] ?? null,
                'installed_at' => now(),
            ]);
        } elseif ($job->status === 'failed') {
            $installation->update(['status' => 'failed', 'error' => $job->error]);

            if ($installation->database_id) {
                app(HostingAccountProvisioningService::class)->deleteDatabase($installation->database);
                $installation->update(['database_id' => null]);
            }
        }
    }

    /**
     * Usa o id do MalwareScan embutido no payload original do dispatch
     * (mesmo padrão de applyBackupResult()/backup_id). Se encontrou
     * algo, notifica admins + dono da conta — diferente de
     * notifyAdminsOfFailure() (só admin, só falha de execução), aqui é
     * sempre relevante pro cliente saber também.
     */
    private function applyMalwareScanResult(AgentJob $job): void
    {
        $scanId = $job->payload['scan_id'] ?? null;

        if (! $scanId) {
            return;
        }

        $scan = MalwareScan::find($scanId);

        if (! $scan) {
            return;
        }

        if ($job->status === 'completed') {
            $infected = $job->result['infected_files'] ?? [];

            // Cruza contra o que já foi marcado como falso positivo
            // pra essa conta (MalwareScanService::ignore()) — sem isso,
            // um arquivo já revisado voltaria a ser quarentenado e
            // notificado em toda varredura seguinte, já que o ClamAV
            // continua detectando a mesma assinatura no mesmo lugar.
            $ignoredPairs = $scan->hostingAccount->malwareIgnoredFiles()
                ->get(['path', 'signature'])
                ->map(fn ($f) => $f->path.'|'.$f->signature)
                ->all();

            foreach ($infected as $i => $file) {
                if (in_array(($file['path'] ?? '').'|'.($file['signature'] ?? ''), $ignoredPairs, true)) {
                    $infected[$i]['ignored'] = true;
                }
            }

            $scan->update(['status' => 'completed', 'infected_files' => $infected, 'completed_at' => now()]);

            if ($scan->hasActionableInfectedFiles()) {
                $malwareScanService = app(MalwareScanService::class);

                // Quarentena automática (mesmo comportamento padrão do
                // Imunify360 real, que roda em cPanel/DirectAdmin) — como
                // quarentena aqui é sempre reversível (move, não apaga),
                // o risco de um falso positivo do ClamAV é bem menor do
                // que deixar malware de verdade no ar até alguém revisar
                // manualmente. Uma falha em UM arquivo não impede a
                // quarentena dos outros nem o envio da notificação. Entradas
                // já marcadas como falso positivo (acima) ficam de fora.
                foreach ($scan->infected_files ?? [] as $file) {
                    if (! empty($file['ignored'])) {
                        continue;
                    }

                    try {
                        $malwareScanService->quarantine($scan, $file['path']);
                    } catch (\Throwable $e) {
                        report($e);
                    }
                }

                $account = $scan->hostingAccount;
                $recipients = User::where('type', 'admin')->get();

                if ($account->client) {
                    $recipients->push($account->client);
                }

                Notification::send($recipients, new MalwareFoundNotification($scan));
            }
        } elseif ($job->status === 'failed') {
            $scan->update(['status' => 'failed', 'error' => $job->error, 'completed_at' => now()]);
        }
    }

    /**
     * Usa o id do ImageOptimization embutido no payload original do
     * dispatch (mesmo padrão de applyMalwareScanResult()/scan_id).
     */
    private function applyImageOptimizationResult(AgentJob $job): void
    {
        $optimizationId = $job->payload['image_optimization_id'] ?? null;

        if (! $optimizationId) {
            return;
        }

        $optimization = ImageOptimization::find($optimizationId);

        if (! $optimization) {
            return;
        }

        if ($job->status === 'completed') {
            $optimization->update([
                'status' => 'completed',
                'processed_count' => $job->result['processed'] ?? 0,
                'converted_count' => $job->result['converted'] ?? 0,
                'skipped_count' => $job->result['skipped'] ?? 0,
                'completed_at' => now(),
            ]);
        } elseif ($job->status === 'failed') {
            $optimization->update(['status' => 'failed', 'error' => $job->error, 'completed_at' => now()]);
        }
    }

    /**
     * Snapshot periódico de status enviado pelo Agent (systemd timer).
     * Marca o servidor online e atualiza o instantâneo de métricas.
     */
    public function heartbeat(Request $request)
    {
        /** @var AgentCredential $credential */
        $credential = $request->attributes->get('agent_credential');

        $data = $request->validate([
            'load_avg' => ['nullable', 'numeric'],
            'disk_percent' => ['nullable', 'numeric'],
            'mem_percent' => ['nullable', 'numeric'],
        ]);

        $credential->server->update([
            'agent_status' => 'online',
            'last_heartbeat_at' => now(),
            'load_avg' => $data['load_avg'] ?? null,
            'disk_percent' => $data['disk_percent'] ?? null,
            'mem_percent' => $data['mem_percent'] ?? null,
        ]);

        // Histórico pros gráficos (item F) — o campo na própria Server
        // continua sendo só o snapshot mais recente (usado em todo
        // lugar que só precisa do "agora"); essa tabela à parte é só
        // pra série temporal, podada por server-metrics:prune.
        $credential->server->metricSnapshots()->create([
            'load_avg' => $data['load_avg'] ?? null,
            'disk_percent' => $data['disk_percent'] ?? null,
            'mem_percent' => $data['mem_percent'] ?? null,
            'recorded_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }
}

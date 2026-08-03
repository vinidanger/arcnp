<?php

namespace App\Domain\Servers\Http\Controllers;

use App\Domain\Hosting\Models\Domain;
use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Models\HostingBackup;
use App\Domain\Servers\Models\AgentCredential;
use App\Domain\Servers\Models\AgentJob;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\AgentTaskFailedNotification;
use Illuminate\Http\Request;
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
            $target->update(['ssl_status' => 'active', 'ssl_error' => null, 'ssl_issued_at' => now()]);
        } elseif ($job->status === 'failed') {
            $target->update(['ssl_status' => 'failed', 'ssl_error' => $job->error]);
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

        return response()->json(['ok' => true]);
    }
}

<?php

namespace App\Domain\Hosting\Http\Controllers\Admin;

use App\Domain\Hosting\Models\Plan;
use App\Domain\Hosting\Services\HostingAccountProvisioningService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Throwable;

class PlanController extends Controller
{
    public function index()
    {
        $plans = Plan::orderBy('name')->get();

        return view('admin.plans.index', compact('plans'));
    }

    public function create()
    {
        return view('admin.plans.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        Plan::create($data);

        return redirect()->route('admin.plans.index')->with('status', 'Plano criado.');
    }

    public function edit(Plan $plan)
    {
        return view('admin.plans.edit', compact('plan'));
    }

    public function update(Request $request, Plan $plan, HostingAccountProvisioningService $provisioning)
    {
        $data = $this->validated($request);

        $plan->update($data);

        // Sem isso, o admin precisaria abrir "Recursos" de cada conta do
        // plano e clicar "Reaplicar limites" uma por uma — inviável com
        // mais de umas poucas contas. Síncrono (mesmo padrão de
        // RefreshDiskUsageCommand: loop com try/catch por conta, uma
        // falha isolada não trava as demais nem impede o plano de salvar)
        // porque este projeto não roda worker de fila nenhum — só
        // aceitável aqui porque essa ação já é rara (editar plano, não
        // toda hora) e cada chamada ao Agent costuma ser rápida.
        $accounts = $plan->hostingAccounts()->where('status', 'active')->get();
        $failed = 0;

        foreach ($accounts as $account) {
            try {
                $provisioning->syncResourceLimits($account);
            } catch (Throwable $e) {
                $failed++;
                logger()->warning('Falha ao reaplicar limites após edição de plano', [
                    'plan_id' => $plan->id,
                    'hosting_account_id' => $account->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $status = "Plano atualizado. Limites reaplicados em {$accounts->count()} conta(s).";

        if ($failed > 0) {
            $status .= " {$failed} falharam (veja o log) — pode reaplicar manualmente pela tela \"Recursos\" de cada uma.";
        }

        return redirect()->route('admin.plans.index')->with('status', $status);
    }

    public function destroy(Plan $plan)
    {
        if ($plan->hostingAccounts()->exists()) {
            return back()->with('error', 'Existem contas de hospedagem usando este plano.');
        }

        $plan->delete();

        return redirect()->route('admin.plans.index')->with('status', 'Plano removido.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'disk_quota_mb' => ['required', 'integer', 'min:1'],
            'bandwidth_quota_mb' => ['nullable', 'integer', 'min:1'],
            'max_databases' => ['required', 'integer', 'min:0'],
            'max_addon_domains' => ['required', 'integer', 'min:0'],
            'max_cron_jobs' => ['required', 'integer', 'min:0'],
            'max_email_accounts' => ['required', 'integer', 'min:0'],
            'max_backups' => ['required', 'integer', 'min:0'],
            'cpu_cores' => ['nullable', 'integer', 'min:1'],
            'max_processes' => ['nullable', 'integer', 'min:1'],
            'memory_limit_mb' => ['nullable', 'integer', 'min:64'],
            'io_weight' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'max_db_connections' => ['nullable', 'integer', 'min:1'],
            'max_db_queries_per_hour' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ]);
    }
}

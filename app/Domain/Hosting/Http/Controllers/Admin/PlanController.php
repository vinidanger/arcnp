<?php

namespace App\Domain\Hosting\Http\Controllers\Admin;

use App\Domain\Hosting\Models\Plan;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

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

    public function update(Request $request, Plan $plan)
    {
        $data = $this->validated($request);

        $plan->update($data);

        return redirect()->route('admin.plans.index')->with('status', 'Plano atualizado.');
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
            'is_active' => ['boolean'],
        ]);
    }
}

<?php

namespace App\Domain\Hosting\Http\Controllers\Client;

use App\Domain\Hosting\Models\CronJob;
use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Services\CronJobService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Throwable;

class CronJobController extends Controller
{
    public function index(HostingAccount $hosting_account)
    {
        $this->authorize('view', $hosting_account);

        $hosting_account->load('cronJobs');

        return view('client.hosting-accounts.cron.index', ['account' => $hosting_account]);
    }

    public function store(Request $request, HostingAccount $hosting_account, CronJobService $cron)
    {
        $this->authorize('update', $hosting_account);

        $data = $this->validated($request);

        try {
            $cron->create($hosting_account, $data);

            return back()->with('status', 'Tarefa criada.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao criar tarefa: '.$e->getMessage());
        }
    }

    public function destroy(HostingAccount $hosting_account, CronJob $cron_job, CronJobService $cron)
    {
        $this->authorize('update', $hosting_account);

        abort_unless($cron_job->hosting_account_id === $hosting_account->id, 404);

        try {
            $cron->delete($cron_job);

            return back()->with('status', 'Tarefa removida.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao remover tarefa: '.$e->getMessage());
        }
    }

    private function validated(Request $request): array
    {
        $fieldRule = ['required', 'string', 'regex:/^[0-9*,\-\/]+$/'];

        return $request->validate([
            'minute' => $fieldRule,
            'hour' => $fieldRule,
            'day' => $fieldRule,
            'month' => $fieldRule,
            'weekday' => $fieldRule,
            'command' => ['required', 'string', 'max:1000', 'regex:/^[^\r\n]+$/'],
        ]);
    }
}

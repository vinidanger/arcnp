<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Hosting\Models\Domain;
use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Servers\Models\Server;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $stats = [
            'clients' => User::where('type', 'client')->count(),
            'accounts_total' => HostingAccount::count(),
            'accounts_active' => HostingAccount::where('status', 'active')->count(),
            'accounts_suspended' => HostingAccount::where('status', 'suspended')->count(),
            'accounts_error' => HostingAccount::where('status', 'error')->count(),
            'servers_total' => Server::count(),
            'servers_online' => Server::where('agent_status', 'online')->count(),
            'disk_usage_mb' => (int) HostingAccount::sum('disk_usage_mb'),
        ];

        $recentAccounts = HostingAccount::with(['client', 'plan'])->latest()->take(6)->get();

        $attentionAccounts = HostingAccount::with(['client', 'plan'])
            ->needsAttention()
            ->latest()
            ->take(6)
            ->get();

        $auditLogs = AuditLog::with('user')->latest('id')->take(5)->get();

        $downTargets = $this->downTargets();

        return view('admin.dashboard', compact('stats', 'recentAccounts', 'attentionAccounts', 'auditLogs', 'downTargets'));
    }

    /**
     * "down" pode estar em HostingAccount (domínio principal) OU Domain
     * (adicional/subdomínio) — junta os dois numa lista só, mais recente
     * primeiro, pra caber num card compacto de "de relance".
     */
    private function downTargets(): Collection
    {
        $accounts = HostingAccount::where('uptime_status', 'down')
            ->with('client')
            ->get()
            ->map(fn (HostingAccount $account) => [
                'label' => $account->primary_domain,
                'account' => $account,
                'down_since' => $account->uptime_down_since,
            ]);

        $domains = Domain::where('uptime_status', 'down')
            ->with('hostingAccount.client')
            ->get()
            ->map(fn (Domain $domain) => [
                'label' => $domain->domain,
                'account' => $domain->hostingAccount,
                'down_since' => $domain->uptime_down_since,
            ]);

        return $accounts->concat($domains)->sortByDesc('down_since')->take(8)->values();
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Servers\Models\Server;
use App\Http\Controllers\Controller;
use App\Models\User;

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
            ->whereIn('status', ['suspended', 'error'])
            ->orWhere('ssl_status', 'failed')
            ->latest()
            ->take(6)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentAccounts', 'attentionAccounts'));
    }
}

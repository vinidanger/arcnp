<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $accounts = Auth::user()->hostingAccounts()->with('plan')->latest()->get();

        $stats = [
            'accounts_total' => $accounts->count(),
            'accounts_active' => $accounts->where('status', 'active')->count(),
            'disk_usage_mb' => (int) $accounts->sum('disk_usage_mb'),
            'disk_quota_mb' => (int) $accounts->sum(fn ($a) => $a->plan->disk_quota_mb ?? 0),
        ];

        $attentionAccounts = Auth::user()->hostingAccounts()
            ->with('plan')
            ->needsAttention()
            ->latest()
            ->take(6)
            ->get();

        return view('client.dashboard', [
            'stats' => $stats,
            'accounts' => $accounts->take(5),
            'attentionAccounts' => $attentionAccounts,
        ]);
    }
}

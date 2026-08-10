<?php

namespace App\Domain\Hosting\Http\Controllers\Admin;

use App\Domain\Hosting\Models\DomainTrafficStat;
use App\Domain\Hosting\Models\HostingAccount;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TrafficStatsController extends Controller
{
    public function index(Request $request, HostingAccount $hosting_account)
    {
        $this->authorize('view', $hosting_account);

        $hosting_account->load('domains');

        $domains = array_values(array_unique(array_merge(
            [$hosting_account->primary_domain],
            $hosting_account->domains->pluck('domain')->all()
        )));

        $data = $request->validate([
            'domain' => ['nullable', 'string', 'in:'.implode(',', $domains)],
        ]);

        $domain = $data['domain'] ?? $hosting_account->primary_domain;

        $stats = DomainTrafficStat::where('hosting_account_id', $hosting_account->id)
            ->where('domain', $domain)
            ->where('date', '>=', now()->subDays(30))
            ->orderBy('date')
            ->get();

        return view('admin.hosting-accounts.traffic.index', [
            'account' => $hosting_account,
            'domains' => $domains,
            'domain' => $domain,
            'stats' => $stats,
            'latest' => $stats->last(),
        ]);
    }
}

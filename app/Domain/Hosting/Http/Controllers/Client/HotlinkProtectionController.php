<?php

namespace App\Domain\Hosting\Http\Controllers\Client;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Services\HotlinkProtectionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Throwable;

class HotlinkProtectionController extends Controller
{
    public function index(HostingAccount $hosting_account)
    {
        $this->authorize('view', $hosting_account);

        $hosting_account->load('hotlinkProtections', 'domains');

        $domains = array_values(array_unique(array_merge(
            [$hosting_account->primary_domain],
            $hosting_account->domains->pluck('domain')->all()
        )));

        $protections = $hosting_account->hotlinkProtections->keyBy('domain');

        return view('client.hosting-accounts.hotlink-protection.index', [
            'account' => $hosting_account,
            'domains' => $domains,
            'protections' => $protections,
        ]);
    }

    public function update(Request $request, HostingAccount $hosting_account, HotlinkProtectionService $service)
    {
        $this->authorize('update', $hosting_account);

        $domains = array_merge([$hosting_account->primary_domain], $hosting_account->domains()->pluck('domain')->all());

        $data = $request->validate([
            'domain' => ['required', 'string', 'in:'.implode(',', $domains)],
            'enabled' => ['nullable', 'boolean'],
            'extensions' => ['required_if:enabled,1', 'nullable', 'string', 'max:500'],
            'allowed_referrers' => ['nullable', 'string', 'max:1000'],
        ]);

        $extensions = $this->splitList($data['extensions'] ?? '');
        $referrers = $this->splitList($data['allowed_referrers'] ?? '');

        try {
            $service->update($hosting_account, $data['domain'], (bool) ($data['enabled'] ?? false), $extensions, $referrers);

            return back()->with('status', 'Proteção hotlink atualizada.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao atualizar proteção hotlink: '.$e->getMessage());
        }
    }

    /** @return list<string> */
    private function splitList(string $value): array
    {
        return collect(explode(',', $value))
            ->map(fn ($v) => trim($v))
            ->filter()
            ->values()
            ->all();
    }
}

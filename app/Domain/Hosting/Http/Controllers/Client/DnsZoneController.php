<?php

namespace App\Domain\Hosting\Http\Controllers\Client;

use App\Domain\Hosting\Models\DnsRecord;
use App\Domain\Hosting\Models\DnsZone;
use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Services\DnsZoneService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Throwable;

class DnsZoneController extends Controller
{
    public function index(HostingAccount $hosting_account, DnsZoneService $dns)
    {
        $this->authorize('view', $hosting_account);

        $hosting_account->load('dnsZones', 'server');

        return view('client.hosting-accounts.dns.index', [
            'account' => $hosting_account,
            'availableDomains' => $dns->availableDomains($hosting_account),
        ]);
    }

    public function store(Request $request, HostingAccount $hosting_account, DnsZoneService $dns)
    {
        $this->authorize('update', $hosting_account);

        $data = $request->validate([
            'domain' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255'],
        ]);

        try {
            $dns->createZone($hosting_account, $data['domain'], $data['admin_email']);

            return back()->with('status', 'Zona DNS criada.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao criar zona DNS: '.$e->getMessage());
        }
    }

    public function show(HostingAccount $hosting_account, DnsZone $dns_zone)
    {
        $this->authorize('view', $hosting_account);

        abort_unless($dns_zone->hosting_account_id === $hosting_account->id, 404);

        $dns_zone->load('records');

        return view('client.hosting-accounts.dns.show', [
            'account' => $hosting_account,
            'zone' => $dns_zone,
        ]);
    }

    public function destroy(HostingAccount $hosting_account, DnsZone $dns_zone, DnsZoneService $dns)
    {
        $this->authorize('update', $hosting_account);

        abort_unless($dns_zone->hosting_account_id === $hosting_account->id, 404);

        try {
            $dns->deleteZone($dns_zone);

            return redirect()
                ->route('client.hosting-accounts.dns.index', $hosting_account)
                ->with('status', 'Zona DNS removida.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao remover zona DNS: '.$e->getMessage());
        }
    }

    public function storeRecord(Request $request, HostingAccount $hosting_account, DnsZone $dns_zone, DnsZoneService $dns)
    {
        $this->authorize('update', $hosting_account);

        abort_unless($dns_zone->hosting_account_id === $hosting_account->id, 404);

        $data = $this->validatedRecord($request);

        try {
            $dns->addRecord($dns_zone, $data);

            return back()->with('status', 'Registro adicionado.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao adicionar registro: '.$e->getMessage());
        }
    }

    public function updateRecord(Request $request, HostingAccount $hosting_account, DnsZone $dns_zone, DnsRecord $dns_record, DnsZoneService $dns)
    {
        $this->authorize('update', $hosting_account);

        abort_unless($dns_zone->hosting_account_id === $hosting_account->id, 404);
        abort_unless($dns_record->dns_zone_id === $dns_zone->id, 404);

        $data = $this->validatedRecord($request);

        try {
            $dns->updateRecord($dns_record, $data);

            return back()->with('status', 'Registro atualizado.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao atualizar registro: '.$e->getMessage());
        }
    }

    public function destroyRecord(HostingAccount $hosting_account, DnsZone $dns_zone, DnsRecord $dns_record, DnsZoneService $dns)
    {
        $this->authorize('update', $hosting_account);

        abort_unless($dns_zone->hosting_account_id === $hosting_account->id, 404);
        abort_unless($dns_record->dns_zone_id === $dns_zone->id, 404);

        try {
            $dns->deleteRecord($dns_record);

            return back()->with('status', 'Registro removido.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao remover registro: '.$e->getMessage());
        }
    }

    private function validatedRecord(Request $request): array
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'in:A,AAAA,CNAME,MX,TXT,NS'],
            'name' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:4000'],
            'ttl' => ['required', 'integer', 'min:60', 'max:604800'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:65535', 'required_if:type,MX'],
        ]);

        $data['priority'] = $data['type'] === 'MX' ? $data['priority'] : null;

        return $data;
    }
}

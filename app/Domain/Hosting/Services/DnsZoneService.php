<?php

namespace App\Domain\Hosting\Services;

use App\Domain\Hosting\Models\DnsRecord;
use App\Domain\Hosting\Models\DnsZone;
use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Servers\Models\Server;
use App\Domain\Servers\Services\AgentHttpClient;
use RuntimeException;

/**
 * O Painel é sempre a fonte da verdade. zones.conf é por SERVIDOR (todo
 * domínio com zona ativa nele, não só os da conta), então criar/apagar
 * zona sempre reenvia a lista completa de domínios do servidor — mesmo
 * padrão do cron.sync/ssh.sync_keys, só que no nível do servidor em vez
 * da conta. Editar registros de uma zona já existente só mexe nela.
 */
class DnsZoneService
{
    public function __construct(private AgentHttpClient $client)
    {
    }

    /**
     * Domínios da conta que ainda não têm zona — candidatos pra criar uma.
     *
     * @return list<string>
     */
    public function availableDomains(HostingAccount $account): array
    {
        $candidates = array_unique(array_merge(
            [$account->primary_domain],
            $account->domains()->pluck('domain')->all()
        ));

        $taken = DnsZone::whereIn('domain', $candidates)->pluck('domain')->all();

        return array_values(array_diff($candidates, $taken));
    }

    public function createZone(HostingAccount $account, string $domain, string $adminEmail): DnsZone
    {
        if ($domain !== $account->primary_domain && ! $account->domains()->where('domain', $domain)->exists()) {
            throw new RuntimeException('Esse domínio não pertence a essa conta.');
        }

        $server = $account->server;
        $ns = $server->nsHosts();

        if ($ns === []) {
            throw new RuntimeException('Configure ao menos um nameserver (NS) no servidor antes de criar uma zona DNS.');
        }

        $zone = $account->dnsZones()->create(['domain' => $domain, 'admin_email' => $adminEmail]);

        $ip = $server->public_ip_address ?: $server->ip_address;

        $zone->records()->createMany([
            ['type' => 'A', 'name' => '@', 'content' => $ip, 'ttl' => 3600],
            ['type' => 'A', 'name' => 'www', 'content' => $ip, 'ttl' => 3600],
        ]);

        try {
            $zonesConfDomains = $this->serverZoneDomains($server, exceptZoneId: null);

            $job = $this->client->dispatch($server, 'dns.create_zone', [
                'domain' => $domain,
                'zones' => $zonesConfDomains,
                'ns' => $ns,
                'admin_email' => $adminEmail,
                'records' => $this->recordsPayload($zone),
            ]);

            if ($job->status !== 'completed') {
                throw new RuntimeException($job->error ?? 'Falha ao criar zona DNS.');
            }
        } catch (RuntimeException $e) {
            $zone->delete();
            throw $e;
        }

        return $zone;
    }

    public function addRecord(DnsZone $zone, array $data): DnsRecord
    {
        $record = $zone->records()->create($data);

        try {
            $this->syncRecords($zone);
        } catch (RuntimeException $e) {
            $record->delete();
            throw $e;
        }

        return $record;
    }

    public function updateRecord(DnsRecord $record, array $data): void
    {
        $zone = $record->zone;
        $original = $record->only(['type', 'name', 'content', 'ttl', 'priority']);

        $record->update($data);

        try {
            $this->syncRecords($zone);
        } catch (RuntimeException $e) {
            $record->update($original);
            throw $e;
        }
    }

    public function deleteRecord(DnsRecord $record): void
    {
        $zone = $record->zone;
        $record->delete();

        $this->syncRecords($zone);
    }

    public function deleteZone(DnsZone $zone): void
    {
        $server = $zone->hostingAccount->server;
        $remaining = $this->serverZoneDomains($server, exceptZoneId: $zone->id);

        $job = $this->client->dispatch($server, 'dns.delete_zone', [
            'domain' => $zone->domain,
            'zones' => $remaining,
        ]);

        if ($job->status !== 'completed') {
            throw new RuntimeException($job->error ?? 'Falha ao apagar zona DNS.');
        }

        $zone->delete();
    }

    private function syncRecords(DnsZone $zone): void
    {
        $server = $zone->hostingAccount->server;

        $job = $this->client->dispatch($server, 'dns.update_zone_records', [
            'domain' => $zone->domain,
            'ns' => $server->nsHosts(),
            'admin_email' => $zone->admin_email,
            'records' => $this->recordsPayload($zone),
        ]);

        if ($job->status !== 'completed') {
            throw new RuntimeException($job->error ?? 'Falha ao sincronizar registros DNS.');
        }
    }

    /** @return list<array<string, mixed>> */
    private function recordsPayload(DnsZone $zone): array
    {
        return $zone->records()->get(['type', 'name', 'content', 'ttl', 'priority'])->toArray();
    }

    /** @return list<string> */
    private function serverZoneDomains(Server $server, ?int $exceptZoneId): array
    {
        $query = DnsZone::whereHas(
            'hostingAccount',
            fn ($q) => $q->where('server_id', $server->id)
        );

        if ($exceptZoneId !== null) {
            $query->whereKeyNot($exceptZoneId);
        }

        return $query->pluck('domain')->all();
    }
}

<?php

namespace App\Domain\Hosting\Services;

use App\Domain\Hosting\Models\MailDomain;
use App\Domain\Servers\Models\Server;

/**
 * Só consulta DNS público de verdade — nunca lê nossa própria tabela
 * `dns_records` pra "confirmar" SPF/DKIM/DMARC, porque o que importa é
 * o que está PUBLICADO de verdade (pode ter sido editado fora daqui,
 * ou ainda não ter propagado), não o que achamos que configuramos.
 */
class EmailHealthService
{
    public function checkMailDomain(MailDomain $mailDomain): void
    {
        $mailDomain->update([
            'spf_valid' => $this->hasTxtStartingWith($mailDomain->domain, 'v=spf1'),
            'dkim_valid' => $this->hasTxtStartingWith($mailDomain->dkimSelector().'._domainkey.'.$mailDomain->domain, 'v=DKIM1'),
            'dmarc_valid' => $this->hasTxtStartingWith('_dmarc.'.$mailDomain->domain, 'v=DMARC1'),
            'health_checked_at' => now(),
        ]);
    }

    public function checkServer(Server $server): void
    {
        $ip = $server->public_ip_address ?: $server->ip_address;

        $server->update([
            'ptr_matches_mail_hostname' => $this->ptrMatches($ip, $server->mail_hostname),
            'ip_blacklisted' => $this->isBlacklisted($ip),
            'mail_health_checked_at' => now(),
        ]);
    }

    private function hasTxtStartingWith(string $hostname, string $prefix): bool
    {
        $records = @dns_get_record($hostname, DNS_TXT);

        if ($records === false) {
            return false;
        }

        foreach ($records as $record) {
            if (str_starts_with($record['txt'] ?? '', $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function ptrMatches(?string $ip, ?string $mailHostname): bool
    {
        if (! $ip || ! $mailHostname) {
            return false;
        }

        $ptr = @gethostbyaddr($ip);

        if ($ptr === false || $ptr === $ip) {
            return false;
        }

        return rtrim(strtolower($ptr), '.') === rtrim(strtolower($mailHostname), '.');
    }

    /**
     * Spamhaus ZEN via DNSBL padrão (IP invertido + zona) — se resolver
     * QUALQUER registro A, o IP está listado. Sem API paga, é só DNS.
     */
    private function isBlacklisted(?string $ip): bool
    {
        if (! $ip || ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        $reversed = implode('.', array_reverse(explode('.', $ip)));

        return @checkdnsrr("{$reversed}.zen.spamhaus.org", 'A');
    }
}

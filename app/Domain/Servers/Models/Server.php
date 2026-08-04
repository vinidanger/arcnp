<?php

namespace App\Domain\Servers\Models;

use App\Domain\Hosting\Models\DnsZone;
use App\Domain\Hosting\Models\FtpAccount;
use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Models\MailDomain;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Server extends Model
{
    protected $fillable = [
        'name',
        'hostname',
        'ip_address',
        'public_ip_address',
        'dns_ns1',
        'dns_ns2',
        'mail_hostname',
        'ftp_hostname',
        'agent_port',
        'use_tls',
        'os',
        'cpu_cores',
        'memory_mb',
        'disk_gb',
        'agent_status',
        'last_heartbeat_at',
        'load_avg',
        'disk_percent',
        'mem_percent',
    ];

    protected function casts(): array
    {
        return [
            'last_heartbeat_at' => 'datetime',
        ];
    }

    public function agentCredentials(): HasMany
    {
        return $this->hasMany(AgentCredential::class);
    }

    public function currentCredential(): HasOne
    {
        return $this->hasOne(AgentCredential::class)->whereNull('revoked_at')->latestOfMany();
    }

    public function agentJobs(): HasMany
    {
        return $this->hasMany(AgentJob::class);
    }

    public function hostingAccounts(): HasMany
    {
        return $this->hasMany(HostingAccount::class);
    }

    public function dnsZones(): HasManyThrough
    {
        return $this->hasManyThrough(DnsZone::class, HostingAccount::class);
    }

    public function mailDomains(): HasManyThrough
    {
        return $this->hasManyThrough(MailDomain::class, HostingAccount::class);
    }

    public function ftpAccounts(): HasMany
    {
        return $this->hasMany(FtpAccount::class);
    }

    /** @return list<string> */
    public function nsHosts(): array
    {
        return array_values(array_filter([$this->dns_ns1, $this->dns_ns2]));
    }

    public function agentBaseUrl(): string
    {
        $scheme = $this->use_tls ? 'https' : 'http';

        return "{$scheme}://{$this->ip_address}:{$this->agent_port}";
    }

    public function phpMyAdminBaseUrl(): string
    {
        $host = $this->public_ip_address ?: $this->ip_address;

        return "https://{$host}:".config('hosting.phpmyadmin_port');
    }

    public function webmailBaseUrl(): string
    {
        $host = $this->public_ip_address ?: $this->ip_address;

        return "https://{$host}:".config('hosting.webmail_port');
    }
}

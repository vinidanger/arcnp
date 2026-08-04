<?php

namespace App\Domain\Hosting\Models;

use App\Domain\Servers\Models\Server;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class HostingAccount extends Model
{
    protected $fillable = [
        'user_id',
        'server_id',
        'plan_id',
        'linux_username',
        'primary_domain',
        'php_version',
        'php_fpm_settings',
        'status',
        'last_provision_error',
        'ssl_status',
        'ssl_error',
        'ssl_issued_at',
        'ssl_expires_at',
        'backup_frequency',
        'last_backup_at',
        'disk_usage_mb',
        'disk_usage_checked_at',
        'disk_alert_sent_at',
        'ssh_enabled',
        'ssh_password',
    ];

    protected function casts(): array
    {
        return [
            'php_fpm_settings' => 'array',
            'ssl_issued_at' => 'datetime',
            'ssl_expires_at' => 'datetime',
            'last_backup_at' => 'datetime',
            'disk_usage_checked_at' => 'datetime',
            'disk_alert_sent_at' => 'datetime',
            'ssh_enabled' => 'boolean',
            'ssh_password' => 'encrypted',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function databases(): HasMany
    {
        return $this->hasMany(HostingDatabase::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function backups(): HasMany
    {
        return $this->hasMany(HostingBackup::class)->latest();
    }

    public function cronJobs(): HasMany
    {
        return $this->hasMany(CronJob::class);
    }

    public function sshKeys(): HasMany
    {
        return $this->hasMany(SshKey::class);
    }

    public function dnsZones(): HasMany
    {
        return $this->hasMany(DnsZone::class);
    }

    public function mailDomains(): HasMany
    {
        return $this->hasMany(MailDomain::class);
    }

    public function mailboxes(): HasManyThrough
    {
        return $this->hasManyThrough(Mailbox::class, MailDomain::class);
    }

    public function folderProtections(): HasMany
    {
        return $this->hasMany(FolderProtection::class);
    }

    public function siteRedirects(): HasMany
    {
        return $this->hasMany(SiteRedirect::class);
    }

    public function mimeTypeRules(): HasMany
    {
        return $this->hasMany(MimeTypeRule::class);
    }

    public function hotlinkProtections(): HasMany
    {
        return $this->hasMany(HotlinkProtection::class);
    }

    public function ftpAccounts(): HasMany
    {
        return $this->hasMany(FtpAccount::class);
    }

    public function hostedApps(): HasMany
    {
        return $this->hasMany(HostedApp::class);
    }

    public function appInstallations(): HasMany
    {
        return $this->hasMany(AppInstallation::class);
    }

    /**
     * Agrupado num where(function...) de propósito: sem isso, encadear
     * depois de uma relação já filtrada (ex.: hostingAccounts() do
     * cliente, que carrega um "where user_id = ?" implícito) vazaria
     * contas de OUTROS donos com ssl_status=failed pra fora do filtro,
     * por causa da precedência de AND/OR do SQL.
     */
    public function scopeNeedsAttention($query)
    {
        return $query->where(function ($q) {
            $q->whereIn('status', ['suspended', 'error'])
                ->orWhere('ssl_status', 'failed');
        });
    }
}

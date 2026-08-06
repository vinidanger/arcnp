<?php

namespace App\Domain\Hosting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Domain extends Model
{
    protected $fillable = [
        'hosting_account_id',
        'domain',
        'type',
        'location',
        'subdirectory',
        'public_path',
        'php_version',
        'php_fpm_settings',
        'status',
        'last_error',
        'ssl_status',
        'ssl_error',
        'ssl_issued_at',
        'ssl_expires_at',
        'waf_enabled',
        'uptime_status',
        'uptime_checked_at',
        'uptime_consecutive_failures',
        'uptime_down_since',
        'uptime_alert_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'php_fpm_settings' => 'array',
            'ssl_issued_at' => 'datetime',
            'ssl_expires_at' => 'datetime',
            'waf_enabled' => 'boolean',
            'uptime_checked_at' => 'datetime',
            'uptime_down_since' => 'datetime',
            'uptime_alert_sent_at' => 'datetime',
        ];
    }

    public function hostingAccount(): BelongsTo
    {
        return $this->belongsTo(HostingAccount::class);
    }

    public function isOutsidePublicHtml(): bool
    {
        return $this->location === 'outside_public_html';
    }
}

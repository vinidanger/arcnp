<?php

namespace App\Domain\Hosting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'disk_quota_mb',
        'bandwidth_quota_mb',
        'max_databases',
        'max_addon_domains',
        'max_cron_jobs',
        'max_email_accounts',
        'max_backups',
        'cpu_cores',
        'max_processes',
        'memory_limit_mb',
        'io_weight',
        'max_db_connections',
        'max_db_queries_per_hour',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function hostingAccounts(): HasMany
    {
        return $this->hasMany(HostingAccount::class);
    }
}

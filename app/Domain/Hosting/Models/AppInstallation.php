<?php

namespace App\Domain\Hosting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppInstallation extends Model
{
    protected $fillable = [
        'hosting_account_id',
        'domain',
        'path',
        'catalog_slug',
        'status',
        'database_id',
        'admin_username',
        'error',
        'installed_at',
    ];

    protected function casts(): array
    {
        return [
            'installed_at' => 'datetime',
        ];
    }

    public function hostingAccount(): BelongsTo
    {
        return $this->belongsTo(HostingAccount::class);
    }

    public function database(): BelongsTo
    {
        return $this->belongsTo(HostingDatabase::class, 'database_id');
    }

    public function catalogEntry(): array
    {
        return config('app_catalog.'.$this->catalog_slug, []);
    }
}

<?php

namespace App\Domain\Hosting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotlinkProtection extends Model
{
    protected $fillable = [
        'hosting_account_id',
        'domain',
        'enabled',
        'extensions',
        'allowed_referrers',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'extensions' => 'array',
            'allowed_referrers' => 'array',
        ];
    }

    public function hostingAccount(): BelongsTo
    {
        return $this->belongsTo(HostingAccount::class);
    }
}

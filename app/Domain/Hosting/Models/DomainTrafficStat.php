<?php

namespace App\Domain\Hosting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainTrafficStat extends Model
{
    protected $fillable = [
        'hosting_account_id',
        'domain',
        'date',
        'hits',
        'unique_visitors',
        'top_paths',
        'status_counts',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'top_paths' => 'array',
            'status_counts' => 'array',
        ];
    }

    public function hostingAccount(): BelongsTo
    {
        return $this->belongsTo(HostingAccount::class);
    }
}

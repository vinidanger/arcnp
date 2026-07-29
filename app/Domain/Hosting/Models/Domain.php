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
        'subdirectory',
        'status',
        'last_error',
        'ssl_status',
        'ssl_error',
        'ssl_issued_at',
    ];

    protected function casts(): array
    {
        return [
            'ssl_issued_at' => 'datetime',
        ];
    }

    public function hostingAccount(): BelongsTo
    {
        return $this->belongsTo(HostingAccount::class);
    }
}

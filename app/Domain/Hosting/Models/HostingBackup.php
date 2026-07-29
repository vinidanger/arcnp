<?php

namespace App\Domain\Hosting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostingBackup extends Model
{
    protected $fillable = [
        'hosting_account_id',
        'status',
        'files',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'files' => 'array',
        ];
    }

    public function hostingAccount(): BelongsTo
    {
        return $this->belongsTo(HostingAccount::class);
    }
}

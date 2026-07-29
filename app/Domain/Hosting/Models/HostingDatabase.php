<?php

namespace App\Domain\Hosting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostingDatabase extends Model
{
    protected $fillable = [
        'hosting_account_id',
        'db_name',
        'db_username',
        'db_password',
    ];

    protected function casts(): array
    {
        return [
            'db_password' => 'encrypted',
        ];
    }

    public function hostingAccount(): BelongsTo
    {
        return $this->belongsTo(HostingAccount::class);
    }
}

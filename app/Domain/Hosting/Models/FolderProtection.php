<?php

namespace App\Domain\Hosting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FolderProtection extends Model
{
    protected $fillable = [
        'hosting_account_id',
        'domain',
        'path',
        'username',
        'password_hash',
    ];

    protected $hidden = [
        'password_hash',
    ];

    public function hostingAccount(): BelongsTo
    {
        return $this->belongsTo(HostingAccount::class);
    }
}

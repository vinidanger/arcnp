<?php

namespace App\Domain\Hosting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MimeTypeRule extends Model
{
    protected $fillable = [
        'hosting_account_id',
        'domain',
        'extension',
        'mime_type',
    ];

    public function hostingAccount(): BelongsTo
    {
        return $this->belongsTo(HostingAccount::class);
    }
}

<?php

namespace App\Domain\Hosting\Models;

use App\Domain\Servers\Models\Server;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FtpAccount extends Model
{
    protected $fillable = [
        'hosting_account_id',
        'server_id',
        'username',
        'path',
        'password_hash',
    ];

    protected $hidden = [
        'password_hash',
    ];

    public function hostingAccount(): BelongsTo
    {
        return $this->belongsTo(HostingAccount::class);
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}

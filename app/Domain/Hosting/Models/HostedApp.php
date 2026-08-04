<?php

namespace App\Domain\Hosting\Models;

use App\Domain\Servers\Models\Server;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostedApp extends Model
{
    protected $fillable = [
        'hosting_account_id',
        'server_id',
        'domain',
        'runtime',
        'entry_file',
        'port',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
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

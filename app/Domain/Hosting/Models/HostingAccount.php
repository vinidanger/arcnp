<?php

namespace App\Domain\Hosting\Models;

use App\Domain\Servers\Models\Server;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HostingAccount extends Model
{
    protected $fillable = [
        'user_id',
        'server_id',
        'plan_id',
        'linux_username',
        'primary_domain',
        'php_version',
        'status',
        'last_provision_error',
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

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function databases(): HasMany
    {
        return $this->hasMany(HostingDatabase::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }
}

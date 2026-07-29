<?php

namespace App\Domain\Servers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AgentCredential extends Model
{
    protected $fillable = [
        'server_id',
        'agent_uuid',
        'shared_secret',
        'issued_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'shared_secret' => 'encrypted',
            'issued_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $credential) {
            $credential->agent_uuid ??= (string) Str::uuid();
            $credential->issued_at ??= now();
        });
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}

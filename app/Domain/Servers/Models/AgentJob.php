<?php

namespace App\Domain\Servers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AgentJob extends Model
{
    protected $fillable = [
        'server_id',
        'uuid',
        'remote_job_id',
        'action',
        'payload',
        'status',
        'result',
        'error',
        'attempts',
        'dispatched_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'result' => 'array',
            'dispatched_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $job) {
            $job->uuid ??= (string) Str::uuid();
        });
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}

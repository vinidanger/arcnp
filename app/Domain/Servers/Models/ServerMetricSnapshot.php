<?php

namespace App\Domain\Servers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServerMetricSnapshot extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'server_id',
        'load_avg',
        'disk_percent',
        'mem_percent',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
        ];
    }

    public function server(): BelongsTo
    {
        return $this->belongsTo(Server::class);
    }
}

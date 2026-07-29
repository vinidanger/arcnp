<?php

namespace App\Domain\Hosting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CronJob extends Model
{
    protected $fillable = [
        'hosting_account_id',
        'minute',
        'hour',
        'day',
        'month',
        'weekday',
        'command',
    ];

    public function hostingAccount(): BelongsTo
    {
        return $this->belongsTo(HostingAccount::class);
    }

    public function schedule(): string
    {
        return "{$this->minute} {$this->hour} {$this->day} {$this->month} {$this->weekday}";
    }
}

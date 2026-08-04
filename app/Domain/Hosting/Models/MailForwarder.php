<?php

namespace App\Domain\Hosting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MailForwarder extends Model
{
    protected $fillable = [
        'mail_domain_id',
        'local_part',
        'destination',
    ];

    public function mailDomain(): BelongsTo
    {
        return $this->belongsTo(MailDomain::class);
    }

    public function source(): string
    {
        return "{$this->local_part}@{$this->mailDomain->domain}";
    }
}

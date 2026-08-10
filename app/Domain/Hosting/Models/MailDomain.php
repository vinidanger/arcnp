<?php

namespace App\Domain\Hosting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MailDomain extends Model
{
    protected $fillable = [
        'hosting_account_id',
        'domain',
        'dkim_txt_value',
        'spf_valid',
        'dkim_valid',
        'dmarc_valid',
        'health_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'spf_valid' => 'boolean',
            'dkim_valid' => 'boolean',
            'dmarc_valid' => 'boolean',
            'health_checked_at' => 'datetime',
        ];
    }

    public function hostingAccount(): BelongsTo
    {
        return $this->belongsTo(HostingAccount::class);
    }

    public function mailboxes(): HasMany
    {
        return $this->hasMany(Mailbox::class);
    }

    public function forwarders(): HasMany
    {
        return $this->hasMany(MailForwarder::class);
    }

    public function dkimSelector(): string
    {
        return 'mail';
    }

    public function spfRecordValue(): string
    {
        return 'v=spf1 mx a ~all';
    }

    public function dmarcRecordValue(): string
    {
        return "v=DMARC1; p=none; rua=mailto:postmaster@{$this->domain}";
    }
}

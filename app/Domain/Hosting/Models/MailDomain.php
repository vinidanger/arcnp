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
    ];

    public function hostingAccount(): BelongsTo
    {
        return $this->belongsTo(HostingAccount::class);
    }

    public function mailboxes(): HasMany
    {
        return $this->hasMany(Mailbox::class);
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

<?php

namespace App\Domain\Hosting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Mailbox extends Model
{
    protected $fillable = [
        'mail_domain_id',
        'local_part',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            // Reversível (não hash) de propósito: precisamos da senha em
            // texto puro pra gerar o token de SSO do webmail e pra
            // recalcular o hash que o Dovecot espera a cada sincronização
            // — mesmo motivo do ssh_password ser "encrypted" em vez de
            // hasheado.
            'password' => 'encrypted',
        ];
    }

    public function mailDomain(): BelongsTo
    {
        return $this->belongsTo(MailDomain::class);
    }

    public function vacation(): HasOne
    {
        return $this->hasOne(MailVacation::class);
    }

    public function email(): string
    {
        return "{$this->local_part}@{$this->mailDomain->domain}";
    }
}

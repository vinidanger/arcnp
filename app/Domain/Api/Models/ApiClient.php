<?php

namespace App\Domain\Api\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

/**
 * Credencial de integração machine-to-machine (ex: outro painel criando
 * conta via API) — não é um usuário de verdade, nunca loga via
 * sessão/senha, só existe pra ter um token Sanctum associado.
 */
class ApiClient extends Model implements AuthenticatableContract
{
    use Authenticatable, HasApiTokens;

    protected $fillable = [
        'name',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
        ];
    }
}

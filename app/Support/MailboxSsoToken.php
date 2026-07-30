<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Token de uso único pra login SSO no Roundcube — mesmo esquema do
 * DatabaseSsoToken (phpMyAdmin): assinado com o shared_secret já usado
 * pra assinar as requisições Painel -> Agent desse servidor, nunca um
 * segredo novo. Validado por deploy/roundcube/sso-login.php no Agent.
 */
class MailboxSsoToken
{
    private const TTL_SECONDS = 60;

    public static function generate(string $email, string $password, string $sharedSecret): string
    {
        $payload = base64_encode(json_encode([
            'u' => $email,
            'p' => $password,
            'exp' => now()->addSeconds(self::TTL_SECONDS)->timestamp,
            'n' => Str::random(24),
        ]));

        $signature = hash_hmac('sha256', $payload, $sharedSecret);

        return "{$payload}.{$signature}";
    }
}

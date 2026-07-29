<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Token de uso único pra login SSO no phpMyAdmin (auth_type=signon) —
 * assinado com o mesmo shared_secret já usado pra assinar as
 * requisições Painel -> Agent desse servidor (AgentCredential), nunca
 * um segredo novo. Validado por deploy/phpmyadmin/sso-login.php no
 * Agent (script PHP puro, sem framework — por isso o formato é
 * deliberadamente simples: payload base64 + assinatura, sem JWT).
 */
class DatabaseSsoToken
{
    private const TTL_SECONDS = 60;

    public static function generate(string $dbUsername, string $dbPassword, string $sharedSecret): string
    {
        $payload = base64_encode(json_encode([
            'u' => $dbUsername,
            'p' => $dbPassword,
            'exp' => now()->addSeconds(self::TTL_SECONDS)->timestamp,
            'n' => Str::random(24),
        ]));

        $signature = hash_hmac('sha256', $payload, $sharedSecret);

        return "{$payload}.{$signature}";
    }
}

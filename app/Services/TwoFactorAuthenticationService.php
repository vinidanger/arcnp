<?php

namespace App\Services;

use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

/**
 * Fina camada sobre pragmarx/google2fa (só a lib "core", sem o wrapper
 * -laravel — não precisamos de middleware nem das views prontas dele,
 * só gerar segredo/verificar código). Evita criptografia/HOTP caseira
 * — TOTP (RFC 6238) tem detalhe suficiente (janela de tolerância,
 * base32, HMAC) pra não valer a pena reimplementar.
 */
class TwoFactorAuthenticationService
{
    private Google2FA $engine;

    public function __construct()
    {
        $this->engine = new Google2FA;
    }

    public function generateSecretKey(): string
    {
        return $this->engine->generateSecretKey();
    }

    public function verify(string $secret, string $code): bool
    {
        return $this->engine->verifyKey($secret, $code) !== false;
    }

    /**
     * @return list<string>
     */
    public function generateRecoveryCodes(): array
    {
        return collect(range(1, 8))
            ->map(fn () => Str::upper(Str::random(4)).'-'.Str::upper(Str::random(4)))
            ->all();
    }
}

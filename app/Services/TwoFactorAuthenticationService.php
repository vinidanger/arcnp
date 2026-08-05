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
     * URI padrão otpauth:// (RFC — usado por todo app autenticador pra
     * ler via QR code). Não precisa de nenhuma lib de geração de
     * imagem no servidor — é só um link; o QR em si é desenhado no
     * navegador (ver resources/js/two-factor-qr.js), a partir desse
     * texto puro.
     */
    public function generateQrCodeUri(string $email, string $secret): string
    {
        $issuer = config('app.name');
        $label = rawurlencode("{$issuer}:{$email}");

        return "otpauth://totp/{$label}?secret={$secret}&issuer=".rawurlencode($issuer).'&algorithm=SHA1&digits=6&period=30';
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

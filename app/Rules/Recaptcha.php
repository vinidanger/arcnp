<?php

namespace App\Rules;

use App\Models\Setting;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;

/**
 * reCAPTCHA v3 — sem dependência nova via composer, é só uma chamada
 * HTTP direta pro endpoint de verificação do Google. Score mínimo fixo
 * em 0.5 (o "meio do caminho" recomendado pelo Google pra v3, que não
 * tem desafio visual — só um score de confiança).
 */
class Recaptcha implements ValidationRule
{
    private const MIN_SCORE = 0.5;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secretKey = Setting::get('recaptcha_secret_key');

        if (! $secretKey || ! $value) {
            $fail('Verificação de segurança falhou. Tente novamente.');

            return;
        }

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => $secretKey,
            'response' => $value,
        ]);

        $data = $response->json() ?? [];

        if (! ($data['success'] ?? false) || ($data['score'] ?? 0) < self::MIN_SCORE) {
            $fail('Verificação de segurança falhou. Tente novamente.');
        }
    }
}

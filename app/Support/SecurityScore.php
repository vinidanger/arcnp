<?php

namespace App\Support;

use App\Domain\Hosting\Models\HostingAccount;

/**
 * Pura síntese de sinais que já existem em outras telas (WAF, malware,
 * SSL, CMS desatualizado, 2FA) — nenhuma chamada de rede nova, nenhuma
 * tabela nova. Só combina o que já é calculado/armazenado em outro
 * lugar numa nota só, fácil de escanear numa listagem.
 *
 * Pra evitar N+1 numa listagem com várias contas, o chamador precisa
 * eager-load `client`, `malwareScans` (só a mais recente já basta) e
 * `appInstallations` antes de instanciar isso em loop.
 */
class SecurityScore
{
    private const POINTS_PER_CHECK = 20;

    public static function calculate(HostingAccount $account): array
    {
        $latestScan = $account->malwareScans->first();

        $checks = [
            'waf' => (bool) $account->waf_enabled,
            'malware' => $latestScan !== null && ! $latestScan->hasActionableInfectedFiles(),
            'ssl' => $account->ssl_status === 'active',
            'cms_updated' => $account->appInstallations->every(fn ($installation) => ! $installation->isOutdated()),
            'two_factor' => (bool) $account->client?->hasTwoFactorEnabled(),
        ];

        $score = collect($checks)->filter()->count() * self::POINTS_PER_CHECK;

        return [
            'score' => $score,
            'grade' => match (true) {
                $score >= 90 => 'A',
                $score >= 70 => 'B',
                $score >= 50 => 'C',
                default => 'D',
            },
            'checks' => $checks,
        ];
    }
}

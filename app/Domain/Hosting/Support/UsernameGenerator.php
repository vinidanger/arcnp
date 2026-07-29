<?php

namespace App\Domain\Hosting\Support;

use App\Domain\Hosting\Models\HostingAccount;
use Illuminate\Support\Str;

/**
 * Gera o username Linux a partir do domínio (estilo DirectAdmin),
 * garantindo compatibilidade com a regex validada no Agent
 * (^[a-z][a-z0-9]{2,31}$) e unicidade contra hosting_accounts.
 */
class UsernameGenerator
{
    private const MAX_BASE_LENGTH = 8;

    public static function fromDomain(string $domain): string
    {
        $base = Str::of($domain)
            ->before('.')
            ->lower()
            ->replaceMatches('/[^a-z0-9]/', '')
            ->substr(0, self::MAX_BASE_LENGTH)
            ->toString();

        if ($base === '' || ctype_digit($base[0])) {
            $base = 'u'.$base;
        }

        $base = substr($base, 0, self::MAX_BASE_LENGTH);

        $username = $base;
        $suffix = 1;

        while (HostingAccount::where('linux_username', $username)->exists()) {
            $suffixStr = (string) $suffix;
            $username = substr($base, 0, self::MAX_BASE_LENGTH - strlen($suffixStr)).$suffixStr;
            $suffix++;
        }

        return $username;
    }
}

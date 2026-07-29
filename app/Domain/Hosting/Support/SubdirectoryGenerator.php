<?php

namespace App\Domain\Hosting\Support;

use App\Domain\Hosting\Models\HostingAccount;
use Illuminate\Support\Str;

/**
 * Deriva o nome do subdiretório (dentro de public_html) a partir do
 * domínio/subdomínio — só precisa ser único DENTRO da conta (não
 * globalmente, ao contrário do username Linux).
 */
class SubdirectoryGenerator
{
    private const MAX_LENGTH = 32;

    public static function fromDomain(HostingAccount $account, string $domain): string
    {
        $base = Str::of($domain)
            ->before('.')
            ->lower()
            ->replaceMatches('/[^a-z0-9_-]/', '')
            ->substr(0, self::MAX_LENGTH)
            ->toString();

        if ($base === '' || ctype_digit($base[0])) {
            $base = 'd'.$base;
        }

        $base = substr($base, 0, self::MAX_LENGTH);

        $subdir = $base;
        $suffix = 1;

        while ($account->domains()->where('subdirectory', $subdir)->exists()) {
            $suffixStr = (string) $suffix;
            $subdir = substr($base, 0, self::MAX_LENGTH - strlen($suffixStr)).$suffixStr;
            $suffix++;
        }

        return $subdir;
    }
}

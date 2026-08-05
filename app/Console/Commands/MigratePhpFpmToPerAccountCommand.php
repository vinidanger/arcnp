<?php

namespace App\Console\Commands;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Services\HostingAccountProvisioningService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Migração ÚNICA, rodada manualmente uma vez no deploy que introduziu
 * "1 processo PHP-FPM por conta" — não é agendada (ao contrário do
 * resto de app/Console/Commands), nunca deve rodar sozinha, pode ser
 * rodada de novo com segurança pra contas que falharem numa primeira
 * tentativa (idempotente por conta: uma conta já migrada só faz o
 * service ser reescrito com o mesmo conteúdo e o vhost re-renderizado,
 * sem efeito colateral).
 */
class MigratePhpFpmToPerAccountCommand extends Command
{
    protected $signature = 'php-fpm:migrate-to-per-account';

    protected $description = 'Cria o processo PHP-FPM dedicado (novo) pra cada conta ativa e re-renderiza os vhosts — rodar uma vez no deploy da feature de limites de recursos, antes de desativar os services antigos compartilhados.';

    public function handle(HostingAccountProvisioningService $provisioning): int
    {
        $accounts = HostingAccount::where('status', 'active')->get();
        $this->info("{$accounts->count()} conta(s) ativa(s) encontrada(s).");

        $ok = 0;
        $failed = 0;

        foreach ($accounts as $account) {
            try {
                $provisioning->migratePhpFpmArchitecture($account);
                $ok++;
                $this->line("OK: {$account->linux_username} ({$account->primary_domain})");
            } catch (Throwable $e) {
                $failed++;
                $this->error("FALHA: {$account->linux_username} ({$account->primary_domain}): {$e->getMessage()}");
            }
        }

        $this->info("Concluído: {$ok} migrada(s), {$failed} com falha.");

        if ($failed > 0) {
            $this->warn('Contas com falha continuam servindo pelo mecanismo antigo (nada foi desativado) — rode o comando de novo depois de investigar.');
        } else {
            $this->warn('Todas migradas. Confirme "systemctl status arcnp-php-*" na VPS antes de seguir pra limpeza manual dos services antigos (ver deploy/README.md).');
        }

        return self::SUCCESS;
    }
}

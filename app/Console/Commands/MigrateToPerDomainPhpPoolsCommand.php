<?php

namespace App\Console\Commands;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Services\HostingAccountProvisioningService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Migração ÚNICA, rodada manualmente uma vez no deploy que introduziu
 * "PHP por domínio, não por conta" — não é agendada (ao contrário do
 * resto de app/Console/Commands), nunca deve rodar sozinha, pode ser
 * rodada de novo com segurança pra contas que falharem numa primeira
 * tentativa (idempotente por conta: uma conta já migrada só faz os
 * processos serem reescritos com o mesmo conteúdo e os vhosts
 * re-renderizados, sem efeito colateral).
 */
class MigrateToPerDomainPhpPoolsCommand extends Command
{
    protected $signature = 'php-fpm:migrate-to-per-domain-pools';

    protected $description = 'Cria os processos PHP-FPM por domínio (novos) pra cada conta ativa e re-renderiza os vhosts — rodar uma vez no deploy da feature de PHP por domínio, antes de desativar os processos antigos por conta.';

    public function handle(HostingAccountProvisioningService $provisioning): int
    {
        $accounts = HostingAccount::where('status', 'active')->get();
        $this->info("{$accounts->count()} conta(s) ativa(s) encontrada(s).");

        $ok = 0;
        $failed = 0;

        foreach ($accounts as $account) {
            try {
                $provisioning->migrateToPerDomainPools($account);
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
            $this->warn('Todas migradas. Confirme "systemctl status arcnp-php-*" na VPS antes de seguir pra limpeza manual dos processos antigos (ver deploy/README.md).');
        }

        return self::SUCCESS;
    }
}

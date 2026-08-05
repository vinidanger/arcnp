<?php

use App\Domain\Hosting\Models\HostingAccount;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PHP por domínio, não só por conta: cada domínio (adicional/subdomínio
 * — o principal continua representado pelas colunas já existentes em
 * hosting_accounts, ver plano) ganha sua própria versão/configurações
 * de PHP-FPM, independentes dos demais domínios da mesma conta.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->string('php_version')->nullable()->after('type');
            $table->json('php_fpm_settings')->nullable()->after('php_version');
        });

        // Backfill: cada domínio existente herda a versão/settings ATUAIS
        // da própria conta — continuidade de comportamento (hoje eles já
        // compartilham o mesmo processo/config da conta, então herdar o
        // valor atual no backfill não muda nada do que já está rodando).
        HostingAccount::query()->with('domains')->chunkById(100, function ($accounts) {
            foreach ($accounts as $account) {
                foreach ($account->domains as $domain) {
                    $domain->update([
                        'php_version' => $account->php_version,
                        'php_fpm_settings' => $account->php_fpm_settings,
                    ]);
                }
            }
        });

        // Sem doctrine/dbal instalado, ALTER cru (mesmo padrão já usado em
        // 2026_08_05_170240_add_username_and_optional_email_to_users_table.php).
        DB::statement("ALTER TABLE domains MODIFY php_version VARCHAR(255) NOT NULL");
    }

    public function down(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn(['php_version', 'php_fpm_settings']);
        });
    }
};

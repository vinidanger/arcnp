<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Painel deixa de usar e-mail como credencial de login (admin passa a
 * logar por "username", cliente já loga pelo linux_username da própria
 * hospedagem desde a mudança anterior) — e-mail vira só um campo de
 * contato/referência, opcional, sem exigir unicidade.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
        });

        // E-mail nulo/repetido — sem doctrine/dbal instalado, ALTER cru
        // (mesmo padrão já usado em
        // 2026_07_29_035554_add_suspended_status_to_hosting_accounts_table.php).
        DB::statement('ALTER TABLE users DROP INDEX users_email_unique');
        DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NULL');

        // Backfill: admin existente precisa continuar conseguindo logar
        // depois do deploy — deriva o username da parte antes do @ do
        // e-mail, sanitizado, com sufixo numérico se colidir (mesmo
        // espírito do UsernameGenerator::fromDomain() já usado pras
        // hospedagens, reescrito aqui sem depender da classe da app
        // porque migration deve ficar independente de código que muda).
        $admins = DB::table('users')->where('type', 'admin')->whereNull('username')->orderBy('id')->get(['id', 'email', 'name']);

        foreach ($admins as $admin) {
            $base = strtolower((string) preg_replace('/[^a-z0-9]/i', '', explode('@', $admin->email ?? '')[0] ?? ''));
            $base = $base !== '' ? substr($base, 0, 20) : 'admin';

            $username = $base;
            $suffix = 1;

            while (DB::table('users')->where('username', $username)->exists()) {
                $username = $base.$suffix;
                $suffix++;
            }

            DB::table('users')->where('id', $admin->id)->update(['username' => $username]);
        }
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NOT NULL');
        DB::statement('ALTER TABLE users ADD UNIQUE users_email_unique (email)');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};

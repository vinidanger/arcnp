<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->unsignedTinyInteger('max_cron_jobs')->default(0)->after('max_addon_domains');
            $table->unsignedSmallInteger('max_email_accounts')->default(0)->after('max_cron_jobs');
            // Sem aplicação real ainda (guardado pra quando entrar
            // enforcement via cgroup/systemd no Agent) — por isso
            // nullable, "0"/vazio aqui não significa "bloqueado".
            $table->unsignedTinyInteger('cpu_cores')->nullable()->after('max_email_accounts');
            $table->unsignedSmallInteger('max_processes')->nullable()->after('cpu_cores');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['max_cron_jobs', 'max_email_accounts', 'cpu_cores', 'max_processes']);
        });
    }
};

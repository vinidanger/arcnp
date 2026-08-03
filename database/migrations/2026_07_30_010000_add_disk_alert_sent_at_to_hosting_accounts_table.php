<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hosting_accounts', function (Blueprint $table) {
            // Evita reenviar o aviso toda hora enquanto a conta continuar
            // acima do limiar — só dispara de novo se cair e passar de
            // novo. Null = não avisado (ou já voltou pra baixo do limiar).
            $table->timestamp('disk_alert_sent_at')->nullable()->after('disk_usage_checked_at');
        });
    }

    public function down(): void
    {
        Schema::table('hosting_accounts', function (Blueprint $table) {
            $table->dropColumn('disk_alert_sent_at');
        });
    }
};

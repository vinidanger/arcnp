<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            // mysqld (Oracle MySQL) ou mariadb (MariaDB), varia conforme
            // o que foi instalado manualmente no servidor — não dá pra
            // fixar um nome só (ver CollectServerInfoAction no Agent).
            $table->string('mysql_service_name')->nullable()->default('mysqld');
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('mysql_service_name');
        });
    }
};

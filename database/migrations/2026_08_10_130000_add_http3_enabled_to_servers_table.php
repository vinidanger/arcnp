<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Flag por SERVIDOR, não por domínio (diferente de waf_enabled/
        // cache_enabled) — o admin só liga DEPOIS de confirmar
        // manualmente que o binário do nginx desse servidor já suporta
        // QUIC (troca de binário + lib TLS, ver seção 52 do
        // deploy/README.md do Agent).
        Schema::table('servers', function (Blueprint $table) {
            $table->boolean('http3_enabled')->default(false)->after('mysql_service_name');
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('http3_enabled');
        });
    }
};

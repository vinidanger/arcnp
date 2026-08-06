<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Opt-in por domínio (não por servidor inteiro) — falso
        // positivo do WAF pode quebrar a aplicação de um cliente, ver
        // seção 47 do deploy/README.md do Agent.
        Schema::table('hosting_accounts', function (Blueprint $table) {
            $table->boolean('waf_enabled')->default(false)->after('ssh_enabled');
        });

        Schema::table('domains', function (Blueprint $table) {
            $table->boolean('waf_enabled')->default(false)->after('ssl_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('hosting_accounts', function (Blueprint $table) {
            $table->dropColumn('waf_enabled');
        });

        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn('waf_enabled');
        });
    }
};

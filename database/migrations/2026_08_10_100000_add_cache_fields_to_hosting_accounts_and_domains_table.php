<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Opt-in por domínio, mesmo padrão do WAF (waf_enabled) — a
        // versão entra na própria fastcgi_cache_key do vhost, e
        // "purgar" é só incrementar ela + re-renderizar o vhost, ver
        // App\Support\CacheDirectives (Agent) e seção 49 do
        // deploy/README.md.
        Schema::table('hosting_accounts', function (Blueprint $table) {
            $table->boolean('cache_enabled')->default(false)->after('waf_enabled');
            $table->unsignedInteger('cache_version')->default(1)->after('cache_enabled');
        });

        Schema::table('domains', function (Blueprint $table) {
            $table->boolean('cache_enabled')->default(false)->after('waf_enabled');
            $table->unsignedInteger('cache_version')->default(1)->after('cache_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('hosting_accounts', function (Blueprint $table) {
            $table->dropColumn(['cache_enabled', 'cache_version']);
        });

        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn(['cache_enabled', 'cache_version']);
        });
    }
};

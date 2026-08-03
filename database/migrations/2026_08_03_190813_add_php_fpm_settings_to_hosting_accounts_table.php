<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hosting_accounts', function (Blueprint $table) {
            // Nulo = usa os defaults do Agent (config('provisioning.default_pool_settings')).
            $table->json('php_fpm_settings')->nullable()->after('php_version');
        });
    }

    public function down(): void
    {
        Schema::table('hosting_accounts', function (Blueprint $table) {
            $table->dropColumn('php_fpm_settings');
        });
    }
};

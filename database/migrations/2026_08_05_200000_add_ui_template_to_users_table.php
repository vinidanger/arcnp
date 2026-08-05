<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Alternador de template visual do painel do CLIENTE (Padrão/cPanel) —
 * `null` = "default" (mesmo padrão "ausente = comportamento atual" já
 * usado em php_fpm_settings). `ui_template_locked`: quando true, só o
 * admin pode trocar (cliente não vê/não consegue mandar a troca).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('ui_template')->nullable()->after('status');
            $table->boolean('ui_template_locked')->default(false)->after('ui_template');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['ui_template', 'ui_template_locked']);
        });
    }
};

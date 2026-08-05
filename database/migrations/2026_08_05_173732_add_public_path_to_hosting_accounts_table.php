<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hosting_accounts', function (Blueprint $table) {
            // Subpasta dentro de public_html que o nginx serve de fato —
            // vazio/null = comportamento de sempre (serve a raiz). Existe
            // pra apps tipo Laravel/Symfony, cujo index real fica em
            // public/, não na raiz do projeto.
            $table->string('public_path')->nullable()->after('primary_domain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hosting_accounts', function (Blueprint $table) {
            $table->dropColumn('public_path');
        });
    }
};

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
        Schema::table('servers', function (Blueprint $table) {
            // false só é usado em dev local (agent sem TLS via `php artisan serve`);
            // em produção o Agent sempre fica atrás de TLS (ver deploy/README.md).
            $table->boolean('use_tls')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn('use_tls');
        });
    }
};

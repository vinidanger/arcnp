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
        // Snapshot mais recente reportado pelo heartbeat — não é série
        // histórica (isso é Módulo 9 / Monitoramento, fase futura).
        Schema::table('servers', function (Blueprint $table) {
            $table->decimal('load_avg', 6, 2)->nullable();
            $table->decimal('disk_percent', 5, 1)->nullable();
            $table->decimal('mem_percent', 5, 1)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn(['load_avg', 'disk_percent', 'mem_percent']);
        });
    }
};

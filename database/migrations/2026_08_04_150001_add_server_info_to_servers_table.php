<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            // Resto do que "Coletar agora" traz (kernel, arquitetura,
            // modelo de CPU, uptime, IPs, discos por ponto de montagem,
            // status de cada serviço) — os/cpu_cores/memory_mb já têm
            // coluna própria e continuam sendo reaproveitadas.
            $table->json('server_info')->nullable();
            $table->timestamp('server_info_collected_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn(['server_info', 'server_info_collected_at']);
        });
    }
};

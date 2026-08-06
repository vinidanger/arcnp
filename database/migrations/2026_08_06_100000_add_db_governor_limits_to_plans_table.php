<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Substituto caseiro do "database governor" do CloudLinux — só a
            // parte nativa do MariaDB (WITH MAX_USER_CONNECTIONS/
            // MAX_QUERIES_PER_HOUR), aplicado a cada usuário MySQL da conta
            // via HostingAccountProvisioningService. null = sem limite.
            $table->unsignedInteger('max_db_connections')->nullable()->after('io_weight');
            $table->unsignedInteger('max_db_queries_per_hour')->nullable()->after('max_db_connections');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['max_db_connections', 'max_db_queries_per_hour']);
        });
    }
};

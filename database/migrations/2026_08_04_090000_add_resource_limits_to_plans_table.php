<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // memory_limit_mb/io_weight completam o que cpu_cores/max_processes
            // já tinham deixado reservado — os 4 juntos viram CPUQuota/
            // MemoryMax/TasksMax/IOWeight do user-{uid}.slice da conta (ver
            // HostingAccountProvisioningService::syncResourceLimits()).
            $table->unsignedInteger('memory_limit_mb')->nullable()->after('max_processes');
            $table->unsignedSmallInteger('io_weight')->nullable()->after('memory_limit_mb');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn(['memory_limit_mb', 'io_weight']);
        });
    }
};

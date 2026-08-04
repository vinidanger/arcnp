<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_metric_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->float('load_avg')->nullable();
            $table->float('disk_percent')->nullable();
            $table->float('mem_percent')->nullable();
            $table->timestamp('recorded_at');

            $table->index(['server_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_metric_snapshots');
    }
};

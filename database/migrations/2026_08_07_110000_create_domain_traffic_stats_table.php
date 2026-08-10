<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_traffic_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_account_id')->constrained()->cascadeOnDelete();
            $table->string('domain');
            $table->date('date');
            $table->unsignedInteger('hits')->default(0);
            $table->unsignedInteger('unique_visitors')->default(0);
            $table->json('top_paths')->nullable();
            $table->json('status_counts')->nullable();
            $table->timestamps();

            $table->unique(['domain', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_traffic_stats');
    }
};

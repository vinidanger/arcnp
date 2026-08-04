<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_installations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_account_id')->constrained()->cascadeOnDelete();
            $table->string('domain');
            $table->string('path')->default('');
            $table->string('catalog_slug');
            $table->enum('status', ['installing', 'active', 'failed'])->default('installing');
            $table->foreignId('database_id')->nullable()->constrained('hosting_databases')->nullOnDelete();
            $table->string('admin_username')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('installed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_installations');
    }
};

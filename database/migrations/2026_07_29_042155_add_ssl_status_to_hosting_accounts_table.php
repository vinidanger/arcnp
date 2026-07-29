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
            $table->enum('ssl_status', ['none', 'pending', 'active', 'failed'])->default('none');
            $table->text('ssl_error')->nullable();
            $table->timestamp('ssl_issued_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hosting_accounts', function (Blueprint $table) {
            $table->dropColumn(['ssl_status', 'ssl_error', 'ssl_issued_at']);
        });
    }
};

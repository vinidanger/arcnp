<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hosting_accounts', function (Blueprint $table) {
            $table->string('backup_frequency')->default('disabled'); // disabled|daily|weekly
            $table->timestamp('last_backup_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('hosting_accounts', function (Blueprint $table) {
            $table->dropColumn(['backup_frequency', 'last_backup_at']);
        });
    }
};

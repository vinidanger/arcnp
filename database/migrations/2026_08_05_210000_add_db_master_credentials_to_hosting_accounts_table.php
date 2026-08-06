<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hosting_accounts', function (Blueprint $table) {
            $table->string('db_master_username')->nullable()->after('ssh_password');
            $table->text('db_master_password')->nullable()->after('db_master_username');
        });
    }

    public function down(): void
    {
        Schema::table('hosting_accounts', function (Blueprint $table) {
            $table->dropColumn(['db_master_username', 'db_master_password']);
        });
    }
};

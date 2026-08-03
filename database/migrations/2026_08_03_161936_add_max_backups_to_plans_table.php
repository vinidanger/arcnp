<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            // Default 5 (não 0) pra não travar de imediato as contas dos
            // planos já cadastrados assim que este limite passar a valer.
            $table->unsignedTinyInteger('max_backups')->default(5)->after('max_email_accounts');
        });
    }

    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('max_backups');
        });
    }
};

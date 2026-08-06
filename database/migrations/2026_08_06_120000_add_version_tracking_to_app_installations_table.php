<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_installations', function (Blueprint $table) {
            $table->string('detected_version')->nullable()->after('admin_username');
            $table->string('latest_known_version')->nullable()->after('detected_version');
            $table->timestamp('version_checked_at')->nullable()->after('latest_known_version');
        });
    }

    public function down(): void
    {
        Schema::table('app_installations', function (Blueprint $table) {
            $table->dropColumn(['detected_version', 'latest_known_version', 'version_checked_at']);
        });
    }
};

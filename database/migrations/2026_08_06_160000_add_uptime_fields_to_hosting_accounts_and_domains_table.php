<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['hosting_accounts', 'domains'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->string('uptime_status')->default('unknown');
                $blueprint->timestamp('uptime_checked_at')->nullable();
                $blueprint->unsignedInteger('uptime_consecutive_failures')->default(0);
                $blueprint->timestamp('uptime_down_since')->nullable();
                $blueprint->timestamp('uptime_alert_sent_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['hosting_accounts', 'domains'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn([
                    'uptime_status',
                    'uptime_checked_at',
                    'uptime_consecutive_failures',
                    'uptime_down_since',
                    'uptime_alert_sent_at',
                ]);
            });
        }
    }
};

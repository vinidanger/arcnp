<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mail_domains', function (Blueprint $blueprint) {
            $blueprint->boolean('spf_valid')->nullable();
            $blueprint->boolean('dkim_valid')->nullable();
            $blueprint->boolean('dmarc_valid')->nullable();
            $blueprint->timestamp('health_checked_at')->nullable();
        });

        Schema::table('servers', function (Blueprint $blueprint) {
            $blueprint->boolean('ptr_matches_mail_hostname')->nullable();
            $blueprint->boolean('ip_blacklisted')->nullable();
            $blueprint->timestamp('mail_health_checked_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('mail_domains', function (Blueprint $blueprint) {
            $blueprint->dropColumn(['spf_valid', 'dkim_valid', 'dmarc_valid', 'health_checked_at']);
        });

        Schema::table('servers', function (Blueprint $blueprint) {
            $blueprint->dropColumn(['ptr_matches_mail_hostname', 'ip_blacklisted', 'mail_health_checked_at']);
        });
    }
};

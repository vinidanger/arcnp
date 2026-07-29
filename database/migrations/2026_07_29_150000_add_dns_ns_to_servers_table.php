<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->string('dns_ns1')->nullable()->after('public_ip_address');
            $table->string('dns_ns2')->nullable()->after('dns_ns1');
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumn(['dns_ns1', 'dns_ns2']);
        });
    }
};

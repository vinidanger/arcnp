<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_redirects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_account_id')->constrained()->cascadeOnDelete();
            $table->string('domain');
            $table->string('path');
            $table->string('destination');
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->timestamps();

            $table->unique(['hosting_account_id', 'domain', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_redirects');
    }
};

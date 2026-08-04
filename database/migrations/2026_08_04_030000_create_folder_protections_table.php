<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('folder_protections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_account_id')->constrained()->cascadeOnDelete();
            $table->string('domain');
            $table->string('path');
            $table->string('username');
            $table->string('password_hash');
            $table->timestamps();

            $table->unique(['hosting_account_id', 'domain', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('folder_protections');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ftp_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('username');
            $table->string('path')->default('');
            $table->string('password_hash');
            $table->timestamps();

            // O pam_userdb do vsftpd é um namespace só por SERVIDOR (uma
            // instância vsftpd por VPS) — não por conta, não global na
            // plataforma. Dois servidores diferentes podem ter o mesmo
            // username FTP sem conflito.
            $table->unique(['server_id', 'username']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ftp_accounts');
    }
};

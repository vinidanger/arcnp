<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hosted_apps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('domain');
            $table->enum('runtime', ['node', 'python']);
            $table->string('entry_file');
            $table->unsignedInteger('port');
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['hosting_account_id', 'domain']);

            // Porta interna alocada por servidor (uma instância nginx/
            // systemd por VPS) — mesmo raciocínio do username FTP.
            $table->unique(['server_id', 'port']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hosted_apps');
    }
};

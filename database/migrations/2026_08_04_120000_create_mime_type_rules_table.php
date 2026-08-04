<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mime_type_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_account_id')->constrained()->cascadeOnDelete();
            $table->string('domain');
            $table->string('extension');
            $table->string('mime_type');
            $table->timestamps();

            $table->unique(['hosting_account_id', 'domain', 'extension']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mime_type_rules');
    }
};

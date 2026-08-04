<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_filters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mailbox_id')->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->enum('field', ['from', 'subject', 'to']);
            $table->string('value');
            $table->enum('action', ['discard', 'move_to_folder']);
            $table->string('folder')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_filters');
    }
};

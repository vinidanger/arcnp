<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_vacations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mailbox_id')->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(false);
            $table->string('subject');
            $table->text('message');
            $table->timestamps();

            $table->unique('mailbox_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_vacations');
    }
};

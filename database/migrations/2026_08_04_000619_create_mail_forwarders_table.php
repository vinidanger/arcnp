<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_forwarders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mail_domain_id')->constrained()->cascadeOnDelete();
            $table->string('local_part');
            $table->string('destination');
            $table->timestamps();

            $table->unique(['mail_domain_id', 'local_part']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_forwarders');
    }
};

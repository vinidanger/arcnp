<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotlink_protections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hosting_account_id')->constrained()->cascadeOnDelete();
            $table->string('domain');
            $table->boolean('enabled')->default(false);
            $table->json('extensions');
            $table->json('allowed_referrers')->nullable();
            $table->timestamps();

            $table->unique(['hosting_account_id', 'domain']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotlink_protections');
    }
};

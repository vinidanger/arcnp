<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->string('location')->default('inside_public_html')->after('type'); // inside_public_html|outside_public_html
        });

        // subdirectory não tem sentido pra outside_public_html — sem
        // doctrine/dbal instalado pra usar ->change(), altera direto.
        DB::statement('ALTER TABLE domains MODIFY subdirectory VARCHAR(255) NULL');
    }

    public function down(): void
    {
        DB::statement("UPDATE domains SET subdirectory = '' WHERE subdirectory IS NULL");
        DB::statement('ALTER TABLE domains MODIFY subdirectory VARCHAR(255) NOT NULL');

        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn('location');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // VARCHAR(255) estourava com chave pública DKIM (RSA 2048 dá uns
        // 400+ caracteres no valor do TXT) — sem doctrine/dbal pra usar
        // ->change(), altera direto.
        DB::statement('ALTER TABLE dns_records MODIFY content TEXT NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE dns_records MODIFY content VARCHAR(255) NOT NULL');
    }
};

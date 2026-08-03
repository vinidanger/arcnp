<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Chave-valor simples pra configurações globais editáveis pelo admin
 * (ex.: tamanho máximo de upload) — diferente dos limites por Plan
 * (disco, bancos, backups etc.), que são por conta de hospedagem.
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}

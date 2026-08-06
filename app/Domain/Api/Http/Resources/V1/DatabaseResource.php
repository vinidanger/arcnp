<?php

namespace App\Domain\Api\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * "db_password" nunca aparece aqui de propósito — mesma política do
 * resto da API nova (credencial só aparece 1x na criação, nunca numa
 * leitura), mesmo o campo sendo reversível no model.
 *
 * @mixin \App\Domain\Hosting\Models\HostingDatabase
 */
class DatabaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'db_name' => $this->db_name,
            'db_username' => $this->db_username,
            'created_at' => $this->created_at,
        ];
    }
}

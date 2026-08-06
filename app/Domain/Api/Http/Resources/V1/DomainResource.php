<?php

namespace App\Domain\Api\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\Hosting\Models\Domain */
class DomainResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'domain' => $this->domain,
            'type' => $this->type,
            'location' => $this->location,
            'subdirectory' => $this->subdirectory,
            'public_path' => $this->public_path,
            'php_version' => $this->php_version,
            'status' => $this->status,
            'last_error' => $this->last_error,
            'ssl_status' => $this->ssl_status,
            'ssl_expires_at' => $this->ssl_expires_at,
            'waf_enabled' => $this->waf_enabled,
        ];
    }
}

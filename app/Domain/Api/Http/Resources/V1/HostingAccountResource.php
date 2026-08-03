<?php

namespace App\Domain\Api\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\Hosting\Models\HostingAccount */
class HostingAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'primary_domain' => $this->primary_domain,
            'status' => $this->status,
            'php_version' => $this->php_version,
            'ssl_status' => $this->ssl_status,
            'disk_usage_mb' => $this->disk_usage_mb,
            'plan' => [
                'id' => $this->plan?->id,
                'name' => $this->plan?->name,
            ],
            'server' => [
                'id' => $this->server?->id,
                'name' => $this->server?->name,
            ],
            'client' => [
                'id' => $this->client?->id,
                'name' => $this->client?->name,
                'email' => $this->client?->email,
            ],
            'created_at' => $this->created_at,
        ];
    }
}

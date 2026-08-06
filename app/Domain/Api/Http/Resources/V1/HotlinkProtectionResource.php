<?php

namespace App\Domain\Api\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\Hosting\Models\HotlinkProtection */
class HotlinkProtectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'domain' => $this->domain,
            'enabled' => $this->enabled,
            'extensions' => $this->extensions,
            'allowed_referrers' => $this->allowed_referrers,
        ];
    }
}

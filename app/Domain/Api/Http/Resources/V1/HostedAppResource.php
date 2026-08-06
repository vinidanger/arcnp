<?php

namespace App\Domain\Api\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\Hosting\Models\HostedApp */
class HostedAppResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'domain' => $this->domain,
            'runtime' => $this->runtime,
            'entry_file' => $this->entry_file,
            'port' => $this->port,
            'enabled' => $this->enabled,
        ];
    }
}

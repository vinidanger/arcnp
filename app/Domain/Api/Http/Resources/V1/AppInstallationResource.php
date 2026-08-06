<?php

namespace App\Domain\Api\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\Hosting\Models\AppInstallation */
class AppInstallationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'domain' => $this->domain,
            'path' => $this->path,
            'catalog_slug' => $this->catalog_slug,
            'status' => $this->status,
            'admin_username' => $this->admin_username,
            'error' => $this->error,
            'installed_at' => $this->installed_at,
            'detected_version' => $this->detected_version,
            'latest_known_version' => $this->latest_known_version,
            'is_outdated' => $this->isOutdated(),
            'site_url' => $this->siteUrl(),
            'database' => $this->database ? [
                'id' => $this->database->id,
                'db_name' => $this->database->db_name,
            ] : null,
        ];
    }
}

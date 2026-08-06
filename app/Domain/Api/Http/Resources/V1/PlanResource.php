<?php

namespace App\Domain\Api\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\Hosting\Models\Plan */
class PlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'disk_quota_mb' => $this->disk_quota_mb,
            'bandwidth_quota_mb' => $this->bandwidth_quota_mb,
            'max_databases' => $this->max_databases,
            'max_addon_domains' => $this->max_addon_domains,
            'max_cron_jobs' => $this->max_cron_jobs,
            'max_email_accounts' => $this->max_email_accounts,
            'cpu_cores' => $this->cpu_cores,
            'max_processes' => $this->max_processes,
            'memory_limit_mb' => $this->memory_limit_mb,
            'io_weight' => $this->io_weight,
            'max_db_connections' => $this->max_db_connections,
            'max_db_queries_per_hour' => $this->max_db_queries_per_hour,
        ];
    }
}

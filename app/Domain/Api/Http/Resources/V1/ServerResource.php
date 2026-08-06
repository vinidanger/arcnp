<?php

namespace App\Domain\Api\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\Servers\Models\Server */
class ServerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'hostname' => $this->hostname,
            'ip_address' => $this->ip_address,
            'public_ip_address' => $this->public_ip_address,
            'ns_hosts' => $this->nsHosts(),
            'os' => $this->os,
            'cpu_cores' => $this->cpu_cores,
            'memory_mb' => $this->memory_mb,
            'disk_gb' => $this->disk_gb,
            'agent_status' => $this->agent_status,
            'last_heartbeat_at' => $this->last_heartbeat_at,
            'load_avg' => $this->load_avg,
            'disk_percent' => $this->disk_percent,
            'mem_percent' => $this->mem_percent,
        ];
    }
}

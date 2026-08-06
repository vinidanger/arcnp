<?php

namespace App\Domain\Api\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\Hosting\Models\CronJob */
class CronJobResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'minute' => $this->minute,
            'hour' => $this->hour,
            'day' => $this->day,
            'month' => $this->month,
            'weekday' => $this->weekday,
            'schedule' => $this->schedule(),
            'command' => $this->command,
        ];
    }
}

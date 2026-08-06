<?php

namespace App\Domain\Api\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\Support\Models\Ticket */
class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject' => $this->subject,
            'priority' => $this->priority,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'messages' => $this->messages->map(fn ($message) => [
                'id' => $message->id,
                'author' => [
                    'id' => $message->user?->id,
                    'name' => $message->user?->name,
                ],
                'body' => $message->body,
                'created_at' => $message->created_at,
            ]),
        ];
    }
}

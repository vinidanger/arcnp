<?php

namespace App\Domain\Api\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A senha da caixa (reversível no model, usada internamente pra SSO do
 * webmail/sincronização com o Dovecot) nunca aparece aqui — mesma
 * política do resto da API nova.
 *
 * @mixin \App\Domain\Hosting\Models\Mailbox
 */
class MailboxResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'local_part' => $this->local_part,
            'email' => $this->email(),
            'vacation' => $this->vacation ? [
                'enabled' => $this->vacation->enabled,
                'subject' => $this->vacation->subject,
                'message' => $this->vacation->message,
            ] : null,
            'filters' => $this->filters->map(fn ($filter) => [
                'id' => $filter->id,
                'enabled' => $filter->enabled,
                'field' => $filter->field,
                'value' => $filter->value,
                'action' => $filter->action,
                'folder' => $filter->folder,
            ]),
        ];
    }
}

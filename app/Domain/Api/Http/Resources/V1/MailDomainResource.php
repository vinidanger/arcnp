<?php

namespace App\Domain\Api\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Domain\Hosting\Models\MailDomain */
class MailDomainResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'domain' => $this->domain,
            'dkim_selector' => $this->dkimSelector(),
            'spf_record_value' => $this->spfRecordValue(),
            'dmarc_record_value' => $this->dmarcRecordValue(),
            'mailboxes' => MailboxResource::collection($this->mailboxes),
            'forwarders' => MailForwarderResource::collection($this->forwarders),
        ];
    }
}

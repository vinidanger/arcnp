<?php

namespace App\Domain\Api\Http\Controllers\V1;

use App\Domain\Api\Http\Resources\V1\MailDomainResource;
use App\Domain\Hosting\Models\HostingAccount;
use App\Http\Controllers\Controller;

class MailController extends Controller
{
    public function index(HostingAccount $hosting_account)
    {
        $mailDomains = $hosting_account->mailDomains()
            ->with(['mailboxes.vacation', 'mailboxes.filters', 'forwarders'])
            ->get();

        return MailDomainResource::collection($mailDomains);
    }
}

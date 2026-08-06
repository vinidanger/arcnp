<?php

namespace App\Domain\Api\Http\Controllers\V1;

use App\Domain\Api\Http\Resources\V1\DnsZoneResource;
use App\Domain\Hosting\Models\HostingAccount;
use App\Http\Controllers\Controller;

class DnsController extends Controller
{
    public function index(HostingAccount $hosting_account)
    {
        return DnsZoneResource::collection($hosting_account->dnsZones()->with('records')->get());
    }
}

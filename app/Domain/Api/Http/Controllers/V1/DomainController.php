<?php

namespace App\Domain\Api\Http\Controllers\V1;

use App\Domain\Api\Http\Resources\V1\DomainResource;
use App\Domain\Hosting\Models\HostingAccount;
use App\Http\Controllers\Controller;

class DomainController extends Controller
{
    public function index(HostingAccount $hosting_account)
    {
        return DomainResource::collection($hosting_account->domains);
    }
}

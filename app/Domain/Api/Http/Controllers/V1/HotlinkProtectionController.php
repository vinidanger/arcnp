<?php

namespace App\Domain\Api\Http\Controllers\V1;

use App\Domain\Api\Http\Resources\V1\HotlinkProtectionResource;
use App\Domain\Hosting\Models\HostingAccount;
use App\Http\Controllers\Controller;

class HotlinkProtectionController extends Controller
{
    public function index(HostingAccount $hosting_account)
    {
        return HotlinkProtectionResource::collection($hosting_account->hotlinkProtections);
    }
}

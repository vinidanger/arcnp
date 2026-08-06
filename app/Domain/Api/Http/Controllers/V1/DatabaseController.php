<?php

namespace App\Domain\Api\Http\Controllers\V1;

use App\Domain\Api\Http\Resources\V1\DatabaseResource;
use App\Domain\Hosting\Models\HostingAccount;
use App\Http\Controllers\Controller;

class DatabaseController extends Controller
{
    public function index(HostingAccount $hosting_account)
    {
        return DatabaseResource::collection($hosting_account->databases);
    }
}

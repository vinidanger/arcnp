<?php

namespace App\Domain\Api\Http\Controllers\V1;

use App\Domain\Api\Http\Resources\V1\CronJobResource;
use App\Domain\Hosting\Models\HostingAccount;
use App\Http\Controllers\Controller;

class CronJobController extends Controller
{
    public function index(HostingAccount $hosting_account)
    {
        return CronJobResource::collection($hosting_account->cronJobs);
    }
}

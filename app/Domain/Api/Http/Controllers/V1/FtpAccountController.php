<?php

namespace App\Domain\Api\Http\Controllers\V1;

use App\Domain\Api\Http\Resources\V1\FtpAccountResource;
use App\Domain\Hosting\Models\HostingAccount;
use App\Http\Controllers\Controller;

class FtpAccountController extends Controller
{
    public function index(HostingAccount $hosting_account)
    {
        return FtpAccountResource::collection($hosting_account->ftpAccounts);
    }
}

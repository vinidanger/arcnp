<?php

namespace App\Domain\Api\Http\Controllers\V1;

use App\Domain\Api\Http\Resources\V1\SshKeyResource;
use App\Domain\Hosting\Models\HostingAccount;
use App\Http\Controllers\Controller;

class SshKeyController extends Controller
{
    public function index(HostingAccount $hosting_account)
    {
        return SshKeyResource::collection($hosting_account->sshKeys);
    }
}

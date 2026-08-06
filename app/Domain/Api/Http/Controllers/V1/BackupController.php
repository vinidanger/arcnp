<?php

namespace App\Domain\Api\Http\Controllers\V1;

use App\Domain\Api\Http\Resources\V1\BackupResource;
use App\Domain\Hosting\Models\HostingAccount;
use App\Http\Controllers\Controller;

class BackupController extends Controller
{
    public function index(HostingAccount $hosting_account)
    {
        return BackupResource::collection($hosting_account->backups);
    }
}

<?php

namespace App\Domain\Api\Http\Controllers\V1;

use App\Domain\Api\Http\Resources\V1\FolderProtectionResource;
use App\Domain\Hosting\Models\HostingAccount;
use App\Http\Controllers\Controller;

class FolderProtectionController extends Controller
{
    public function index(HostingAccount $hosting_account)
    {
        return FolderProtectionResource::collection($hosting_account->folderProtections);
    }
}

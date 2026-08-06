<?php

namespace App\Domain\Api\Http\Controllers\V1;

use App\Domain\Api\Http\Resources\V1\MimeTypeRuleResource;
use App\Domain\Hosting\Models\HostingAccount;
use App\Http\Controllers\Controller;

class MimeTypeRuleController extends Controller
{
    public function index(HostingAccount $hosting_account)
    {
        return MimeTypeRuleResource::collection($hosting_account->mimeTypeRules);
    }
}

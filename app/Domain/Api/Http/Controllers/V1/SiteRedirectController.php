<?php

namespace App\Domain\Api\Http\Controllers\V1;

use App\Domain\Api\Http\Resources\V1\SiteRedirectResource;
use App\Domain\Hosting\Models\HostingAccount;
use App\Http\Controllers\Controller;

class SiteRedirectController extends Controller
{
    public function index(HostingAccount $hosting_account)
    {
        return SiteRedirectResource::collection($hosting_account->siteRedirects);
    }
}

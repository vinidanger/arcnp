<?php

namespace App\Domain\Api\Http\Controllers\V1;

use App\Domain\Api\Http\Resources\V1\AppInstallationResource;
use App\Domain\Api\Http\Resources\V1\HostedAppResource;
use App\Domain\Hosting\Models\HostingAccount;
use App\Http\Controllers\Controller;

class AppController extends Controller
{
    /**
     * Reúne os dois conceitos de "app" que existem hoje — instalador de
     * apps (WordPress/zip genérico) e apps Node/Python (modo proxy) —
     * numa resposta só, já que pra um painel externo os dois são
     * "aplicações rodando nessa conta".
     */
    public function index(HostingAccount $hosting_account)
    {
        return response()->json([
            'data' => [
                'app_installations' => AppInstallationResource::collection($hosting_account->appInstallations)->resolve(),
                'hosted_apps' => HostedAppResource::collection($hosting_account->hostedApps)->resolve(),
            ],
        ]);
    }
}

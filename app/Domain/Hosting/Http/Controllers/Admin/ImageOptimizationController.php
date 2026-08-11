<?php

namespace App\Domain\Hosting\Http\Controllers\Admin;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Services\ImageOptimizationService;
use App\Http\Controllers\Controller;
use Throwable;

class ImageOptimizationController extends Controller
{
    public function index(HostingAccount $hosting_account)
    {
        $this->authorize('view', $hosting_account);

        $hosting_account->load('imageOptimizations');

        return view('admin.hosting-accounts.image-optimization.index', ['account' => $hosting_account]);
    }

    public function store(HostingAccount $hosting_account, ImageOptimizationService $service)
    {
        $this->authorize('update', $hosting_account);

        try {
            $service->optimize($hosting_account);

            return back()->with('status', 'Otimização iniciada — atualize a página em alguns instantes pra ver o resultado.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao iniciar a otimização: '.$e->getMessage());
        }
    }
}

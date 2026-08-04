<?php

namespace App\Domain\Hosting\Http\Controllers\Admin;

use App\Domain\Hosting\Models\FolderProtection;
use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Services\FolderProtectionService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Throwable;

class FolderProtectionController extends Controller
{
    public function index(HostingAccount $hosting_account)
    {
        $this->authorize('view', $hosting_account);

        $hosting_account->load('folderProtections', 'domains');

        $domains = array_values(array_unique(array_merge(
            [$hosting_account->primary_domain],
            $hosting_account->domains->pluck('domain')->all()
        )));

        return view('admin.hosting-accounts.protected-folders.index', [
            'account' => $hosting_account,
            'domains' => $domains,
        ]);
    }

    public function store(Request $request, HostingAccount $hosting_account, FolderProtectionService $service)
    {
        $this->authorize('update', $hosting_account);

        $domains = array_merge([$hosting_account->primary_domain], $hosting_account->domains()->pluck('domain')->all());

        $data = $request->validate([
            'domain' => ['required', 'string', 'in:'.implode(',', $domains)],
            'path' => ['required', 'string', 'max:255', 'regex:/^\/[a-zA-Z0-9_\-\/]*$/'],
            'username' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_.\-]+$/'],
            'password' => ['required', 'string', 'min:8', 'max:100'],
        ]);

        try {
            $service->create($hosting_account, $data['domain'], $data['path'], $data['username'], $data['password']);

            return back()->with('status', 'Pasta protegida.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao proteger pasta: '.$e->getMessage());
        }
    }

    public function destroy(HostingAccount $hosting_account, FolderProtection $folder_protection, FolderProtectionService $service)
    {
        $this->authorize('update', $hosting_account);

        abort_unless($folder_protection->hosting_account_id === $hosting_account->id, 404);

        try {
            $service->delete($folder_protection);

            return back()->with('status', 'Proteção removida.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao remover proteção: '.$e->getMessage());
        }
    }
}

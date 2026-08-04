<?php

namespace App\Domain\Hosting\Http\Controllers\Client;

use App\Domain\Hosting\Models\FtpAccount;
use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Services\FtpAccountService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class FtpAccountController extends Controller
{
    public function index(HostingAccount $hosting_account)
    {
        $this->authorize('view', $hosting_account);

        $hosting_account->load('ftpAccounts', 'server');

        return view('client.hosting-accounts.ftp.index', ['account' => $hosting_account]);
    }

    public function store(Request $request, HostingAccount $hosting_account, FtpAccountService $ftp)
    {
        $this->authorize('update', $hosting_account);

        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:32', 'regex:/^[a-zA-Z0-9._@-]+$/'],
            'path' => ['nullable', 'string', 'max:255', 'regex:/^[a-zA-Z0-9_\-\/]*$/'],
            'password' => ['nullable', 'string', 'min:8', 'max:100'],
        ]);

        $password = $data['password'] ?: Str::password(16);

        try {
            $ftpAccount = $ftp->create($hosting_account, $data['username'], $data['path'] ?? '', $password);

            return back()->with('status', 'Conta de FTP criada: '.$ftpAccount->username)->with('plain_ftp_password', $password);
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao criar conta de FTP: '.$e->getMessage());
        }
    }

    public function updatePassword(Request $request, HostingAccount $hosting_account, FtpAccount $ftp_account, FtpAccountService $ftp)
    {
        $this->authorize('update', $hosting_account);

        abort_unless($ftp_account->hosting_account_id === $hosting_account->id, 404);

        $data = $request->validate(['password' => ['required', 'string', 'min:8', 'max:100', 'confirmed']]);

        try {
            $ftp->updatePassword($ftp_account, $data['password']);

            return back()->with('status', 'Senha atualizada.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao atualizar senha: '.$e->getMessage());
        }
    }

    public function destroy(HostingAccount $hosting_account, FtpAccount $ftp_account, FtpAccountService $ftp)
    {
        $this->authorize('update', $hosting_account);

        abort_unless($ftp_account->hosting_account_id === $hosting_account->id, 404);

        try {
            $ftp->delete($ftp_account);

            return back()->with('status', 'Conta de FTP removida.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao remover conta de FTP: '.$e->getMessage());
        }
    }
}

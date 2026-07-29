<?php

namespace App\Domain\Hosting\Http\Controllers\Client;

use App\Domain\Hosting\Models\HostingAccount;
use App\Domain\Hosting\Models\SshKey;
use App\Domain\Hosting\Services\SshAccessService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Throwable;

class SshAccessController extends Controller
{
    public function index(HostingAccount $hosting_account)
    {
        $this->authorize('view', $hosting_account);

        $hosting_account->load('sshKeys', 'server');

        return view('client.hosting-accounts.ssh.index', ['account' => $hosting_account]);
    }

    public function toggle(Request $request, HostingAccount $hosting_account, SshAccessService $ssh)
    {
        $this->authorize('update', $hosting_account);

        $data = $request->validate(['enabled' => ['required', 'boolean']]);

        try {
            $password = $ssh->setEnabled($hosting_account, $data['enabled']);

            $redirect = back()->with('status', $data['enabled'] ? 'Acesso SSH liberado.' : 'Acesso SSH revogado.');

            return $password ? $redirect->with('plain_ssh_password', $password) : $redirect;
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao alterar acesso SSH: '.$e->getMessage());
        }
    }

    public function regeneratePassword(HostingAccount $hosting_account, SshAccessService $ssh)
    {
        $this->authorize('update', $hosting_account);

        try {
            $password = $ssh->regeneratePassword($hosting_account);

            return back()->with('status', 'Senha SSH gerada.')->with('plain_ssh_password', $password);
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao gerar senha SSH: '.$e->getMessage());
        }
    }

    public function updatePassword(Request $request, HostingAccount $hosting_account, SshAccessService $ssh)
    {
        $this->authorize('update', $hosting_account);

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
        ]);

        try {
            $ssh->setPassword($hosting_account, $data['password']);

            return back()->with('status', 'Senha SSH definida.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao definir senha SSH: '.$e->getMessage());
        }
    }

    public function storeKey(Request $request, HostingAccount $hosting_account, SshAccessService $ssh)
    {
        $this->authorize('update', $hosting_account);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'public_key' => ['required', 'string', 'max:8192'],
        ]);

        try {
            $ssh->addKey($hosting_account, $data['name'], $data['public_key']);

            return back()->with('status', 'Chave adicionada.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao adicionar chave: '.$e->getMessage());
        }
    }

    public function destroyKey(HostingAccount $hosting_account, SshKey $ssh_key, SshAccessService $ssh)
    {
        $this->authorize('update', $hosting_account);

        abort_unless($ssh_key->hosting_account_id === $hosting_account->id, 404);

        try {
            $ssh->removeKey($ssh_key);

            return back()->with('status', 'Chave removida.');
        } catch (Throwable $e) {
            return back()->with('error', 'Falha ao remover chave: '.$e->getMessage());
        }
    }
}

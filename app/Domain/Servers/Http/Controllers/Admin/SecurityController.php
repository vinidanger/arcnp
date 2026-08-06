<?php

namespace App\Domain\Servers\Http\Controllers\Admin;

use App\Domain\Servers\Models\Server;
use App\Domain\Servers\Services\AgentHttpClient;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * IPs banidos pelo fail2ban — dado sempre ao vivo, nunca persistido
 * (mesma filosofia de "resources.usage"). Página própria em vez de
 * cartão dentro de admin/servers/show.blade.php, mesmo raciocínio do
 * PhpExtensionController: mantém a página principal do servidor leve,
 * dado sob demanda fica na própria tela.
 */
class SecurityController extends Controller
{
    public function index(Server $server, AgentHttpClient $client)
    {
        $this->authorize('view', $server);

        $banned = [];
        $error = null;

        $job = $client->dispatch($server, 'security.list_banned_ips', []);

        if ($job->status === 'completed') {
            $banned = $job->result['banned'] ?? [];
        } else {
            $error = $job->error ?? 'Falha ao consultar IPs banidos.';
        }

        return view('admin.servers.security', [
            'server' => $server,
            'banned' => $banned,
            'fetchError' => $error,
        ]);
    }

    public function unban(Request $request, Server $server, AgentHttpClient $client)
    {
        $this->authorize('update', $server);

        $data = $request->validate([
            'jail' => ['required', Rule::in(['sshd', 'vsftpd'])],
            'ip' => ['required', 'ip'],
        ]);

        $job = $client->dispatch($server, 'security.unban_ip', $data);

        if ($job->status !== 'completed') {
            return back()->with('error', 'Falha ao desbanir IP: '.($job->error ?? 'erro desconhecido'));
        }

        return back()->with('status', 'IP desbanido.');
    }
}

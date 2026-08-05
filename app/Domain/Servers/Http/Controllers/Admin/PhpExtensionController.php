<?php

namespace App\Domain\Servers\Http\Controllers\Admin;

use App\Domain\Servers\Models\Server;
use App\Domain\Servers\Services\AgentHttpClient;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Por servidor + versão de PHP (não por conta) — extensão é instalada
 * a nível de sistema, compartilhada por todas as contas naquela
 * versão. Admin-only: diferente de "funções desabilitadas" (por conta,
 * também admin+cliente), aqui uma mudança afeta todo mundo do servidor
 * de uma vez, então fica só na área do servidor.
 */
class PhpExtensionController extends Controller
{
    public function index(Request $request, Server $server, AgentHttpClient $client)
    {
        $this->authorize('view', $server);

        $data = $request->validate([
            'version' => ['nullable', Rule::in(config('hosting.php_versions'))],
        ]);

        $version = $data['version'] ?? '8.3';

        $extensions = [];
        $error = null;

        $job = $client->dispatch($server, 'php.list_extensions', ['php_version' => $version]);

        if ($job->status === 'completed') {
            $extensions = $job->result['extensions'] ?? [];
        } else {
            $error = $job->error ?? 'Falha ao consultar extensões.';
        }

        return view('admin.servers.php-extensions', [
            'server' => $server,
            'version' => $version,
            'extensions' => $extensions,
            'fetchError' => $error,
        ]);
    }

    public function toggle(Request $request, Server $server, AgentHttpClient $client)
    {
        $this->authorize('update', $server);

        $data = $request->validate([
            'version' => ['required', Rule::in(config('hosting.php_versions'))],
            'filename' => ['required', 'string', 'regex:/^[a-zA-Z0-9_.-]+\.ini$/'],
            'enable' => ['required', 'boolean'],
        ]);

        $job = $client->dispatch($server, 'php.toggle_extension', [
            'php_version' => $data['version'],
            'filename' => $data['filename'],
            'enable' => $data['enable'],
        ]);

        if ($job->status !== 'completed') {
            return back()->with('error', 'Falha ao alterar extensão: '.($job->error ?? 'erro desconhecido'));
        }

        return back()->with('status', 'Extensão atualizada.');
    }
}

<?php

namespace App\Domain\Servers\Http\Controllers\Admin;

use App\Domain\Servers\Http\Requests\StoreServerRequest;
use App\Domain\Servers\Http\Requests\UpdateServerRequest;
use App\Domain\Servers\Models\Server;
use App\Domain\Servers\Services\AgentHttpClient;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServerController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Server::class);

        $servers = Server::when($request->filled('status'), fn ($q) => $q->where('agent_status', $request->string('status')))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.servers.index', compact('servers'));
    }

    public function create()
    {
        $this->authorize('create', Server::class);

        return view('admin.servers.create');
    }

    public function store(StoreServerRequest $request)
    {
        $server = Server::create($request->validated());

        $secret = Str::random(48);

        $server->agentCredentials()->create([
            'shared_secret' => $secret,
        ]);

        return redirect()
            ->route('admin.servers.show', $server)
            ->with('plain_secret', $secret)
            ->with('status', 'Servidor cadastrado. Copie as credenciais abaixo para o .env do Agent — elas só aparecem nesta tela agora.');
    }

    public function show(Server $server)
    {
        $this->authorize('view', $server);

        $server->load('currentCredential');
        $recentJobs = $server->agentJobs()->latest()->limit(10)->get();

        $metricSnapshots = $server->metricSnapshots()
            ->where('recorded_at', '>=', now()->subDay())
            ->orderBy('recorded_at')
            ->get(['load_avg', 'disk_percent', 'mem_percent', 'recorded_at']);

        return view('admin.servers.show', compact('server', 'recentJobs', 'metricSnapshots'));
    }

    public function edit(Server $server)
    {
        $this->authorize('update', $server);

        return view('admin.servers.edit', compact('server'));
    }

    public function update(UpdateServerRequest $request, Server $server)
    {
        $server->update($request->validated());

        return redirect()->route('admin.servers.show', $server)->with('status', 'Servidor atualizado.');
    }

    public function destroy(Server $server)
    {
        $this->authorize('delete', $server);

        $server->delete();

        return redirect()->route('admin.servers.index')->with('status', 'Servidor removido.');
    }

    public function regenerateCredential(Server $server)
    {
        $this->authorize('update', $server);

        $server->agentCredentials()->whereNull('revoked_at')->update(['revoked_at' => now()]);

        $secret = Str::random(48);

        $server->agentCredentials()->create([
            'shared_secret' => $secret,
        ]);

        return redirect()
            ->route('admin.servers.show', $server)
            ->with('plain_secret', $secret)
            ->with('status', 'Credencial rotacionada. A credencial antiga foi revogada — atualize o .env do Agent com o novo segredo.');
    }

    public function testConnection(Server $server, AgentHttpClient $client)
    {
        $this->authorize('update', $server);

        $job = $client->dispatch($server, 'demo.health_check');

        if ($job->status === 'completed') {
            $server->update(['agent_status' => 'online', 'last_heartbeat_at' => now()]);

            return back()->with('status', 'Conexão OK: '.json_encode($job->result));
        }

        return back()->with('error', 'Falha ao conectar no Agent: '.($job->error ?? 'erro desconhecido'));
    }

    /**
     * Síncrono, sob demanda — diferente do heartbeat (load/disco/RAM,
     * a cada 60s via timer), isso é hardware/SO/status de serviço, que
     * quase não muda; não vale a pena agendar, só coleta quando o
     * admin pede. Também atualiza os campos estáticos os/cpu_cores/
     * memory_mb, que antes só eram preenchidos manualmente no form.
     */
    public function collectInfo(Server $server, AgentHttpClient $client)
    {
        $this->authorize('update', $server);

        $job = $client->dispatch($server, 'server.collect_info', [
            'mysql_service_name' => $server->mysql_service_name,
        ]);

        if ($job->status !== 'completed') {
            return back()->with('error', 'Falha ao coletar informações: '.($job->error ?? 'erro desconhecido'));
        }

        $system = $job->result['system'] ?? [];

        $server->update([
            'os' => $system['os'] ?? $server->os,
            'cpu_cores' => $system['cpu_cores'] ?? $server->cpu_cores,
            'memory_mb' => $system['memory_mb'] ?? $server->memory_mb,
            'server_info' => $job->result,
            'server_info_collected_at' => now(),
        ]);

        return back()->with('status', 'Informações do servidor atualizadas.');
    }
}

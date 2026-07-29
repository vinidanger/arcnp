<x-admin-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">{{ $server->name }}</h1>
            <div class="d-flex gap-2">
                <form method="POST" action="{{ route('admin.servers.test-connection', $server) }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('Testar conexão') }}</button>
                </form>
                <a href="{{ route('admin.servers.edit', $server) }}" class="btn btn-sm btn-outline-secondary">{{ __('Editar') }}</a>
            </div>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if (session('plain_secret'))
        <div class="alert alert-warning">
            <strong>{{ __('Credenciais de pareamento — copie agora, não aparecem de novo.') }}</strong>
            <pre class="mb-0 mt-2 bg-white p-2 rounded border small">AGENT_SERVER_ID={{ $server->currentCredential->agent_uuid }}
AGENT_SHARED_SECRET={{ session('plain_secret') }}
AGENT_PANEL_BASE_URL={{ url('/') }}</pre>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6">{{ __('Detalhes') }}</h2>
                    <dl class="row mb-0 small">
                        <dt class="col-5">{{ __('IP') }}</dt>
                        <dd class="col-7">{{ $server->ip_address }}</dd>
                        <dt class="col-5">{{ __('Hostname') }}</dt>
                        <dd class="col-7">{{ $server->hostname ?? '—' }}</dd>
                        <dt class="col-5">{{ __('Porta do Agent') }}</dt>
                        <dd class="col-7">{{ $server->agent_port }} ({{ $server->use_tls ? 'TLS' : 'sem TLS' }})</dd>
                        <dt class="col-5">{{ __('SO') }}</dt>
                        <dd class="col-7">{{ $server->os ?? '—' }}</dd>
                        <dt class="col-5">{{ __('Recursos') }}</dt>
                        <dd class="col-7">{{ $server->cpu_cores ?? '—' }} vCPU / {{ $server->memory_mb ?? '—' }} MB / {{ $server->disk_gb ?? '—' }} GB</dd>
                        <dt class="col-5">{{ __('Status') }}</dt>
                        <dd class="col-7">
                            @php
                                $badge = match ($server->agent_status) {
                                    'online' => 'success',
                                    'offline' => 'danger',
                                    default => 'secondary',
                                };
                            @endphp
                            <span class="badge text-bg-{{ $badge }}">{{ $server->agent_status }}</span>
                        </dd>
                        <dt class="col-5">{{ __('Último heartbeat') }}</dt>
                        <dd class="col-7">{{ $server->last_heartbeat_at?->diffForHumans() ?? '—' }}</dd>
                        <dt class="col-5">{{ __('Load / Disco / RAM') }}</dt>
                        <dd class="col-7">
                            {{ $server->load_avg ?? '—' }} /
                            {{ $server->disk_percent !== null ? $server->disk_percent.'%' : '—' }} /
                            {{ $server->mem_percent !== null ? $server->mem_percent.'%' : '—' }}
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6">{{ __('Pareamento') }}</h2>
                    @if ($server->currentCredential)
                        <p class="small mb-2">
                            {{ __('AGENT_SERVER_ID') }}:
                            <code>{{ $server->currentCredential->agent_uuid }}</code>
                        </p>
                        <p class="small text-secondary">
                            {{ __('O segredo compartilhado só é exibido uma vez, na criação ou rotação da credencial.') }}
                        </p>
                    @else
                        <p class="small text-danger">{{ __('Sem credencial ativa.') }}</p>
                    @endif

                    <form method="POST" action="{{ route('admin.servers.regenerate-credential', $server) }}"
                          onsubmit="return confirm('{{ __('Isso revoga a credencial atual. O Agent precisará ser reconfigurado. Continuar?') }}')">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-warning">{{ __('Rotacionar credencial') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <h2 class="h6">{{ __('Últimos comandos enviados') }}</h2>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>{{ __('Ação') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Despachado em') }}</th>
                            <th>{{ __('Concluído em') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentJobs as $job)
                            <tr>
                                <td>{{ $job->action }}</td>
                                <td>
                                    @php
                                        $jobBadge = match ($job->status) {
                                            'completed' => 'success',
                                            'failed' => 'danger',
                                            'running', 'sent' => 'info',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge text-bg-{{ $jobBadge }}">{{ $job->status }}</span>
                                </td>
                                <td>{{ $job->dispatched_at?->diffForHumans() ?? '—' }}</td>
                                <td>{{ $job->completed_at?->diffForHumans() ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-secondary py-3">{{ __('Nenhum comando enviado ainda.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-admin-layout>

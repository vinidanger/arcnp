<x-admin-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">{{ $server->name }}</h1>
            <div class="d-flex gap-2">
                <form method="POST" action="{{ route('admin.servers.test-connection', $server) }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('Testar conexão') }}</button>
                </form>
                <form method="POST" action="{{ route('admin.servers.collect-info', $server) }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('Coletar agora') }}</button>
                </form>
                <a href="{{ route('admin.servers.php-extensions.index', $server) }}" class="btn btn-sm btn-outline-secondary">{{ __('Extensões PHP') }}</a>
                <a href="{{ route('admin.servers.security.index', $server) }}" class="btn btn-sm btn-outline-secondary">{{ __('Segurança') }}</a>
                <a href="{{ route('admin.servers.edit', $server) }}" class="btn btn-sm btn-outline-secondary">{{ __('Editar') }}</a>
                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#confirm-server-deletion">
                    {{ __('Excluir') }}
                </button>
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
            <pre class="mb-0 mt-2 bg-body-secondary p-2 rounded border small">AGENT_SERVER_ID={{ $server->currentCredential->agent_uuid }}
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
                        <dt class="col-5">{{ __('IP público') }}</dt>
                        <dd class="col-7">{{ $server->public_ip_address ?? '—' }}</dd>
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
                            <span class="badge text-bg-{{ $badge }}">{{ status_label($server->agent_status) }}</span>
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

    @php $info = $server->server_info ?? []; @endphp

    <div class="row g-3 mt-3">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <h2 class="h6">{{ __('Hardware / SO') }}</h2>
                        @if ($server->server_info_collected_at)
                            <span class="small text-secondary">{{ __('Coletado') }} {{ $server->server_info_collected_at->diffForHumans() }}</span>
                        @endif
                    </div>

                    @if (empty($info))
                        <p class="small text-secondary mb-0">{{ __('Nada coletado ainda — clique em "Coletar agora" no topo da página.') }}</p>
                    @else
                        @php $system = $info['system'] ?? []; @endphp
                        <dl class="row mb-0 small">
                            <dt class="col-5">{{ __('SO') }}</dt>
                            <dd class="col-7">{{ $system['os'] ?? '—' }}</dd>
                            <dt class="col-5">{{ __('Kernel') }}</dt>
                            <dd class="col-7">{{ $system['kernel'] ?? '—' }}</dd>
                            <dt class="col-5">{{ __('Arquitetura') }}</dt>
                            <dd class="col-7">{{ $system['architecture'] ?? '—' }}</dd>
                            <dt class="col-5">{{ __('CPU') }}</dt>
                            <dd class="col-7">{{ $system['cpu_model'] ?? '—' }} ({{ $system['cpu_cores'] ?? '—' }} {{ __('núcleos') }})</dd>
                            <dt class="col-5">{{ __('RAM total') }}</dt>
                            <dd class="col-7">{{ isset($system['memory_mb']) ? number_format($system['memory_mb'] / 1024, 1) . ' GB' : '—' }}</dd>
                            <dt class="col-5">{{ __('Uptime') }}</dt>
                            <dd class="col-7">
                                @if (isset($system['uptime_seconds']))
                                    {{ (int) floor($system['uptime_seconds'] / 86400) }} {{ __('dias') }}
                                @else
                                    —
                                @endif
                            </dd>
                            <dt class="col-5">{{ __('IPs') }}</dt>
                            <dd class="col-7">{{ ! empty($system['ip_addresses']) ? implode(', ', $system['ip_addresses']) : '—' }}</dd>
                        </dl>

                        @if (! empty($system['disks']))
                            <div class="table-responsive mt-2">
                                <table class="table table-sm mb-0 small">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Montagem') }}</th>
                                            <th>{{ __('Tamanho') }}</th>
                                            <th>{{ __('Usado') }}</th>
                                            <th>{{ __('Livre') }}</th>
                                            <th>{{ __('%') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($system['disks'] as $disk)
                                            <tr>
                                                <td>{{ $disk['mount'] }}</td>
                                                <td>{{ $disk['size'] }}</td>
                                                <td>{{ $disk['used'] }}</td>
                                                <td>{{ $disk['available'] }}</td>
                                                <td>{{ $disk['percent'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6 mb-3">{{ __('Serviços') }}</h2>

                    @if (empty($info['services'] ?? []))
                        <p class="small text-secondary mb-0">{{ __('Nada coletado ainda — clique em "Coletar agora" no topo da página.') }}</p>
                    @else
                        <div class="row g-2">
                            @foreach ($info['services'] as $service => $status)
                                @php
                                    $serviceBadge = match ($status) {
                                        'active' => 'success',
                                        'inactive' => 'secondary',
                                        default => 'danger',
                                    };
                                    $version = $info['service_versions'][$service] ?? null;
                                @endphp
                                <div class="col-6">
                                    <div class="d-flex justify-content-between align-items-center border rounded-3 p-2 small">
                                        <div>
                                            <code>{{ $service }}</code>
                                            <div class="text-secondary">{{ $version ?? '—' }}</div>
                                        </div>
                                        <span class="badge text-bg-{{ $serviceBadge }}">{{ $status }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <h2 class="h6">{{ __('Uso ao longo do tempo (últimas 24h)') }}</h2>

            @if ($metricSnapshots->isEmpty())
                <p class="small text-secondary mb-0">{{ __('Ainda não há snapshots suficientes — volte daqui a algumas horas.') }}</p>
            @else
                <div class="row g-3">
                    <div class="col-md-6">
                        <canvas id="server-metrics-percent-chart" data-snapshots="{{ $metricSnapshots->toJson() }}" height="200"></canvas>
                    </div>
                    <div class="col-md-6">
                        <canvas id="server-metrics-load-chart" data-snapshots="{{ $metricSnapshots->toJson() }}" height="200"></canvas>
                    </div>
                </div>
                @vite('resources/js/server-metrics.js')
            @endif
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
                                    <span class="badge text-bg-{{ $jobBadge }}">{{ status_label($job->status) }}</span>
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

    <x-modal name="confirm-server-deletion" :show="$errors->serverDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('admin.servers.destroy', $server) }}" class="p-3">
            @csrf
            @method('delete')

            <h2 class="h5">
                {{ __('Tem certeza que deseja excluir este servidor?') }}
            </h2>

            <p class="small text-secondary">
                {{ __('Isso remove o servidor :name (:ip) e revoga as credenciais de pareamento. Só é possível excluir se não houver nenhuma hospedagem ativa nele. Digite sua senha para confirmar.', ['name' => $server->name, 'ip' => $server->ip_address]) }}
            </p>

            <div class="mt-3">
                <x-input-label for="server-deletion-password" value="{{ __('Senha') }}" class="visually-hidden" />
                <x-text-input
                    id="server-deletion-password"
                    name="password"
                    type="password"
                    placeholder="{{ __('Senha') }}"
                    class="w-full"
                />
                <x-input-error :messages="$errors->serverDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-3 d-flex justify-content-end gap-2">
                <x-secondary-button type="button" data-bs-dismiss="modal">
                    {{ __('Cancelar') }}
                </x-secondary-button>

                <x-danger-button>
                    {{ __('Excluir servidor') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</x-admin-layout>

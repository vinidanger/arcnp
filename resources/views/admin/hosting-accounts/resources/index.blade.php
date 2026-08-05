<x-admin-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Recursos') }} — {{ $account->primary_domain }}</h1>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @unless ($account->status === 'active')
        <div class="alert alert-warning mb-3">{{ __('Disponível quando a conta estiver ativa.') }}</div>
    @endunless

    @php $plan = $account->plan; @endphp

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6">{{ __('Limites do plano') }}</h2>
                    <p class="small text-secondary">{{ __('Aplicados no cgroup da conta (user-{uid}.slice) e na cota de disco do filesystem.') }}</p>
                    <dl class="row mb-0 small">
                        <dt class="col-6">{{ __('CPU') }}</dt>
                        <dd class="col-6">{{ $plan->cpu_cores ? $plan->cpu_cores.' '.__('núcleo(s)') : __('sem limite') }}</dd>
                        <dt class="col-6">{{ __('RAM') }}</dt>
                        <dd class="col-6">{{ $plan->memory_limit_mb ? $plan->memory_limit_mb.' MB' : __('sem limite') }}</dd>
                        <dt class="col-6">{{ __('Processos simultâneos') }}</dt>
                        <dd class="col-6">{{ $plan->max_processes ?? __('sem limite') }}</dd>
                        <dt class="col-6">{{ __('Peso de I/O') }}</dt>
                        <dd class="col-6">{{ $plan->io_weight ?? 100 }}</dd>
                        <dt class="col-6">{{ __('Disco') }}</dt>
                        <dd class="col-6">{{ $plan->disk_quota_mb ? $plan->disk_quota_mb.' MB' : __('sem limite') }}</dd>
                    </dl>

                    @if ($account->status === 'active')
                        <form method="POST" action="{{ route('admin.hosting-accounts.resources.reapply', $account) }}" class="mt-3">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('Reaplicar limites') }}</button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6">{{ __('Uso atual') }}</h2>

                    @if ($usageError)
                        <p class="small text-danger mb-0">{{ $usageError }}</p>
                    @elseif (! $usage)
                        <p class="small text-secondary mb-0">{{ __('Disponível quando a conta estiver ativa.') }}</p>
                    @else
                        <dl class="row mb-0 small">
                            <dt class="col-6">{{ __('CPU consumida (acumulado)') }}</dt>
                            <dd class="col-6">
                                {{ isset($usage['cpu_usage_ns']) ? number_format($usage['cpu_usage_ns'] / 1e9, 1).'s' : '—' }}
                            </dd>
                            <dt class="col-6">{{ __('RAM em uso agora') }}</dt>
                            <dd class="col-6">
                                {{ isset($usage['memory_current_bytes']) ? number_format($usage['memory_current_bytes'] / 1048576, 1).' MB' : '—' }}
                            </dd>
                            <dt class="col-6">{{ __('Processos ativos agora') }}</dt>
                            <dd class="col-6">{{ $usage['tasks_current'] ?? '—' }}</dd>
                        </dl>
                        <p class="small text-secondary mt-2 mb-0">{{ __('CPU é acumulada desde que o limite foi aplicado pela primeira vez, não é uma taxa instantânea.') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>

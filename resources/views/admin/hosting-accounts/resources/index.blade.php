<x-admin-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">{{ __('Recursos') }} — {{ $account->primary_domain }}</h1>
            @if ($account->status === 'active')
                <form method="POST" action="{{ route('admin.hosting-accounts.resources.reapply', $account) }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-repeat me-1"></i>{{ __('Reaplicar limites') }}</button>
                </form>
            @endif
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @unless ($account->status === 'active')
        <div class="alert alert-warning mb-3">{{ __('Disponível quando a conta estiver ativa.') }}</div>
    @else
        @php
            $plan = $account->plan;
            $severityOf = fn (?int $percent) => $percent === null ? 'secondary' : ($percent >= 90 ? 'danger' : ($percent >= 70 ? 'warning' : 'success'));

            $memPercent = ($plan->memory_limit_mb && isset($usage['memory_current_bytes']))
                ? min(100, (int) round(($usage['memory_current_bytes'] / 1048576) / $plan->memory_limit_mb * 100))
                : null;

            $tasksPercent = ($plan->max_processes && isset($usage['tasks_current']))
                ? min(100, (int) round($usage['tasks_current'] / $plan->max_processes * 100))
                : null;

            $diskQuota = $plan->disk_quota_mb;
            $diskUsed = $account->disk_usage_mb;
            $diskPercent = ($diskQuota && $diskUsed !== null) ? min(100, (int) round($diskUsed / $diskQuota * 100)) : null;
        @endphp

        <div class="mb-2 small text-uppercase text-secondary fw-semibold" style="letter-spacing: .04em;">{{ __('Limites do plano') }} — {{ $plan->name }}</div>
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="stat-tile h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-tile-icon bg-primary-subtle text-primary"><i class="bi bi-cpu"></i></div>
                        <div>
                            <div class="stat-tile-value">{{ $plan->cpu_cores ?? '∞' }}<span class="fs-6 text-body-tertiary">{{ $plan->cpu_cores ? ' '.__('núcleo(s)') : '' }}</span></div>
                            <div class="small text-secondary">{{ __('CPU') }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-tile h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-tile-icon bg-info-subtle text-info"><i class="bi bi-memory"></i></div>
                        <div>
                            <div class="stat-tile-value">{{ $plan->memory_limit_mb ?? '∞' }}<span class="fs-6 text-body-tertiary">{{ $plan->memory_limit_mb ? ' MB' : '' }}</span></div>
                            <div class="small text-secondary">{{ __('RAM') }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-tile h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-tile-icon bg-warning-subtle text-warning"><i class="bi bi-list-task"></i></div>
                        <div>
                            <div class="stat-tile-value">{{ $plan->max_processes ?? '∞' }}</div>
                            <div class="small text-secondary">{{ __('Processos') }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="stat-tile h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-tile-icon bg-secondary-subtle text-secondary"><i class="bi bi-hdd-network"></i></div>
                        <div>
                            <div class="stat-tile-value">{{ $plan->io_weight ?? 100 }}</div>
                            <div class="small text-secondary">{{ __('Peso de I/O') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-2 small text-uppercase text-secondary fw-semibold" style="letter-spacing: .04em;">{{ __('Uso atual') }}</div>

        @if ($usageError)
            <div class="alert alert-danger">{{ $usageError }}</div>
        @elseif (! $usage)
            <p class="small text-secondary">{{ __('Sem dados de uso no momento.') }}</p>
        @else
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="stat-tile h-100">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-tile-icon bg-primary-subtle text-primary"><i class="bi bi-cpu"></i></div>
                            <div>
                                <div class="stat-tile-value">{{ isset($usage['cpu_usage_ns']) ? number_format($usage['cpu_usage_ns'] / 1e9, 1) : '—' }}<span class="fs-6 text-body-tertiary">s</span></div>
                                <div class="small text-secondary">{{ __('CPU acumulada') }}</div>
                            </div>
                        </div>
                        <p class="small text-secondary mt-2 mb-0">{{ __('Desde que o limite foi aplicado pela primeira vez — não é uma taxa instantânea.') }}</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-tile h-100">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <div class="stat-tile-icon bg-{{ $severityOf($memPercent) }}-subtle text-{{ $severityOf($memPercent) }}"><i class="bi bi-memory"></i></div>
                            <div>
                                <div class="stat-tile-value">{{ isset($usage['memory_current_bytes']) ? number_format($usage['memory_current_bytes'] / 1048576, 1) : '—' }}<span class="fs-6 text-body-tertiary"> MB</span></div>
                                <div class="small text-secondary">{{ __('RAM em uso') }}{{ $plan->memory_limit_mb ? ' / '.$plan->memory_limit_mb.' MB' : '' }}</div>
                            </div>
                        </div>
                        @if ($memPercent !== null)
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-{{ $severityOf($memPercent) }}" style="width: {{ $memPercent }}%"></div>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-tile h-100">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <div class="stat-tile-icon bg-{{ $severityOf($tasksPercent) }}-subtle text-{{ $severityOf($tasksPercent) }}"><i class="bi bi-list-task"></i></div>
                            <div>
                                <div class="stat-tile-value">{{ $usage['tasks_current'] ?? '—' }}<span class="fs-6 text-body-tertiary">{{ $plan->max_processes ? ' / '.$plan->max_processes : '' }}</span></div>
                                <div class="small text-secondary">{{ __('Processos ativos') }}</div>
                            </div>
                        </div>
                        @if ($tasksPercent !== null)
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-{{ $severityOf($tasksPercent) }}" style="width: {{ $tasksPercent }}%"></div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        @if ($diskQuota)
            <div class="mb-2 mt-4 small text-uppercase text-secondary fw-semibold" style="letter-spacing: .04em;">{{ __('Disco') }}</div>
            <div class="stat-tile" style="max-width: 24rem;">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <div class="stat-tile-icon bg-{{ $severityOf($diskPercent) }}-subtle text-{{ $severityOf($diskPercent) }}"><i class="bi bi-hdd"></i></div>
                    <div>
                        <div class="stat-tile-value">{{ $diskUsed ?? '—' }}<span class="fs-6 text-body-tertiary"> / {{ $diskQuota }} MB</span></div>
                        <div class="small text-secondary">
                            {{ __('Uso de disco') }}
                            @if ($account->disk_usage_checked_at)
                                — {{ __('atualizado') }} {{ $account->disk_usage_checked_at->diffForHumans() }}
                            @endif
                        </div>
                    </div>
                </div>
                @if ($diskPercent !== null)
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-{{ $severityOf($diskPercent) }}" style="width: {{ $diskPercent }}%"></div>
                    </div>
                @endif
            </div>
        @endif
    @endunless
</x-admin-layout>

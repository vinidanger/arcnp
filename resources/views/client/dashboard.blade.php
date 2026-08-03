<x-client-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Dashboard') }}</h1>
    </x-slot>

    <p class="text-secondary mb-4">{{ __('Bem-vindo, :name.', ['name' => auth()->user()->name]) }}</p>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="stat-tile d-flex align-items-center gap-3">
                <div class="stat-tile-icon bg-primary-subtle text-primary">
                    <i class="bi bi-hdd-stack"></i>
                </div>
                <div>
                    <div class="stat-tile-value">{{ $stats['accounts_total'] }}</div>
                    <div class="small text-secondary">{{ __('Hospedagens') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-tile d-flex align-items-center gap-3">
                <div class="stat-tile-icon bg-success-subtle text-success">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div>
                    <div class="stat-tile-value">{{ $stats['accounts_active'] }}</div>
                    <div class="small text-secondary">{{ __('Ativas') }}</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-tile d-flex align-items-center gap-3">
                <div class="stat-tile-icon bg-warning-subtle text-warning">
                    <i class="bi bi-device-hdd"></i>
                </div>
                <div>
                    <div class="stat-tile-value">{{ number_format($stats['disk_usage_mb'] / 1024, 1) }} <span class="fs-6">GB</span></div>
                    <div class="small text-secondary">
                        {{ __('Disco usado') }}
                        @if ($stats['disk_quota_mb'] > 0)
                            <span class="text-body-tertiary">/ {{ number_format($stats['disk_quota_mb'] / 1024, 1) }} GB</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 mb-0">{{ __('Minhas hospedagens') }}</h2>
                        <a href="{{ route('client.hosting-accounts.index') }}" class="btn btn-sm btn-outline-primary">{{ __('Ver todas') }}</a>
                    </div>

                    @forelse ($accounts as $account)
                        <div class="d-flex justify-content-between align-items-center py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                            <div>
                                <a href="{{ route('client.hosting-accounts.show', $account) }}">{{ $account->primary_domain }}</a>
                                <div class="small text-secondary">{{ $account->plan?->name }}</div>
                            </div>
                            @php
                                $badge = match ($account->status) {
                                    'active' => 'success',
                                    'suspended' => 'warning',
                                    'error' => 'danger',
                                    default => 'secondary',
                                };
                            @endphp
                            <span class="badge text-bg-{{ $badge }}">{{ $account->status }}</span>
                        </div>
                    @empty
                        <p class="small text-secondary mb-0">{{ __('Você ainda não tem nenhuma hospedagem.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6 mb-3">{{ __('Precisa de atenção') }}</h2>

                    @forelse ($attentionAccounts as $account)
                        <div class="d-flex justify-content-between align-items-center py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                            <div>
                                <a href="{{ route('client.hosting-accounts.show', $account) }}">{{ $account->primary_domain }}</a>
                                <div class="small text-secondary">{{ $account->plan?->name }}</div>
                            </div>
                            <div class="d-flex gap-1">
                                @if ($account->status !== 'active')
                                    <span class="badge text-bg-{{ $account->status === 'suspended' ? 'warning' : 'danger' }}">{{ $account->status }}</span>
                                @endif
                                @if ($account->ssl_status === 'failed')
                                    <span class="badge text-bg-danger">SSL</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="small text-secondary mb-0">{{ __('Nada precisando de atenção agora — tudo tranquilo.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    @php $primary = $accounts->firstWhere('status', 'active') ?? $accounts->first(); @endphp

    @if ($primary)
        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h6">{{ __('Plano de hospedagem') }} — {{ $primary->primary_domain }}</h2>
                <dl class="row mb-0 small">
                    <dt class="col-sm-3">{{ __('Plano') }}</dt>
                    <dd class="col-sm-9">{{ $primary->plan->name }}</dd>

                    <dt class="col-sm-3">{{ __('IP do servidor') }}</dt>
                    <dd class="col-sm-9">
                        @if ($primary->server)
                            <code>{{ $primary->server->public_ip_address ?: $primary->server->ip_address }}</code>
                        @else
                            —
                        @endif
                    </dd>

                    <dt class="col-sm-3">{{ __('Limites') }}</dt>
                    <dd class="col-sm-9">
                        {{ __('Disco') }}: {{ number_format($primary->plan->disk_quota_mb / 1024, 1) }} GB
                        · {{ __('Banda') }}: {{ $primary->plan->bandwidth_quota_mb ? number_format($primary->plan->bandwidth_quota_mb / 1024, 1).' GB' : __('Ilimitada') }}
                        · {{ __('Bancos') }}: {{ $primary->plan->max_databases }}
                        · {{ __('Domínios') }}: {{ $primary->plan->max_addon_domains }}
                        · {{ __('E-mails') }}: {{ $primary->plan->max_email_accounts }}
                        · {{ __('Cron') }}: {{ $primary->plan->max_cron_jobs }}
                        · {{ __('Backups') }}: {{ $primary->plan->max_backups }}
                    </dd>
                </dl>
            </div>
        </div>

        <div class="mb-2 small text-uppercase text-secondary fw-semibold" style="letter-spacing: .04em;">
            {{ __('Acesso rápido') }} — {{ $primary->primary_domain }}
        </div>

        <div class="mb-3">
            <div class="small text-secondary mb-2">{{ __('Site') }}</div>
            <div class="row g-3">
                <div class="col-6 col-md-3 col-lg-2">
                    <a href="{{ route('client.hosting-accounts.files.index', $primary) }}" class="quick-link-card">
                        <span class="quick-link-icon"><i class="bi bi-folder2-open"></i></span>
                        <div class="fw-semibold small">{{ __('Arquivos') }}</div>
                    </a>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <a href="{{ route('client.hosting-accounts.php.index', $primary) }}" class="quick-link-card">
                        <span class="quick-link-icon"><i class="bi bi-sliders"></i></span>
                        <div class="fw-semibold small">{{ __('PHP') }}</div>
                    </a>
                </div>
            </div>
        </div>

        <div class="mb-3">
            <div class="small text-secondary mb-2">{{ __('Domínio') }}</div>
            <div class="row g-3">
                <div class="col-6 col-md-3 col-lg-2">
                    <a href="{{ route('client.hosting-accounts.dns.index', $primary) }}" class="quick-link-card">
                        <span class="quick-link-icon"><i class="bi bi-globe2"></i></span>
                        <div class="fw-semibold small">{{ __('DNS') }}</div>
                    </a>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <a href="{{ route('client.hosting-accounts.mail.index', $primary) }}" class="quick-link-card">
                        <span class="quick-link-icon"><i class="bi bi-envelope"></i></span>
                        <div class="fw-semibold small">{{ __('E-mail') }}</div>
                    </a>
                </div>
            </div>
        </div>

        <div>
            <div class="small text-secondary mb-2">{{ __('Avançado') }}</div>
            <div class="row g-3">
                <div class="col-6 col-md-3 col-lg-2">
                    <a href="{{ route('client.hosting-accounts.ssh.index', $primary) }}" class="quick-link-card">
                        <span class="quick-link-icon"><i class="bi bi-terminal"></i></span>
                        <div class="fw-semibold small">{{ __('SSH') }}</div>
                    </a>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <a href="{{ route('client.hosting-accounts.cron.index', $primary) }}" class="quick-link-card">
                        <span class="quick-link-icon"><i class="bi bi-clock-history"></i></span>
                        <div class="fw-semibold small">{{ __('Cron') }}</div>
                    </a>
                </div>
            </div>
        </div>
    @endif
</x-client-layout>

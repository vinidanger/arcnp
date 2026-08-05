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
                            <span class="badge text-bg-{{ $badge }}">{{ status_label($account->status) }}</span>
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
                                    <span class="badge text-bg-{{ $account->status === 'suspended' ? 'warning' : 'danger' }}">{{ status_label($account->status) }}</span>
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
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                    <h2 class="h6 mb-0">{{ __('Plano de hospedagem') }} — {{ $primary->primary_domain }}</h2>
                    <div class="d-flex align-items-center gap-3 small text-secondary">
                        <span>{{ __('Plano') }}: <strong class="text-body">{{ $primary->plan->name }}</strong></span>
                        @if ($primary->server)
                            <span>{{ __('IP') }}: <code>{{ $primary->server->public_ip_address ?: $primary->server->ip_address }}</code></span>
                        @endif
                    </div>
                </div>

                @php
                    $limits = [
                        ['icon' => 'bi-hdd', 'label' => __('Disco'), 'value' => number_format($primary->plan->disk_quota_mb / 1024, 1).' GB'],
                        ['icon' => 'bi-router', 'label' => __('Banda'), 'value' => $primary->plan->bandwidth_quota_mb ? number_format($primary->plan->bandwidth_quota_mb / 1024, 1).' GB' : __('Ilimitada')],
                        ['icon' => 'bi-database', 'label' => __('Bancos'), 'value' => $primary->plan->max_databases],
                        ['icon' => 'bi-globe2', 'label' => __('Domínios'), 'value' => $primary->plan->max_addon_domains],
                        ['icon' => 'bi-envelope', 'label' => __('E-mails'), 'value' => $primary->plan->max_email_accounts],
                        ['icon' => 'bi-clock-history', 'label' => __('Cron'), 'value' => $primary->plan->max_cron_jobs],
                        ['icon' => 'bi-archive', 'label' => __('Backups'), 'value' => $primary->plan->max_backups],
                    ];
                @endphp
                <div class="row g-2">
                    @foreach ($limits as $limit)
                        <div class="col-6 col-md-3 col-lg">
                            <div class="border rounded-3 p-2 text-center h-100">
                                <i class="bi {{ $limit['icon'] }} text-secondary"></i>
                                <div class="fw-semibold">{{ $limit['value'] }}</div>
                                <div class="small text-secondary">{{ $limit['label'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
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
                        <span class="quick-link-icon"><i class="bi bi-filetype-php"></i></span>
                        <div class="fw-semibold small">{{ __('PHP') }}</div>
                    </a>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <a href="{{ route('client.hosting-accounts.protected-folders.index', $primary) }}" class="quick-link-card">
                        <span class="quick-link-icon"><i class="bi bi-shield-lock"></i></span>
                        <div class="fw-semibold small">{{ __('Proteção de pasta') }}</div>
                    </a>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <a href="{{ route('client.hosting-accounts.redirects.index', $primary) }}" class="quick-link-card">
                        <span class="quick-link-icon"><i class="bi bi-signpost-split"></i></span>
                        <div class="fw-semibold small">{{ __('Redirecionamentos') }}</div>
                    </a>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <a href="{{ route('client.hosting-accounts.hotlink-protection.index', $primary) }}" class="quick-link-card">
                        <span class="quick-link-icon"><i class="bi bi-link-45deg"></i></span>
                        <div class="fw-semibold small">{{ __('Proteção Hotlink') }}</div>
                    </a>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <a href="{{ route('client.hosting-accounts.logs.index', $primary) }}" class="quick-link-card">
                        <span class="quick-link-icon"><i class="bi bi-file-text"></i></span>
                        <div class="fw-semibold small">{{ __('Logs') }}</div>
                    </a>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <a href="{{ route('client.hosting-accounts.apps.index', $primary) }}" class="quick-link-card">
                        <span class="quick-link-icon"><i class="bi bi-cpu"></i></span>
                        <div class="fw-semibold small">{{ __('Apps') }}</div>
                    </a>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <a href="{{ route('client.hosting-accounts.installer.index', $primary) }}" class="quick-link-card">
                        <span class="quick-link-icon"><i class="bi bi-box-seam"></i></span>
                        <div class="fw-semibold small">{{ __('Instalador') }}</div>
                    </a>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    <a href="{{ route('client.hosting-accounts.mime-types.index', $primary) }}" class="quick-link-card">
                        <span class="quick-link-icon"><i class="bi bi-file-earmark-code"></i></span>
                        <div class="fw-semibold small">{{ __('MIME Types') }}</div>
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
                <div class="col-6 col-md-3 col-lg-2">
                    <a href="{{ route('client.hosting-accounts.mail-log.index', $primary) }}" class="quick-link-card">
                        <span class="quick-link-icon"><i class="bi bi-envelope-paper"></i></span>
                        <div class="fw-semibold small">{{ __('Rastrear e-mails') }}</div>
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
                <div class="col-6 col-md-3 col-lg-2">
                    <a href="{{ route('client.hosting-accounts.ftp.index', $primary) }}" class="quick-link-card">
                        <span class="quick-link-icon"><i class="bi bi-hdd-network"></i></span>
                        <div class="fw-semibold small">{{ __('FTP') }}</div>
                    </a>
                </div>
                <div class="col-6 col-md-3 col-lg-2">
                    @if ($primary->ssh_enabled)
                        <a href="{{ $primary->server->terminalBaseUrl() }}" target="_blank" rel="noopener" class="quick-link-card" title="{{ __('No terminal, use o usuário: ').$primary->linux_username }}">
                            <span class="quick-link-icon"><i class="bi bi-terminal-fill"></i></span>
                            <div class="fw-semibold small">{{ __('Terminal') }}</div>
                        </a>
                    @else
                        <div class="quick-link-card text-secondary" style="opacity: .5;" title="{{ __('Ative o acesso SSH primeiro.') }}">
                            <span class="quick-link-icon"><i class="bi bi-terminal-fill"></i></span>
                            <div class="fw-semibold small">{{ __('Terminal') }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</x-client-layout>

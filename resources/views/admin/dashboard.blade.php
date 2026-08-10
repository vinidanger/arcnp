<x-admin-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Dashboard') }}</h1>
    </x-slot>

    <p class="text-secondary mb-4">{{ __('Bem-vindo, :name.', ['name' => auth()->user()->name]) }}</p>

    {{-- Estatísticas --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="stat-tile d-flex align-items-center gap-3">
                <div class="stat-tile-icon bg-primary-subtle text-primary">
                    <i class="bi bi-people"></i>
                </div>
                <div>
                    <div class="stat-tile-value">{{ $stats['clients'] }}</div>
                    <div class="small text-secondary">{{ __('Clientes') }}</div>
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
                    <div class="small text-secondary">{{ __('Contas ativas') }} <span class="text-body-tertiary">/ {{ $stats['accounts_total'] }}</span></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-tile d-flex align-items-center gap-3">
                <div class="stat-tile-icon bg-info-subtle text-info">
                    <i class="bi bi-hdd-network"></i>
                </div>
                <div>
                    <div class="stat-tile-value">{{ $stats['servers_online'] }}<span class="text-body-tertiary fs-6"> / {{ $stats['servers_total'] }}</span></div>
                    <div class="small text-secondary">{{ __('Servidores online') }}</div>
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
                    <div class="small text-secondary">{{ __('Disco usado (todas as contas)') }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 mb-0">{{ __('Últimas contas criadas') }}</h2>
                        <a href="{{ route('admin.hosting-accounts.index') }}" class="btn btn-sm btn-outline-primary">{{ __('Ver todas') }}</a>
                    </div>

                    @forelse ($recentAccounts as $account)
                        <div class="d-flex justify-content-between align-items-center py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                            <div>
                                <a href="{{ route('admin.hosting-accounts.show', $account) }}">{{ $account->primary_domain }}</a>
                                <div class="small text-secondary">{{ $account->client?->name }} · {{ $account->plan?->name }}</div>
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
                        <p class="small text-secondary mb-0">{{ __('Nenhuma conta cadastrada ainda.') }}</p>
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
                                <a href="{{ route('admin.hosting-accounts.show', $account) }}">{{ $account->primary_domain }}</a>
                                <div class="small text-secondary">{{ $account->client?->name }}</div>
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

    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 mb-0">{{ __('Últimas ações sensíveis') }}</h2>
                        <a href="{{ route('admin.audit-logs.index') }}" class="btn btn-sm btn-outline-primary">{{ __('Ver log completo') }}</a>
                    </div>

                    @forelse ($auditLogs as $log)
                        <div class="d-flex justify-content-between align-items-center py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                            <div>
                                <span class="small">{{ $log->subject_type }}: {{ $log->subject_label }}</span>
                                <div class="small text-secondary">{{ $log->user_name ?? __('Sistema') }} · {{ $log->created_at?->diffForHumans() }}</div>
                            </div>
                            <span class="badge text-bg-{{ match ($log->action) { 'created' => 'success', 'deleted' => 'danger', default => 'secondary' } }}">
                                {{ __($log->action) }}
                            </span>
                        </div>
                    @empty
                        <p class="small text-secondary mb-0">{{ __('Nenhuma ação registrada ainda.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6 mb-3">{{ __('Contas fora do ar agora') }}</h2>

                    @forelse ($downTargets as $target)
                        <div class="d-flex justify-content-between align-items-center py-2 {{ ! $loop->last ? 'border-bottom' : '' }}">
                            <div>
                                @if ($target['account'])
                                    <a href="{{ route('admin.hosting-accounts.show', $target['account']) }}">{{ $target['label'] }}</a>
                                    <div class="small text-secondary">{{ $target['account']->client?->name }}</div>
                                @else
                                    {{ $target['label'] }}
                                @endif
                            </div>
                            <span class="badge text-bg-danger">{{ $target['down_since']?->diffForHumans() }}</span>
                        </div>
                    @empty
                        <p class="small text-secondary mb-0">{{ __('Nada fora do ar agora — tudo no ar.') }}</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Acesso rápido, por categoria — mesmo agrupamento do menu lateral --}}
    <h2 class="h6 text-secondary mb-3">{{ __('Acesso rápido') }}</h2>

    <div class="mb-2 small text-uppercase text-secondary fw-semibold" style="letter-spacing: .04em;">{{ __('Clientes e hospedagem') }}</div>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <a href="{{ route('admin.clients.index') }}" class="quick-link-card">
                <span class="quick-link-icon"><i class="bi bi-people"></i></span>
                <div>
                    <div class="fw-semibold">{{ __('Clientes') }}</div>
                    <div class="small text-secondary">{{ __('Cadastrar e gerenciar clientes') }}</div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('admin.hosting-accounts.index') }}" class="quick-link-card">
                <span class="quick-link-icon"><i class="bi bi-hdd-stack"></i></span>
                <div>
                    <div class="fw-semibold">{{ __('Hospedagens') }}</div>
                    <div class="small text-secondary">{{ __('Contas, domínios, SSL, backup') }}</div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('admin.plans.index') }}" class="quick-link-card">
                <span class="quick-link-icon"><i class="bi bi-box-seam"></i></span>
                <div>
                    <div class="fw-semibold">{{ __('Planos') }}</div>
                    <div class="small text-secondary">{{ __('Limites e ofertas') }}</div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('admin.tickets.index') }}" class="quick-link-card">
                <span class="quick-link-icon"><i class="bi bi-life-preserver"></i></span>
                <div>
                    <div class="fw-semibold">{{ __('Chamados') }}</div>
                    <div class="small text-secondary">{{ __('Suporte aos clientes') }}</div>
                </div>
            </a>
        </div>
    </div>

    <div class="mb-2 small text-uppercase text-secondary fw-semibold" style="letter-spacing: .04em;">{{ __('Infraestrutura') }}</div>
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <a href="{{ route('admin.servers.index') }}" class="quick-link-card">
                <span class="quick-link-icon"><i class="bi bi-server"></i></span>
                <div>
                    <div class="fw-semibold">{{ __('Servidores') }}</div>
                    <div class="small text-secondary">{{ __('Agentes pareados, status') }}</div>
                </div>
            </a>
        </div>
    </div>

    <div class="mb-2 small text-uppercase text-secondary fw-semibold" style="letter-spacing: .04em;">{{ __('Sistema') }}</div>
    <div class="row g-3">
        <div class="col-md-4">
            <a href="{{ route('admin.announcements.index') }}" class="quick-link-card">
                <span class="quick-link-icon"><i class="bi bi-megaphone"></i></span>
                <div>
                    <div class="fw-semibold">{{ __('Mensagens do sistema') }}</div>
                    <div class="small text-secondary">{{ __('Avisos pros clientes') }}</div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('admin.api-clients.index') }}" class="quick-link-card">
                <span class="quick-link-icon"><i class="bi bi-plug"></i></span>
                <div>
                    <div class="fw-semibold">{{ __('Integrações de API') }}</div>
                    <div class="small text-secondary">{{ __('Tokens e documentação') }}</div>
                </div>
            </a>
        </div>
        <div class="col-md-4">
            <a href="{{ route('admin.settings.edit') }}" class="quick-link-card">
                <span class="quick-link-icon"><i class="bi bi-gear"></i></span>
                <div>
                    <div class="fw-semibold">{{ __('Configurações') }}</div>
                    <div class="small text-secondary">{{ __('Ajustes gerais do painel') }}</div>
                </div>
            </a>
        </div>
    </div>
</x-admin-layout>

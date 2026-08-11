<x-admin-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h4 mb-1">{{ $account->primary_domain }}</h1>
                <div class="d-flex align-items-center gap-2">
                    @php
                        $badge = match ($account->status) {
                            'active' => 'success',
                            'suspended' => 'warning',
                            'error' => 'danger',
                            default => 'secondary',
                        };
                    @endphp
                    <span class="badge text-bg-{{ $badge }}">{{ status_label($account->status) }}</span>
                    <span class="text-secondary small">{{ $account->client->name }} · {{ $account->plan->name }}</span>
                </div>
            </div>

            <div class="d-flex gap-2 align-items-center">
                @if ($account->status === 'error')
                    <form method="POST" action="{{ route('admin.hosting-accounts.retry', $account) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-warning">{{ __('Tentar provisionar novamente') }}</button>
                    </form>
                @endif

                <x-dropdown align="end">
                    <x-slot name="trigger">
                        <button type="button" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-three-dots"></i> {{ __('Ações') }}
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        @if ($account->status === 'active' && $account->ssl_status !== 'active')
                            <li>
                                <form method="POST" action="{{ route('admin.hosting-accounts.ssl.store', $account) }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item"><i class="bi bi-shield-check me-1"></i> {{ __('Emitir SSL') }}</button>
                                </form>
                            </li>
                        @endif

                        @if ($account->status === 'active')
                            <li>
                                <form method="POST" action="{{ route('admin.hosting-accounts.suspend', $account) }}"
                                      onsubmit="return confirm('{{ __('Isso desativa o site e o e-mail do cliente até reativar. Continuar?') }}')">
                                    @csrf
                                    <button type="submit" class="dropdown-item"><i class="bi bi-pause-circle me-1"></i> {{ __('Suspender') }}</button>
                                </form>
                            </li>
                        @elseif ($account->status === 'suspended')
                            <li>
                                <form method="POST" action="{{ route('admin.hosting-accounts.reactivate', $account) }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item"><i class="bi bi-play-circle me-1"></i> {{ __('Reativar') }}</button>
                                </form>
                            </li>
                        @endif

                        <li><hr class="dropdown-divider"></li>

                        <li>
                            <form method="POST" action="{{ route('admin.hosting-accounts.destroy', $account) }}"
                                  onsubmit="return confirm('{{ __('Isso remove o usuário Linux, vhost, pool PHP-FPM e banco de dados do servidor. Continuar?') }}')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-1"></i> {{ __('Excluir') }}</button>
                            </form>
                        </li>
                    </x-slot>
                </x-dropdown>
            </div>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($account->status === 'error' && $account->last_provision_error)
        <div class="alert alert-danger">
            <strong>{{ __('Erro no provisionamento:') }}</strong> {{ $account->last_provision_error }}
        </div>
    @endif

    @if (session('plain_ssh_password'))
        <div class="alert alert-warning">
            <strong>{{ __('Credenciais de acesso do cliente — copie agora, não aparecem de novo.') }}</strong>
            <p class="small mb-2">{{ __('Essa é a senha que o cliente usa pra entrar no painel (login com o usuário abaixo) e também a senha de SSH.') }}</p>
            <pre class="mb-0 bg-body-secondary p-2 rounded border small">USUÁRIO={{ $account->linux_username }}
SENHA={{ session('plain_ssh_password') }}</pre>
        </div>
    @endif

    @if (session('plain_db_password'))
        <div class="alert alert-warning">
            <strong>{{ __('Credenciais do banco de dados — copie agora, não aparecem de novo.') }}</strong>
            <pre class="mb-0 mt-2 bg-body-secondary p-2 rounded border small">DB_DATABASE={{ session('plain_db_name') }}
DB_USERNAME={{ session('plain_db_username') }}
DB_PASSWORD={{ session('plain_db_password') }}</pre>
        </div>
    @endif

    @php
        $diskUsed = $account->disk_usage_mb ?? 0;
        $diskQuota = max($account->plan->disk_quota_mb, 1);
        $diskPercent = min(100, (int) round(($diskUsed / $diskQuota) * 100));
        $diskSeverity = $diskPercent >= 90 ? 'danger' : ($diskPercent >= 70 ? 'warning' : 'success');
        $severityOf = fn ($percent) => $percent >= 90 ? 'danger' : ($percent >= 70 ? 'warning' : 'success');
        $percentOf = fn ($used, $limit) => min(100, (int) round($used / max($limit, 1) * 100));

        $dbCount = $account->databases->count();
        $dbPercent = $percentOf($dbCount, $account->plan->max_databases);

        $domainCount = $account->domains->count();
        $domainPercent = $percentOf($domainCount, $account->plan->max_addon_domains);

        $cronCount = $account->cronJobs->count();
        $cronPercent = $percentOf($cronCount, $account->plan->max_cron_jobs);

        $backupCount = $account->backups->whereIn('status', ['pending', 'completed'])->count();
        $backupLimitReached = $backupCount >= $account->plan->max_backups;
    @endphp

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button">
                <i class="bi bi-grid-1x2 me-1"></i> {{ __('Visão geral') }}
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-domains" type="button">
                <i class="bi bi-globe2 me-1"></i> {{ __('Domínios') }} <span class="badge text-bg-secondary rounded-pill ms-1">{{ $domainCount }}</span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-databases" type="button">
                <i class="bi bi-database me-1"></i> {{ __('Bancos de dados') }} <span class="badge text-bg-secondary rounded-pill ms-1">{{ $dbCount }}</span>
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-backups" type="button">
                <i class="bi bi-archive me-1"></i> {{ __('Backups') }} <span class="badge text-bg-secondary rounded-pill ms-1">{{ $account->backups->count() }}</span>
            </button>
        </li>
    </ul>

    <div class="tab-content">
        {{-- ============================== VISÃO GERAL ============================== --}}
        <div class="tab-pane fade show active" id="tab-overview">
            <div class="row g-3 mb-3">
                <div class="col-6 col-lg-3">
                    <div class="stat-tile">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <div class="stat-tile-icon bg-{{ $diskSeverity }}-subtle text-{{ $diskSeverity }}"><i class="bi bi-hdd"></i></div>
                            <div>
                                <div class="stat-tile-value">{{ $diskPercent }}<span class="fs-6 text-body-tertiary">%</span></div>
                                <div class="small text-secondary">{{ __('Disco') }} — {{ $diskUsed }}/{{ $diskQuota }} MB</div>
                            </div>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-{{ $diskSeverity }}" style="width: {{ $diskPercent }}%"></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-tile">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <div class="stat-tile-icon bg-primary-subtle text-primary"><i class="bi bi-database"></i></div>
                            <div>
                                <div class="stat-tile-value">{{ $dbCount }}<span class="fs-6 text-body-tertiary"> / {{ $account->plan->max_databases }}</span></div>
                                <div class="small text-secondary">{{ __('Bancos de dados') }}</div>
                            </div>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-{{ $severityOf($dbPercent) }}" style="width: {{ $dbPercent }}%"></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-tile">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <div class="stat-tile-icon bg-info-subtle text-info"><i class="bi bi-globe2"></i></div>
                            <div>
                                <div class="stat-tile-value">{{ $domainCount }}<span class="fs-6 text-body-tertiary"> / {{ $account->plan->max_addon_domains }}</span></div>
                                <div class="small text-secondary">{{ __('Domínios adicionais') }}</div>
                            </div>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-{{ $severityOf($domainPercent) }}" style="width: {{ $domainPercent }}%"></div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="stat-tile">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <div class="stat-tile-icon bg-warning-subtle text-warning"><i class="bi bi-clock-history"></i></div>
                            <div>
                                <div class="stat-tile-value">{{ $cronCount }}<span class="fs-6 text-body-tertiary"> / {{ $account->plan->max_cron_jobs }}</span></div>
                                <div class="small text-secondary">{{ __('Tarefas cron') }}</div>
                            </div>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-{{ $severityOf($cronPercent) }}" style="width: {{ $cronPercent }}%"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <h2 class="h6">{{ __('Detalhes da conta') }}</h2>
                    <dl class="row mb-0 small">
                        <dt class="col-sm-3">{{ __('Cliente') }}</dt>
                        <dd class="col-sm-9">{{ $account->client->name }} ({{ $account->client->email }})</dd>

                        <dt class="col-sm-3">{{ __('Servidor') }}</dt>
                        <dd class="col-sm-9">
                            <a href="{{ route('admin.servers.show', $account->server) }}">{{ $account->server->name }}</a>
                        </dd>

                        <dt class="col-sm-3">{{ __('Plano') }}</dt>
                        <dd class="col-sm-9">{{ $account->plan->name }}</dd>

                        <dt class="col-sm-3">{{ __('Username Linux') }}</dt>
                        <dd class="col-sm-9"><code>{{ $account->linux_username }}</code></dd>

                        <dt class="col-sm-3">{{ __('Diretório público') }}</dt>
                        <dd class="col-sm-9">
                            @if ($account->status === 'active')
                                <form method="POST" action="{{ route('admin.hosting-accounts.public-path.update', $account) }}" class="d-flex align-items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    <code class="small">public_html{{ $account->public_path ? '/'.$account->public_path : '' }}</code>
                                    <input type="text" name="public_path" value="{{ old('public_path', $account->public_path) }}" placeholder="{{ __('vazio = raiz') }}" class="form-control form-control-sm" style="width: 9rem;">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('Salvar') }}</button>
                                </form>
                                <x-input-error :messages="$errors->get('public_path')" class="mt-1" />
                                <div class="small text-secondary mt-1">{{ __('Pra apps tipo Laravel/Symfony, cujo index.php real fica numa subpasta (ex.: "public") em vez da raiz do projeto.') }}</div>
                            @else
                                <code class="small">public_html{{ $account->public_path ? '/'.$account->public_path : '' }}</code>
                            @endif
                        </dd>

                        <dt class="col-sm-3">{{ __('Versão PHP') }}</dt>
                        <dd class="col-sm-9">
                            @if ($account->status === 'active')
                                <form method="POST" action="{{ route('admin.hosting-accounts.php.version.update', $account) }}" class="d-flex align-items-center gap-2">
                                    @csrf
                                    <select name="php_version" class="form-select form-select-sm w-auto">
                                        @foreach (config('hosting.php_versions') as $version)
                                            <option value="{{ $version }}" @selected($account->php_version === $version)>{{ $version }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('Trocar') }}</button>
                                    <a href="{{ route('admin.hosting-accounts.php.index', $account) }}" class="small">{{ __('mais configurações') }}</a>
                                </form>
                            @else
                                {{ $account->php_version }}
                            @endif
                        </dd>

                        <dt class="col-sm-3">{{ __('SSL') }}</dt>
                        <dd class="col-sm-9">
                            <x-ssl-info :model="$account" />
                        </dd>

                        <dt class="col-sm-3">{{ __('Disponibilidade') }}</dt>
                        <dd class="col-sm-9">
                            <x-uptime-badge :model="$account" />
                        </dd>

                        <dt class="col-sm-3">{{ __('Segurança') }}</dt>
                        <dd class="col-sm-9">
                            <x-security-score-badge :account="$account" :detailed="true" />
                        </dd>
                    </dl>
                </div>
            </div>

            @if ($account->status === 'active')
                @php
                    // Mesmo padrão/agrupamento já usado na versão cliente
                    // (ver client/hosting-accounts/show.blade.php) — cada
                    // item é [tipo, alvo, ícone, rótulo]. "tab" troca pra
                    // uma aba que já existe NESTA MESMA página (nav-tabs
                    // no topo — Domínios/Bancos de Dados/Backups).
                    $adminToolCategories = [
                        'Site' => [
                            ['route', 'admin.hosting-accounts.files.index', 'bi-folder2-open', 'Arquivos'],
                            ['route', 'admin.hosting-accounts.php.index', 'bi-filetype-php', 'PHP'],
                            ['route', 'admin.hosting-accounts.apps.index', 'bi-cpu', 'Apps'],
                            ['route', 'admin.hosting-accounts.installer.index', 'bi-box-seam', 'Instalador'],
                            ['route', 'admin.hosting-accounts.mime-types.index', 'bi-file-earmark-code', 'MIME Types'],
                            ['route', 'admin.hosting-accounts.image-optimization.index', 'bi-images', 'Otimizar imagens'],
                            ['route', 'admin.hosting-accounts.redis.index', 'bi-lightning-charge', 'Cache de objeto'],
                        ],
                        'Domínio' => [
                            ['tab', '#tab-domains', 'bi-globe2', 'Domínios'],
                            ['route', 'admin.hosting-accounts.dns.index', 'bi-hdd-network', 'DNS'],
                            ['route', 'admin.hosting-accounts.redirects.index', 'bi-signpost-split', 'Redirecionamentos'],
                        ],
                        'Banco de dados' => [
                            ['tab', '#tab-databases', 'bi-database', 'Bancos de Dados'],
                            ['url', route('admin.hosting-accounts.databases.phpmyadmin-all', $account), asset('storage/images/icons/phpmyadmin.png'), 'phpMyAdmin'],
                            ['tab', '#tab-backups', 'bi-archive', 'Backups'],
                        ],
                        'E-mail' => [
                            ['route', 'admin.hosting-accounts.mail.index', 'bi-envelope', 'E-mail'],
                            ['route', 'admin.hosting-accounts.mail-log.index', 'bi-envelope-paper', 'Rastrear e-mails'],
                        ],
                        'Segurança' => [
                            ['route', 'admin.hosting-accounts.protected-folders.index', 'bi-shield-lock', 'Proteção de pasta'],
                            ['route', 'admin.hosting-accounts.hotlink-protection.index', 'bi-link-45deg', 'Proteção Hotlink'],
                            ['route', 'admin.hosting-accounts.malware.index', 'bi-bug', 'Malware'],
                        ],
                        'Avançado' => [
                            ['route', 'admin.hosting-accounts.ssh.index', 'bi-terminal', 'SSH'],
                            ['route', 'admin.hosting-accounts.cron.index', 'bi-clock-history', 'Cron'],
                            ['route', 'admin.hosting-accounts.ftp.index', 'bi-hdd-network', 'FTP'],
                            ['terminal', null, 'bi-terminal-fill', 'Terminal'],
                            ['route', 'admin.hosting-accounts.logs.index', 'bi-file-text', 'Logs'],
                            ['route', 'admin.hosting-accounts.traffic.index', 'bi-graph-up', 'Estatísticas'],
                            ['route', 'admin.hosting-accounts.resources.index', 'bi-speedometer2', 'Recursos'],
                        ],
                    ];
                @endphp

                <div class="mt-3 mb-2 small text-uppercase text-secondary fw-semibold" style="letter-spacing: .04em;">{{ __('Acesso rápido') }}</div>

                @foreach ($adminToolCategories as $categoryName => $tools)
                    <div class="mb-3">
                        <div class="small text-secondary mb-2">{{ __($categoryName) }}</div>
                        <div class="row g-3">
                            @foreach ($tools as [$type, $target, $icon, $label])
                                <div class="col-6 col-md-3 col-lg-2">
                                    @if ($type === 'terminal')
                                        @if ($account->ssh_enabled)
                                            <a href="{{ $account->server->terminalBaseUrl() }}" target="_blank" rel="noopener" class="quick-link-card" title="{{ __('No terminal, use o usuário: ').$account->linux_username }}">
                                                <span class="quick-link-icon"><i class="bi {{ $icon }}"></i></span>
                                                <div class="fw-semibold small">{{ __($label) }}</div>
                                            </a>
                                        @else
                                            <div class="quick-link-card text-secondary" style="opacity: .5;" title="{{ __('Ative o acesso SSH primeiro.') }}">
                                                <span class="quick-link-icon"><i class="bi {{ $icon }}"></i></span>
                                                <div class="fw-semibold small">{{ __($label) }}</div>
                                            </div>
                                        @endif
                                    @else
                                        @if ($type === 'route')
                                            <a href="{{ route($target, $account) }}" class="quick-link-card">
                                        @elseif ($type === 'tab')
                                            <a href="{{ $target }}" data-bs-toggle="tab" data-bs-target="{{ $target }}" class="quick-link-card">
                                        @else
                                            <a href="{{ $target }}" target="_blank" rel="noopener" class="quick-link-card">
                                        @endif
                                            <span class="quick-link-icon">
                                                @if (str_starts_with($icon, 'bi-'))
                                                    <i class="bi {{ $icon }}"></i>
                                                @else
                                                    <img src="{{ $icon }}" alt="" style="width: 1.05rem; height: 1.05rem;">
                                                @endif
                                            </span>
                                            <div class="fw-semibold small">{{ __($label) }}</div>
                                        </a>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        {{-- ============================== DOMÍNIOS ============================== --}}
        <div class="tab-pane fade" id="tab-domains">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="h6 mb-0">{{ __('Domínios adicionais / subdomínios') }}</h2>
                        @if ($account->status === 'active')
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#add-domain-modal">
                                <i class="bi bi-plus-lg"></i> {{ __('Adicionar domínio') }}
                            </button>
                        @endif
                    </div>

                    <div class="table-responsive mb-3">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Domínio') }}</th>
                                    <th>{{ __('Tipo') }}</th>
                                    <th>{{ __('Document root') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('SSL') }}</th>
                                    <th>{{ __('Disponibilidade') }}</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>{{ $account->primary_domain }}</td>
                                    <td><span class="badge text-bg-primary">{{ __('Principal') }}</span></td>
                                    <td>
                                        <code class="small">public_html{{ $account->public_path ? '/'.$account->public_path : '' }}</code>
                                        @if ($account->status === 'active')
                                            <button type="button" class="btn btn-sm btn-link p-0 ms-1" data-bs-toggle="modal" data-bs-target="#docroot-modal-primary" title="{{ __('Editar document root') }}">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $accountBadge = match ($account->status) {
                                                'active' => 'success',
                                                'error' => 'danger',
                                                default => 'secondary',
                                            };
                                        @endphp
                                        <span class="badge text-bg-{{ $accountBadge }}">{{ status_label($account->status) }}</span>
                                    </td>
                                    <td>
                                        <x-ssl-info :model="$account" />
                                    </td>
                                    <td>
                                        <x-uptime-badge :model="$account" />
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex align-items-center gap-1">
                                            @if ($account->waf_enabled)
                                                <span class="badge text-bg-success" title="{{ __('WAF ligado') }}"><i class="bi bi-shield-check"></i></span>
                                            @endif
                                            @if ($account->cache_enabled)
                                                <span class="badge text-bg-success" title="{{ __('Cache ligado') }}"><i class="bi bi-speedometer2"></i></span>
                                            @endif
                                            <x-dropdown align="end">
                                                <x-slot name="trigger">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary">
                                                        <i class="bi bi-three-dots"></i>
                                                    </button>
                                                </x-slot>
                                                <x-slot name="content">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('admin.hosting-accounts.php.index', $account) }}">
                                                            <i class="bi bi-filetype-php me-1"></i> {{ __('PHP') }}
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <form method="POST" action="{{ route('admin.hosting-accounts.waf.update', $account) }}"
                                                              onsubmit="return confirm('{{ __('Isso reescreve a configuração desse domínio no servidor — o site fica fora do ar por um instante. Continuar?') }}')">
                                                            @csrf
                                                            <input type="hidden" name="enabled" value="{{ $account->waf_enabled ? '0' : '1' }}">
                                                            <button type="submit" class="dropdown-item">
                                                                <i class="bi bi-shield-{{ $account->waf_enabled ? 'check' : 'slash' }} me-1"></i>
                                                                {{ __('WAF') }}: {{ $account->waf_enabled ? __('ligado') : __('desligado') }}
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form method="POST" action="{{ route('admin.hosting-accounts.cache.update', $account) }}"
                                                              onsubmit="return confirm('{{ __('Isso reescreve a configuração desse domínio no servidor — o site fica fora do ar por um instante. Continuar?') }}')">
                                                            @csrf
                                                            <input type="hidden" name="enabled" value="{{ $account->cache_enabled ? '0' : '1' }}">
                                                            <button type="submit" class="dropdown-item">
                                                                <i class="bi bi-speedometer2 me-1"></i>
                                                                {{ __('Cache') }}: {{ $account->cache_enabled ? __('ligado') : __('desligado') }}
                                                            </button>
                                                        </form>
                                                    </li>
                                                    @if ($account->cache_enabled)
                                                        <li>
                                                            <form method="POST" action="{{ route('admin.hosting-accounts.cache.purge', $account) }}">
                                                                @csrf
                                                                <button type="submit" class="dropdown-item"><i class="bi bi-arrow-clockwise me-1"></i> {{ __('Limpar cache') }}</button>
                                                            </form>
                                                        </li>
                                                    @endif
                                                </x-slot>
                                            </x-dropdown>
                                        </div>
                                    </td>
                                </tr>
                                @foreach ($account->domains as $domain)
                                        @php
                                            $domainBase = $domain->isOutsidePublicHtml()
                                                ? "domains/{$domain->domain}/public_html"
                                                : "public_html/{$domain->subdirectory}";
                                        @endphp
                                        <tr>
                                            <td>{{ $domain->domain }}</td>
                                            <td>{{ $domain->type === 'addon' ? __('Adicional') : __('Subdomínio') }}</td>
                                            <td>
                                                <code class="small">{{ $domainBase }}{{ $domain->public_path ? '/'.$domain->public_path : '' }}</code>
                                                <button type="button" class="btn btn-sm btn-link p-0 ms-1" data-bs-toggle="modal" data-bs-target="#docroot-modal-{{ $domain->id }}" title="{{ __('Editar document root') }}">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                            </td>
                                            <td>
                                                @php
                                                    $domainBadge = match ($domain->status) {
                                                        'active' => 'success',
                                                        'error' => 'danger',
                                                        default => 'secondary',
                                                    };
                                                @endphp
                                                <span class="badge text-bg-{{ $domainBadge }}">{{ status_label($domain->status) }}</span>
                                                @if ($domain->status === 'error' && $domain->last_error)
                                                    <div class="small text-danger">{{ $domain->last_error }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                <x-ssl-info :model="$domain" />
                                            </td>
                                            <td>
                                                <x-uptime-badge :model="$domain" />
                                            </td>
                                            <td class="text-end">
                                                <div class="d-inline-flex align-items-center gap-1">
                                                    @if ($domain->waf_enabled)
                                                        <span class="badge text-bg-success" title="{{ __('WAF ligado') }}"><i class="bi bi-shield-check"></i></span>
                                                    @endif
                                                    @if ($domain->cache_enabled)
                                                        <span class="badge text-bg-success" title="{{ __('Cache ligado') }}"><i class="bi bi-speedometer2"></i></span>
                                                    @endif
                                                    <x-dropdown align="end">
                                                        <x-slot name="trigger">
                                                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                                                <i class="bi bi-three-dots"></i>
                                                            </button>
                                                        </x-slot>
                                                        <x-slot name="content">
                                                            <li>
                                                                <a class="dropdown-item" href="{{ route('admin.hosting-accounts.domains.php.index', [$account, $domain]) }}">
                                                                    <i class="bi bi-filetype-php me-1"></i> {{ __('PHP') }}
                                                                </a>
                                                            </li>
                                                            <li>
                                                                <form method="POST" action="{{ route('admin.hosting-accounts.domains.waf.update', [$account, $domain]) }}"
                                                                      onsubmit="return confirm('{{ __('Isso reescreve a configuração desse domínio no servidor — o site fica fora do ar por um instante. Continuar?') }}')">
                                                                    @csrf
                                                                    <input type="hidden" name="enabled" value="{{ $domain->waf_enabled ? '0' : '1' }}">
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i class="bi bi-shield-{{ $domain->waf_enabled ? 'check' : 'slash' }} me-1"></i>
                                                                        {{ __('WAF') }}: {{ $domain->waf_enabled ? __('ligado') : __('desligado') }}
                                                                    </button>
                                                                </form>
                                                            </li>
                                                            <li>
                                                                <form method="POST" action="{{ route('admin.hosting-accounts.domains.cache.update', [$account, $domain]) }}"
                                                                      onsubmit="return confirm('{{ __('Isso reescreve a configuração desse domínio no servidor — o site fica fora do ar por um instante. Continuar?') }}')">
                                                                    @csrf
                                                                    <input type="hidden" name="enabled" value="{{ $domain->cache_enabled ? '0' : '1' }}">
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i class="bi bi-speedometer2 me-1"></i>
                                                                        {{ __('Cache') }}: {{ $domain->cache_enabled ? __('ligado') : __('desligado') }}
                                                                    </button>
                                                                </form>
                                                            </li>
                                                            @if ($domain->cache_enabled)
                                                                <li>
                                                                    <form method="POST" action="{{ route('admin.hosting-accounts.domains.cache.purge', [$account, $domain]) }}">
                                                                        @csrf
                                                                        <button type="submit" class="dropdown-item"><i class="bi bi-arrow-clockwise me-1"></i> {{ __('Limpar cache') }}</button>
                                                                    </form>
                                                                </li>
                                                            @endif
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <form method="POST" action="{{ route('admin.hosting-accounts.domains.destroy', [$account, $domain]) }}"
                                                                      onsubmit="return confirm('{{ __('Remove o vhost e o diretório desse domínio. Continuar?') }}')">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-1"></i> {{ __('Remover') }}</button>
                                                                </form>
                                                            </li>
                                                        </x-slot>
                                                    </x-dropdown>
                                                </div>
                                            </td>
                                        </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @unless ($account->status === 'active')
                        <p class="small text-secondary mb-0">{{ __('Disponível quando a conta estiver ativa.') }}</p>
                    @endunless
                </div>
            </div>
        </div>

        @if ($account->status === 'active')
            <x-modal name="docroot-modal-primary" maxWidth="sm">
                <form method="POST" action="{{ route('admin.hosting-accounts.public-path.update', $account) }}">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Document root — :domain', ['domain' => $account->primary_domain]) }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <x-input-label for="public_path-primary" value="{{ __('Subpasta dentro de public_html') }}" class="small mb-1" />
                        <x-text-input id="public_path-primary" name="public_path" type="text" :value="$account->public_path" placeholder="{{ __('vazio = raiz') }}" />
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancelar') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Salvar') }}</button>
                    </div>
                </form>
            </x-modal>
        @endif

        @foreach ($account->domains as $domain)
            @php
                $domainBase = $domain->isOutsidePublicHtml()
                    ? "domains/{$domain->domain}/public_html"
                    : "public_html/{$domain->subdirectory}";
            @endphp
            <x-modal name="docroot-modal-{{ $domain->id }}" maxWidth="sm">
                <form method="POST" action="{{ route('admin.hosting-accounts.domains.public-path.update', [$account, $domain]) }}">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('Document root — :domain', ['domain' => $domain->domain]) }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <x-input-label for="public_path-{{ $domain->id }}" value="{{ __('Subpasta dentro de :base', ['base' => $domainBase]) }}" class="small mb-1" />
                        <x-text-input id="public_path-{{ $domain->id }}" name="public_path" type="text" :value="$domain->public_path" placeholder="{{ __('vazio = raiz') }}" />
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancelar') }}</button>
                        <button type="submit" class="btn btn-primary">{{ __('Salvar') }}</button>
                    </div>
                </form>
            </x-modal>
        @endforeach

        {{-- ============================== BANCOS DE DADOS ============================== --}}
        <div class="tab-pane fade" id="tab-databases">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="h6 mb-0">{{ __('Bancos de dados') }}</h2>
                        @if ($account->status === 'active')
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#add-database-modal">
                                <i class="bi bi-plus-lg"></i> {{ __('Criar banco de dados') }}
                            </button>
                        @endif
                    </div>

                    @if ($account->databases->isNotEmpty())
                        <div class="table-responsive mb-3">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>{{ __('Banco') }}</th>
                                        <th>{{ __('Usuário') }}</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($account->databases as $database)
                                        <tr>
                                            <td><code>{{ $database->db_name }}</code></td>
                                            <td><code>{{ $database->db_username }}</code></td>
                                            <td class="text-end">
                                                <a href="{{ route('admin.hosting-accounts.databases.phpmyadmin', [$account, $database]) }}"
                                                   class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">{{ __('phpMyAdmin') }}</a>
                                                <form method="POST" action="{{ route('admin.hosting-accounts.databases.destroy', [$account, $database]) }}"
                                                      class="d-inline"
                                                      onsubmit="return confirm('{{ __('Remove o banco e o usuário MySQL. Continuar?') }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Remover') }}</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @unless ($account->status === 'active')
                        <p class="small text-secondary mb-0">{{ __('Disponível quando a conta estiver ativa.') }}</p>
                    @endunless
                </div>
            </div>
        </div>

        {{-- ============================== BACKUPS ============================== --}}
        <div class="tab-pane fade" id="tab-backups">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <h2 class="h6 mb-0">{{ __('Backups') }}</h2>
                            <span class="badge text-bg-{{ $backupLimitReached ? 'warning' : 'secondary' }} rounded-pill">{{ $backupCount }} / {{ $account->plan->max_backups }}</span>
                        </div>
                        @if ($account->status === 'active')
                            <div class="d-flex gap-2">
                                <form method="POST" action="{{ route('admin.hosting-accounts.backup-frequency.update', $account) }}" class="d-flex gap-1">
                                    @csrf
                                    <select name="backup_frequency" class="form-select form-select-sm" onchange="this.form.submit()">
                                        @foreach (config('hosting.backup_frequencies') as $frequency)
                                            <option value="{{ $frequency }}" @selected($account->backup_frequency === $frequency)>
                                                {{ match ($frequency) { 'daily' => __('Automático diário'), 'weekly' => __('Automático semanal'), default => __('Desativado') } }}
                                            </option>
                                        @endforeach
                                    </select>
                                </form>
                                <form method="POST" action="{{ route('admin.hosting-accounts.backups.store', $account) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-primary" @disabled($backupLimitReached) title="{{ $backupLimitReached ? __('Limite de backups do plano atingido') : '' }}">{{ __('Criar backup agora') }}</button>
                                </form>
                            </div>
                        @endif
                    </div>

                    @if ($account->backups->isNotEmpty())
                        <div class="table-responsive">
                            <table class="table table-sm mb-0 align-middle">
                                <thead>
                                    <tr>
                                        <th>{{ __('Data') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Tamanho') }}</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($account->backups as $backup)
                                        @php
                                            $backupBadge = match ($backup->status) {
                                                'completed' => 'success',
                                                'failed' => 'danger',
                                                default => 'info',
                                            };
                                            $hasFilesArchive = collect($backup->files)->contains(fn ($f) => str_starts_with($f['filename'], 'files-'));
                                            $hasDatabaseArchives = collect($backup->files)->contains(fn ($f) => str_starts_with($f['filename'], 'db-'));
                                            $totalSize = collect($backup->files)->sum('size');
                                        @endphp
                                        <tr>
                                            <td>{{ $backup->created_at->format('d/m/Y H:i') }}</td>
                                            <td>
                                                <span class="badge text-bg-{{ $backupBadge }}">{{ status_label($backup->status) }}</span>
                                                @if ($backup->status === 'failed' && $backup->error)
                                                    <div class="small text-danger">{{ $backup->error }}</div>
                                                @endif
                                            </td>
                                            <td class="small text-secondary">{{ $backup->status === 'completed' ? number_format($totalSize / 1048576, 1).' MB' : '—' }}</td>
                                            <td class="text-end">
                                                @if ($backup->status === 'completed')
                                                    <div class="dropdown d-inline-block">
                                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle d-inline-flex align-items-center gap-1" type="button" data-bs-toggle="dropdown" data-bs-strategy="fixed">
                                                            <i class="bi bi-download"></i> {{ __('Baixar') }}
                                                        </button>
                                                        <ul class="dropdown-menu dropdown-menu-end">
                                                            @if ($hasFilesArchive)
                                                                <li><a class="dropdown-item" href="{{ route('admin.hosting-accounts.backups.bundle', [$account, $backup, 'files']) }}">{{ __('Arquivos') }}</a></li>
                                                            @endif
                                                            @if ($hasDatabaseArchives)
                                                                <li><a class="dropdown-item" href="{{ route('admin.hosting-accounts.backups.bundle', [$account, $backup, 'databases']) }}">{{ __('Bancos de dados') }}</a></li>
                                                            @endif
                                                            <li><a class="dropdown-item" href="{{ route('admin.hosting-accounts.backups.bundle', [$account, $backup, 'all']) }}">{{ __('Completo') }}</a></li>
                                                        </ul>
                                                    </div>
                                                @endif
                                                <form method="POST" action="{{ route('admin.hosting-accounts.backups.destroy', [$account, $backup]) }}" class="d-inline"
                                                      onsubmit="return confirm('{{ __('Remove esse backup e seus arquivos do servidor. Continuar?') }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Remover') }}"><i class="bi bi-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="small text-secondary mb-0">{{ __('Nenhum backup ainda.') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Modal: adicionar domínio --}}
    <x-modal name="add-domain-modal" maxWidth="sm" :show="$errors->has('domain') || $errors->has('location')">
        <form method="POST" action="{{ route('admin.hosting-accounts.domains.store', $account) }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Adicionar domínio') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <x-input-label for="domain" value="{{ __('Domínio') }}" class="small mb-1" />
                    <x-text-input id="domain" name="domain" type="text" placeholder="blog.{{ $account->primary_domain }}" :value="old('domain')" required autofocus />
                    <x-input-error :messages="$errors->get('domain')" class="mt-2" />
                </div>
                <div class="mb-3">
                    <x-input-label for="domain-type" value="{{ __('Tipo') }}" class="small mb-1" />
                    <select id="domain-type" name="type" class="form-select">
                        <option value="subdomain">{{ __('Subdomínio') }}</option>
                        <option value="addon">{{ __('Domínio adicional') }}</option>
                    </select>
                </div>
                <div class="mb-0">
                    <x-input-label for="domain-location" value="{{ __('Localização') }}" class="small mb-1" />
                    <select id="domain-location" name="location" class="form-select">
                        <option value="inside_public_html">{{ __('Dentro de public_html') }}</option>
                        <option value="outside_public_html">{{ __('Fora de public_html (domains/)') }}</option>
                    </select>
                    <x-input-error :messages="$errors->get('location')" class="mt-2" />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancelar') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('Adicionar') }}</button>
            </div>
        </form>
    </x-modal>

    {{-- Modal: criar banco de dados --}}
    <x-modal name="add-database-modal" maxWidth="sm" :show="$errors->has('name') || $errors->has('username') || $errors->has('password')">
        <form method="POST" action="{{ route('admin.hosting-accounts.databases.store', $account) }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Criar banco de dados') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <x-input-label for="db_name" value="{{ __('Nome do banco') }}" class="small mb-1" />
                    <div class="input-group">
                        <span class="input-group-text">{{ $account->linux_username }}_</span>
                        <input id="db_name" name="name" type="text" class="form-control" placeholder="loja" value="{{ old('name') }}" required autofocus>
                    </div>
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <div class="mb-3">
                    <x-input-label for="db_username" value="{{ __('Usuário (opcional)') }}" class="small mb-1" />
                    <div class="input-group">
                        <span class="input-group-text">{{ $account->linux_username }}_</span>
                        <input id="db_username" name="username" type="text" class="form-control" placeholder="{{ __('igual ao nome do banco') }}" value="{{ old('username') }}">
                    </div>
                    <x-input-error :messages="$errors->get('username')" class="mt-2" />
                </div>
                <div class="mb-0">
                    <x-input-label for="db_password" value="{{ __('Senha (opcional)') }}" class="small mb-1" />
                    <input id="db_password" name="password" type="text" class="form-control" placeholder="{{ __('gerar automaticamente') }}">
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancelar') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('Criar banco') }}</button>
            </div>
        </form>
    </x-modal>
</x-admin-layout>

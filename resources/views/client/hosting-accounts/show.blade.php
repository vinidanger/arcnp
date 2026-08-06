<x-client-layout>
    @php
        $uiTemplate = auth()->user()->resolvedUiTemplate();

        // Precisa ser calculado ANTES do slot "header" logo abaixo — slots
        // nomeados rodam no ponto em que aparecem no arquivo, então um
        // @php mais pra baixo no mesmo arquivo (como este bloco vivia
        // antes) nunca está pronto a tempo pro slot, e {{ $badge }}
        // sai vazio (bug real, achado verificando esta página: o badge de
        // status no cabeçalho do template Padrão rendia sem cor
        // nenhuma — <span class="badge text-bg-"> — porque $badge só
        // existia depois do slot já ter sido capturado).
        $badge = match ($account->status) {
            'active' => 'success',
            'suspended' => 'warning',
            'error' => 'danger',
            default => 'secondary',
        };
    @endphp

    <x-slot name="header">
        @unless ($uiTemplate === 'cpanel')
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h4 mb-1">{{ $account->primary_domain }}</h1>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge text-bg-{{ $badge }}">{{ status_label($account->status) }}</span>
                        <span class="text-secondary small">{{ $account->plan->name }}</span>
                    </div>
                </div>

                @if ($account->status === 'active' && $account->ssl_status !== 'active')
                    <form method="POST" action="{{ route('client.hosting-accounts.ssl.store', $account) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('Emitir SSL') }}</button>
                    </form>
                @endif
            </div>
        @endunless
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($account->status === 'suspended')
        <div class="alert alert-warning">
            {{ __('Essa conta está suspensa. Entre em contato com o suporte se isso for inesperado.') }}
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
    @endphp

    @unless ($uiTemplate === 'cpanel')
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
    @endunless

    {{-- ============================== TEMPLATE CPANEL: página "Tools" ==============================
         Reaproveita os MESMOS destinos (rotas) da grade "Acesso rápido" de sempre, só
         reorganizados no layout de categorias colapsáveis do cPanel. "Domínios"/"Bancos de
         Dados"/"Backups" disparam a MESMA troca de aba que a barra escondida acima já fazia
         (data-bs-toggle="tab", sem JS novo pra isso — ver resources/js/app.js). --}}
    @if ($uiTemplate === 'cpanel' && $account->status !== 'active')
        <div class="cpanel-page-title mb-3">{{ __('Tools') }}</div>
        <div class="cpanel-info-panel">
            <p class="small text-secondary mb-0">{{ __('Disponível quando a conta estiver ativa.') }}</p>
        </div>
    @elseif ($uiTemplate === 'cpanel')
        @php
            // Cada item é [tipo, alvo, ícone, rótulo]. Tipo "route" vira
            // route($alvo, $account); "tab" dispara a mesma troca de aba que a
            // barra de abas escondida já faz (data-bs-toggle, sem JS novo);
            // "url" é link direto (externo, nova aba — caso do Terminal).
            $cpanelCategories = [
                'E-mail' => ['bi-envelope', [
                    ['route', 'client.hosting-accounts.mail.index', 'bi-envelope', 'E-mail'],
                    ['route', 'client.hosting-accounts.mail-log.index', 'bi-envelope-paper', 'Rastrear e-mails'],
                ]],
                'Domínios' => ['bi-globe2', [
                    ['route', 'client.hosting-accounts.domains.index', 'bi-globe2', 'Domínios'],
                    ['route', 'client.hosting-accounts.dns.index', 'bi-hdd-network', 'DNS'],
                    ['route', 'client.hosting-accounts.redirects.index', 'bi-signpost-split', 'Redirecionamentos'],
                ]],
                'Arquivos' => ['bi-folder2-open', [
                    ['route', 'client.hosting-accounts.files.index', 'bi-folder2-open', 'Arquivos'],
                    ['route', 'client.hosting-accounts.backups.index', 'bi-archive', 'Backups'],
                    ['route', 'client.hosting-accounts.mime-types.index', 'bi-file-earmark-code', 'MIME Types'],
                ]],
                'Bancos de Dados' => ['bi-database', [
                    ['route', 'client.hosting-accounts.databases.index', 'bi-database', 'Bancos de Dados'],
                ]],
                'Segurança' => ['bi-shield-lock', [
                    ['route', 'client.hosting-accounts.protected-folders.index', 'bi-shield-lock', 'Proteção de pasta'],
                    ['route', 'client.hosting-accounts.hotlink-protection.index', 'bi-link-45deg', 'Proteção Hotlink'],
                    ['route', 'client.hosting-accounts.ssh.index', 'bi-terminal', 'SSH'],
                    ['route', 'client.hosting-accounts.malware.index', 'bi-bug', 'Malware'],
                ]],
                'Software' => ['bi-box-seam', [
                    ['route', 'client.hosting-accounts.php.index', 'bi-filetype-php', 'PHP'],
                    ['route', 'client.hosting-accounts.apps.index', 'bi-cpu', 'Apps'],
                    ['route', 'client.hosting-accounts.installer.index', 'bi-box-seam', 'Instalador'],
                ]],
                'Avançado' => ['bi-gear', [
                    ['route', 'client.hosting-accounts.logs.index', 'bi-file-text', 'Logs'],
                    ['route', 'client.hosting-accounts.cron.index', 'bi-clock-history', 'Cron'],
                    ['route', 'client.hosting-accounts.ftp.index', 'bi-hdd-network', 'FTP'],
                    ['route', 'client.hosting-accounts.resources.index', 'bi-speedometer2', 'Recursos'],
                ]],
            ];

            if ($account->ssh_enabled) {
                $cpanelCategories['Avançado'][1][] = ['url', $account->server->terminalBaseUrl(), 'bi-terminal-fill', 'Terminal'];
            }

            // SSO via o usuário "mestre" da conta (grant em curinga sobre
            // todos os bancos, ver HostingAccountController::phpMyAdminSsoAll)
            // — lista TODOS os bancos de uma vez, diferente do link por
            // banco que já existe dentro da própria página de Bancos de
            // Dados (esse aqui é o atalho do topo, faz mais sentido
            // mostrar tudo já que não está no contexto de um banco só).
            $cpanelCategories['Bancos de Dados'][1][] = ['url', route('client.hosting-accounts.databases.phpmyadmin-all', $account), asset('storage/images/icons/phpmyadmin.png'), 'phpMyAdmin'];
        @endphp

        <div class="cpanel-page-title mb-3">{{ __('Tools') }}</div>

        <div class="row g-3">
            <div class="col-lg-8 cpanel-tools-column">
                @foreach ($cpanelCategories as $categoryName => [$categoryIcon, $tools])
                    <div class="cpanel-category" data-category="{{ \Illuminate\Support\Str::slug($categoryName) }}">
                        <button type="button" class="cpanel-category-header" draggable="true" data-cpanel-category-toggle>
                            <span class="cpanel-category-icon"><i class="bi {{ $categoryIcon }}"></i></span>
                            <span class="cpanel-category-title">{{ __($categoryName) }}</span>
                            <i class="bi bi-chevron-up cpanel-category-chevron"></i>
                        </button>
                        <div class="cpanel-tool-grid">
                            @foreach ($tools as [$type, $target, $icon, $label])
                                @if ($type === 'route')
                                    <a href="{{ route($target, $account) }}" class="cpanel-tool-item" data-tool-label="{{ __($label) }}">
                                @elseif ($type === 'tab')
                                    <a href="{{ $target }}" data-bs-toggle="tab" data-bs-target="{{ $target }}" class="cpanel-tool-item" data-tool-label="{{ __($label) }}">
                                @else
                                    <a href="{{ $target }}" target="_blank" rel="noopener" class="cpanel-tool-item" data-tool-label="{{ __($label) }}">
                                @endif
                                    <span class="cpanel-tool-icon">
                                        @if (str_starts_with($icon, 'bi-'))
                                            <i class="bi {{ $icon }}"></i>
                                        @else
                                            <img src="{{ $icon }}" alt="">
                                        @endif
                                    </span>
                                    <span class="cpanel-tool-label">{{ __($label) }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="col-lg-4">
                <div class="cpanel-info-panel mb-3">
                    <h2>{{ __('Informações Gerais') }}</h2>
                    <dl class="mb-0">
                        <div class="cpanel-info-row">
                            <dt>{{ __('Usuário') }}</dt>
                            <dd>{{ $account->linux_username }}</dd>
                        </div>
                        <div class="cpanel-info-row">
                            <dt><i class="bi bi-lock-fill"></i> {{ __('Domínio principal') }}</dt>
                            <dd>
                                <a href="http://{{ $account->primary_domain }}" target="_blank" rel="noopener" class="text-decoration-none">
                                    {{ $account->primary_domain }} <i class="bi bi-box-arrow-up-right" style="font-size: 0.7rem;"></i>
                                </a>
                            </dd>
                        </div>
                        <div class="cpanel-info-row">
                            <dt>{{ __('Plano') }}</dt>
                            <dd>{{ $account->plan->name }}</dd>
                        </div>
                        <div class="cpanel-info-row">
                            <dt>{{ __('Status') }}</dt>
                            <dd><span class="badge text-bg-{{ $badge }}">{{ status_label($account->status) }}</span></dd>
                        </div>
                        <div class="cpanel-info-row">
                            <dt>{{ __('SSL') }}</dt>
                            <dd><x-ssl-info :model="$account" /></dd>
                        </div>
                        <div class="cpanel-info-row">
                            <dt>{{ __('Disponibilidade') }}</dt>
                            <dd><x-uptime-badge :model="$account" /></dd>
                        </div>
                    </dl>

                    @if ($account->ssl_status !== 'active')
                        <form method="POST" action="{{ route('client.hosting-accounts.ssl.store', $account) }}" class="mt-2">
                            @csrf
                            <button type="submit" class="cpanel-ssl-btn"><i class="bi bi-bar-chart-line"></i> {{ __('Emitir SSL') }}</button>
                        </form>
                    @else
                        <div class="cpanel-ssl-btn mt-2" style="cursor: default;"><i class="bi bi-bar-chart-line"></i> {{ __('SSL/TLS ativo') }}</div>
                    @endif
                </div>

                <div class="cpanel-info-panel">
                    <h2>{{ __('Estatísticas') }}</h2>
                    <dl class="mb-0">
                        <div class="cpanel-info-row">
                            <dt>{{ __('Disco') }}</dt>
                            <dd>{{ $diskUsed }} / {{ $diskQuota }} MB</dd>
                        </div>
                        <div class="cpanel-info-row">
                            <dt>{{ __('Bancos de dados') }}</dt>
                            <dd>{{ $dbCount }} / {{ $account->plan->max_databases }}</dd>
                        </div>
                        <div class="cpanel-info-row">
                            <dt>{{ __('Domínios adicionais') }}</dt>
                            <dd>{{ $domainCount }} / {{ $account->plan->max_addon_domains }}</dd>
                        </div>
                        <div class="cpanel-info-row">
                            <dt>{{ __('Tarefas cron') }}</dt>
                            <dd>{{ $cronCount }} / {{ $account->plan->max_cron_jobs }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    @endif

    <div class="tab-content {{ $uiTemplate === 'cpanel' ? 'mt-3' : '' }}">
        {{-- ============================== VISÃO GERAL ==============================
             Só existe/começa ativa no template Padrão — no cPanel esse conteúdo
             (stat-tiles, detalhes, acesso rápido) foi reorganizado no painel
             "Informações Gerais"/"Estatísticas" + grade de ferramentas acima. --}}
        <div class="tab-pane fade {{ $uiTemplate === 'cpanel' ? '' : 'show active' }}" id="tab-overview">
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
                        <dt class="col-sm-3">{{ __('Plano') }}</dt>
                        <dd class="col-sm-9">{{ $account->plan->name }}</dd>

                        <dt class="col-sm-3">{{ __('Diretório público') }}</dt>
                        <dd class="col-sm-9">
                            @if ($account->status === 'active')
                                <form method="POST" action="{{ route('client.hosting-accounts.public-path.update', $account) }}" class="d-flex align-items-center gap-2">
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
                                <form method="POST" action="{{ route('client.hosting-accounts.php.version.update', $account) }}" class="d-flex align-items-center gap-2">
                                    @csrf
                                    <select name="php_version" class="form-select form-select-sm w-auto">
                                        @foreach (config('hosting.php_versions') as $version)
                                            <option value="{{ $version }}" @selected($account->php_version === $version)>{{ $version }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('Trocar') }}</button>
                                    <a href="{{ route('client.hosting-accounts.php.index', $account) }}" class="small">{{ __('mais configurações') }}</a>
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
                    </dl>
                </div>
            </div>

            @if ($account->status === 'active')
                @php
                    // Cada item é [tipo, alvo, ícone, rótulo]. "route" vira
                    // route($alvo, $account); "tab" troca pra uma aba que já
                    // existe NESTA MESMA página (nav-tabs logo no topo —
                    // Domínios/Bancos de Dados/Backups, via data-bs-toggle,
                    // sem JS novo, ver resources/js/app.js); "url" é link
                    // externo (nova aba); "terminal" é o único caso com
                    // estado desabilitado (SSH precisa estar ligado).
                    $padraoToolCategories = [
                        'Site' => [
                            ['route', 'client.hosting-accounts.files.index', 'bi-folder2-open', 'Arquivos'],
                            ['route', 'client.hosting-accounts.php.index', 'bi-filetype-php', 'PHP'],
                            ['route', 'client.hosting-accounts.apps.index', 'bi-cpu', 'Apps'],
                            ['route', 'client.hosting-accounts.installer.index', 'bi-box-seam', 'Instalador'],
                            ['route', 'client.hosting-accounts.mime-types.index', 'bi-file-earmark-code', 'MIME Types'],
                        ],
                        'Domínio' => [
                            ['tab', '#tab-domains', 'bi-globe2', 'Domínios'],
                            ['route', 'client.hosting-accounts.dns.index', 'bi-hdd-network', 'DNS'],
                            ['route', 'client.hosting-accounts.redirects.index', 'bi-signpost-split', 'Redirecionamentos'],
                        ],
                        'Banco de dados' => [
                            ['tab', '#tab-databases', 'bi-database', 'Bancos de Dados'],
                            ['url', route('client.hosting-accounts.databases.phpmyadmin-all', $account), asset('storage/images/icons/phpmyadmin.png'), 'phpMyAdmin'],
                            ['tab', '#tab-backups', 'bi-archive', 'Backups'],
                        ],
                        'E-mail' => [
                            ['route', 'client.hosting-accounts.mail.index', 'bi-envelope', 'E-mail'],
                            ['route', 'client.hosting-accounts.mail-log.index', 'bi-envelope-paper', 'Rastrear e-mails'],
                        ],
                        'Segurança' => [
                            ['route', 'client.hosting-accounts.protected-folders.index', 'bi-shield-lock', 'Proteção de pasta'],
                            ['route', 'client.hosting-accounts.hotlink-protection.index', 'bi-link-45deg', 'Proteção Hotlink'],
                            ['route', 'client.hosting-accounts.malware.index', 'bi-bug', 'Malware'],
                        ],
                        'Avançado' => [
                            ['route', 'client.hosting-accounts.ssh.index', 'bi-terminal', 'SSH'],
                            ['route', 'client.hosting-accounts.cron.index', 'bi-clock-history', 'Cron'],
                            ['route', 'client.hosting-accounts.ftp.index', 'bi-hdd-network', 'FTP'],
                            ['terminal', null, 'bi-terminal-fill', 'Terminal'],
                            ['route', 'client.hosting-accounts.logs.index', 'bi-file-text', 'Logs'],
                            ['route', 'client.hosting-accounts.resources.index', 'bi-speedometer2', 'Recursos'],
                        ],
                    ];
                @endphp

                <div class="mt-3 mb-2 small text-uppercase text-secondary fw-semibold" style="letter-spacing: .04em;">{{ __('Acesso rápido') }}</div>

                @foreach ($padraoToolCategories as $categoryName => $tools)
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

        {{-- Domínios/Bancos de dados/Backups: só no template Padrão — no
             cPanel cada um virou uma página própria (mesmo padrão de
             Arquivos/DNS/SSH/etc.), ver rotas hosting-accounts.domains.index
             /.databases.index/.backups.index e as views em
             domains|databases|backups/index.blade.php. Conteúdo
             compartilhado via partial pra não duplicar HTML. --}}
        @unless ($uiTemplate === 'cpanel')
            {{-- ============================== DOMÍNIOS ============================== --}}
            <div class="tab-pane fade" id="tab-domains">
                @include('client.hosting-accounts.partials._domains-table')
            </div>

            {{-- ============================== BANCOS DE DADOS ============================== --}}
            <div class="tab-pane fade" id="tab-databases">
                @include('client.hosting-accounts.partials._databases-table')
            </div>

            {{-- ============================== BACKUPS ============================== --}}
            <div class="tab-pane fade" id="tab-backups">
                @include('client.hosting-accounts.partials._backups-table')
            </div>
        @endunless
    </div>

    @unless ($uiTemplate === 'cpanel')
        @include('client.hosting-accounts.partials._add-domain-modal')
        @include('client.hosting-accounts.partials._add-database-modal')
    @endunless
</x-client-layout>

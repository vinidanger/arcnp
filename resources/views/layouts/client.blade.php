<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-template="{{ auth()->user()?->resolvedUiTemplate() ?? 'default' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @include('layouts.partials.theme-init')

        <title>{{ config('app.name', 'Arcn Panel') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/file-manager.js'])
    </head>
    <body class="min-h-screen">
        @php
            // Mesma fonte usada pelo back-link do header, logo abaixo — na
            // maioria das páginas (as ~80 rotas aninhadas em
            // hosting-accounts/{hosting_account}/...) já vem pronto da
            // própria rota, sem query extra. Só cai pro fallback nas
            // poucas páginas "soltas" (chamados, a home de espera).
            $navAccount = request()->route('hosting_account') ?? auth()->user()->hostingAccount;
            $uiTemplate = auth()->user()?->resolvedUiTemplate() ?? 'default';
        @endphp

        <div class="flex min-h-screen">
            @if ($uiTemplate === 'cpanel')
                {{-- Sidebar minimalista (arquitetura de navegação do
                     cPanel: a lista completa de ferramentas não fica na
                     sidebar, fica na grade central da página "Tools" —
                     ver client/hosting-accounts/show.blade.php). Sem
                     item de enfeite só pra imitar a contagem do cPanel
                     de verdade — só 2 destinos reais. --}}
                <aside class="app-sidebar flex flex-col p-3">
                    <a class="flex items-center gap-2 px-1 mb-4 font-semibold text-white no-underline" href="{{ route('client.dashboard') }}">
                        <x-application-logo class="w-7 h-7 shrink-0" style="fill: currentColor;" />
                        <span>{{ config('app.name', 'Arcn Panel') }}</span>
                    </a>

                    <nav class="flex flex-col gap-1">
                        <a href="{{ route('client.dashboard') }}" class="nav-link {{ request()->routeIs('client.dashboard') || request()->routeIs('client.hosting-accounts.show') ? 'active' : '' }}">
                            <i class="bi bi-grid-3x3-gap"></i> <span>{{ __('Tools') }}</span>
                        </a>

                        @if ($navAccount)
                            <a href="{{ route('client.hosting-accounts.files.index', $navAccount) }}" class="nav-link {{ request()->routeIs('client.hosting-accounts.files.*') ? 'active' : '' }}">
                                <i class="bi bi-folder2-open"></i> <span>{{ __('Arquivos') }}</span>
                            </a>
                            <a href="{{ route('client.hosting-accounts.mail.index', $navAccount) }}" class="nav-link {{ request()->routeIs('client.hosting-accounts.mail.*') ? 'active' : '' }}">
                                <i class="bi bi-envelope"></i> <span>{{ __('E-mail') }}</span>
                            </a>
                            <a href="{{ route('client.hosting-accounts.databases.index', $navAccount) }}" class="nav-link {{ request()->routeIs('client.hosting-accounts.databases.*') ? 'active' : '' }}">
                                <i class="bi bi-database"></i> <span>{{ __('Bancos de Dados') }}</span>
                            </a>
                            <a href="{{ route('client.hosting-accounts.backups.index', $navAccount) }}" class="nav-link {{ request()->routeIs('client.hosting-accounts.backups.*') ? 'active' : '' }}">
                                <i class="bi bi-archive"></i> <span>{{ __('Backups') }}</span>
                            </a>
                        @endif

                        <a href="{{ route('client.tickets.index') }}" class="nav-link {{ request()->routeIs('client.tickets.*') ? 'active' : '' }}">
                            <i class="bi bi-life-preserver"></i> <span>{{ __('Chamados') }}</span>
                        </a>

                        @unless (auth()->user()->ui_template_locked)
                            <a href="{{ route('profile.template.edit') }}" class="nav-link {{ request()->routeIs('profile.template.*') ? 'active' : '' }}">
                                <i class="bi bi-palette"></i> <span>{{ __('Template') }}</span>
                            </a>
                        @endunless
                    </nav>
                </aside>
            @else
                <aside class="app-sidebar flex flex-col p-3">
                    <a class="flex items-center gap-2 px-1 mb-2 font-display font-semibold text-white no-underline" href="{{ route('client.dashboard') }}">
                        <x-application-logo class="w-7 h-7 shrink-0" style="fill: currentColor;" />
                        <span class="sidebar-label">{{ config('app.name', 'Arcn Panel') }}</span>
                    </a>

                    <button type="button" id="sidebar-toggle" class="flex items-center gap-2 px-1 mb-3 py-1 text-sm text-white/50 hover:text-white" title="{{ __('Expandir/retrair menu') }}">
                        <i class="bi bi-chevron-double-left"></i>
                        <span class="sidebar-label">{{ __('Retrair menu') }}</span>
                    </button>

                    @php
                        $navItems = [
                            'Site' => [
                                ['files.index', 'bi-folder2-open', 'Arquivos'],
                                ['php.index', 'bi-filetype-php', 'PHP'],
                                ['protected-folders.index', 'bi-shield-lock', 'Proteção de pasta'],
                                ['redirects.index', 'bi-signpost-split', 'Redirecionamentos'],
                                ['hotlink-protection.index', 'bi-link-45deg', 'Proteção Hotlink'],
                                ['malware.index', 'bi-bug', 'Malware'],
                                ['logs.index', 'bi-file-text', 'Logs'],
                                ['apps.index', 'bi-cpu', 'Apps'],
                                ['installer.index', 'bi-box-seam', 'Instalador'],
                                ['mime-types.index', 'bi-file-earmark-code', 'MIME Types'],
                            ],
                            'Domínio' => [
                                ['dns.index', 'bi-globe2', 'DNS'],
                                ['mail.index', 'bi-envelope', 'E-mail'],
                                ['mail-log.index', 'bi-envelope-paper', 'Rastrear e-mails'],
                            ],
                            'Avançado' => [
                                ['ssh.index', 'bi-terminal', 'SSH'],
                                ['cron.index', 'bi-clock-history', 'Cron'],
                                ['ftp.index', 'bi-hdd-network', 'FTP'],
                                ['resources.index', 'bi-speedometer2', 'Recursos'],
                            ],
                        ];
                    @endphp

                    <nav class="flex flex-col flex-1 overflow-y-auto overflow-x-hidden">
                        @foreach ($navItems as $section => $items)
                            <div class="app-sidebar-section sidebar-label">{{ __($section) }}</div>
                            @foreach ($items as [$routeSuffix, $icon, $label])
                                @php $routeName = 'client.hosting-accounts.'.$routeSuffix; @endphp
                                @if ($navAccount)
                                    <a href="{{ route($routeName, $navAccount) }}" class="nav-link {{ request()->routeIs('client.hosting-accounts.'.explode('.', $routeSuffix)[0].'.*') ? 'active' : '' }}" title="{{ __($label) }}">
                                        <i class="bi {{ $icon }}"></i> <span class="sidebar-label">{{ __($label) }}</span>
                                    </a>
                                @else
                                    <span class="nav-link text-secondary" style="opacity: .5;" title="{{ __('Disponível assim que sua hospedagem for provisionada.') }}">
                                        <i class="bi {{ $icon }}"></i> <span class="sidebar-label">{{ __($label) }}</span>
                                    </span>
                                @endif
                            @endforeach
                        @endforeach

                        <div class="app-sidebar-section sidebar-label">{{ __('Suporte') }}</div>
                        <a href="{{ route('client.tickets.index') }}" class="nav-link {{ request()->routeIs('client.tickets.*') ? 'active' : '' }}" title="{{ __('Chamados') }}">
                            <i class="bi bi-life-preserver"></i> <span class="sidebar-label">{{ __('Chamados') }}</span>
                        </a>

                        @unless (auth()->user()->ui_template_locked)
                            <a href="{{ route('profile.template.edit') }}" class="nav-link {{ request()->routeIs('profile.template.*') ? 'active' : '' }}" title="{{ __('Template') }}">
                                <i class="bi bi-palette"></i> <span class="sidebar-label">{{ __('Template') }}</span>
                            </a>
                        @endunless
                    </nav>

                    @include('layouts.partials.sidebar-user')
                </aside>
            @endif

            <div class="sidebar-backdrop" id="sidebar-backdrop"></div>

            <div class="flex flex-col flex-1 min-w-0">
                @if ($uiTemplate === 'cpanel')
                    <header class="cpanel-topbar">
                        <div class="flex items-center justify-between gap-3 px-4 md:px-6 py-3">
                            <button type="button" id="mobile-nav-toggle" class="mobile-nav-toggle shrink-0 items-center justify-center w-9 h-9 rounded border border-line-strong text-text-dim hover:border-accent hover:text-accent" title="{{ __('Abrir menu') }}">
                                <i class="bi bi-list" style="font-size: 1.1rem;"></i>
                            </button>

                            <div class="flex-1 min-w-0 relative">
                                @if ($navAccount instanceof \App\Domain\Hosting\Models\HostingAccount && ! request()->routeIs('*.hosting-accounts.show') && ! request()->routeIs('client.dashboard'))
                                    <a href="{{ route('client.hosting-accounts.show', $navAccount) }}"
                                       class="inline-flex items-center gap-1 text-sm text-text-dim hover:text-accent no-underline mb-1">
                                        <i class="bi bi-arrow-left"></i> {{ $navAccount->primary_domain }}
                                    </a>
                                @else
                                    <div class="relative" style="max-width: 22rem;">
                                        <i class="bi bi-search" style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); color: var(--color-text-faint); font-size: 0.85rem;"></i>
                                        <input type="search" id="cpanel-tool-search" class="cpanel-search" placeholder="{{ __('Buscar ferramentas') }}" autocomplete="off">
                                    </div>
                                @endif
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                @php
                                    $hasAnnouncements = \App\Models\Announcement::active()->forAudience('client')->exists();
                                @endphp
                                <a href="{{ route('client.tickets.index') }}" class="cpanel-topbar-icon" title="{{ __('Chamados') }}">
                                    <i class="bi bi-bell"></i>
                                    @if ($hasAnnouncements)
                                        <span class="cpanel-notif-dot"></span>
                                    @endif
                                </a>

                                <x-dropdown>
                                    <x-slot name="trigger">
                                        <span class="cpanel-topbar-icon cursor-pointer">
                                            <i class="bi bi-person"></i>
                                        </span>
                                    </x-slot>

                                    <x-slot name="content">
                                        <x-dropdown-link :href="route('profile.edit')">
                                            <i class="bi bi-person"></i> {{ __('Perfil') }}
                                        </x-dropdown-link>

                                        <li>
                                            <form method="POST" action="{{ route('logout') }}">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    <i class="bi bi-box-arrow-right"></i> {{ __('Sair') }}
                                                </button>
                                            </form>
                                        </li>
                                    </x-slot>
                                </x-dropdown>
                            </div>
                        </div>
                    </header>
                @else
                    <header class="border-b border-line bg-panel">
                        <div class="flex items-center justify-between gap-3 px-4 md:px-6 py-3">
                            <button type="button" id="mobile-nav-toggle" class="mobile-nav-toggle shrink-0 items-center justify-center w-9 h-9 rounded border border-line-strong text-text-dim hover:border-accent hover:text-accent" title="{{ __('Abrir menu') }}">
                                <i class="bi bi-list" style="font-size: 1.1rem;"></i>
                            </button>

                            <div class="flex-1 min-w-0">
                                {{-- $navAccount já foi resolvido lá em cima, antes da sidebar
                                     (mesma fonte, reaproveitada aqui pro back-link). --}}
                                @if ($navAccount instanceof \App\Domain\Hosting\Models\HostingAccount && ! request()->routeIs('*.hosting-accounts.show'))
                                    <a href="{{ route('client.hosting-accounts.show', $navAccount) }}"
                                       class="inline-flex items-center gap-1 text-sm text-text-dim hover:text-accent no-underline mb-1">
                                        <i class="bi bi-arrow-left"></i> {{ $navAccount->primary_domain }}
                                    </a>
                                @endif
                                @isset($header)
                                    {{ $header }}
                                @endisset
                            </div>

                            <button type="button" id="theme-toggle" class="shrink-0 inline-flex items-center justify-center w-9 h-9 rounded border border-line-strong text-text-dim hover:border-accent hover:text-accent" title="{{ __('Alternar tema claro/escuro') }}">
                                <i class="bi bi-moon-stars theme-icon-light"></i>
                                <i class="bi bi-sun theme-icon-dark"></i>
                            </button>
                        </div>
                    </header>
                @endif

                <main class="flex-1 px-4 md:px-6 py-5">
                    @include('layouts.partials.announcements')

                    @isset($header)
                        @if ($uiTemplate === 'cpanel')
                            <div class="mb-3">{{ $header }}</div>
                        @endif
                    @endisset

                    {{ $slot }}
                </main>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>

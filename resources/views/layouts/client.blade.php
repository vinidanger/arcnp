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
        <div class="flex min-h-screen">
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
                    // Mesma fonte usada pelo back-link do header, logo
                    // abaixo — na maioria das páginas (as ~80 rotas
                    // aninhadas em hosting-accounts/{hosting_account}/...)
                    // já vem pronto da própria rota, sem query extra.
                    // Só cai pro fallback nas poucas páginas "soltas"
                    // (chamados, a home de espera).
                    $navAccount = request()->route('hosting_account') ?? auth()->user()->hostingAccount;

                    $navItems = [
                        'Site' => [
                            ['files.index', 'bi-folder2-open', 'Arquivos'],
                            ['php.index', 'bi-filetype-php', 'PHP'],
                            ['protected-folders.index', 'bi-shield-lock', 'Proteção de pasta'],
                            ['redirects.index', 'bi-signpost-split', 'Redirecionamentos'],
                            ['hotlink-protection.index', 'bi-link-45deg', 'Proteção Hotlink'],
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
                </nav>

                @include('layouts.partials.sidebar-user')
            </aside>

            <div class="flex flex-col flex-1 min-w-0">
                <header class="border-b border-line bg-panel">
                    <div class="flex items-center justify-between gap-3 px-4 md:px-6 py-3">
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

                <main class="flex-1 px-4 md:px-6 py-5">
                    @include('layouts.partials.announcements')

                    {{ $slot }}
                </main>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @include('layouts.partials.theme-init')

        <title>{{ config('app.name', 'Arcn Panel') }} — Admin</title>

        @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/file-manager.js'])
    </head>
    <body class="min-h-screen">
        <div class="flex min-h-screen">
            <aside class="app-sidebar flex flex-col p-3">
                <a class="flex items-center gap-2 px-1 mb-2 font-display font-semibold text-white no-underline" href="{{ route('admin.dashboard') }}">
                    <x-application-logo class="w-7 h-7 shrink-0" style="fill: currentColor;" />
                    <span class="sidebar-label">{{ config('app.name', 'Arcn Panel') }}</span>
                </a>

                <button type="button" id="sidebar-toggle" class="flex items-center gap-2 px-1 mb-3 py-1 text-sm text-white/50 hover:text-white" title="{{ __('Expandir/retrair menu') }}">
                    <i class="bi bi-chevron-double-left"></i>
                    <span class="sidebar-label">{{ __('Retrair menu') }}</span>
                </button>

                <nav class="flex flex-col flex-1 overflow-y-auto overflow-x-hidden">
                    <div class="app-sidebar-section sidebar-label">{{ __('Geral') }}</div>
                    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" title="{{ __('Dashboard') }}">
                        <i class="bi bi-speedometer2"></i> <span class="sidebar-label">{{ __('Dashboard') }}</span>
                    </a>

                    <div class="app-sidebar-section sidebar-label">{{ __('Clientes e hospedagem') }}</div>
                    <a href="{{ route('admin.clients.index') }}" class="nav-link {{ request()->routeIs('admin.clients.*') ? 'active' : '' }}" title="{{ __('Clientes') }}">
                        <i class="bi bi-people"></i> <span class="sidebar-label">{{ __('Clientes') }}</span>
                    </a>
                    <a href="{{ route('admin.hosting-accounts.index') }}" class="nav-link {{ request()->routeIs('admin.hosting-accounts.*') ? 'active' : '' }}" title="{{ __('Hospedagens') }}">
                        <i class="bi bi-hdd-stack"></i> <span class="sidebar-label">{{ __('Hospedagens') }}</span>
                    </a>
                    <a href="{{ route('admin.tickets.index') }}" class="nav-link {{ request()->routeIs('admin.tickets.*') ? 'active' : '' }}" title="{{ __('Chamados') }}">
                        <i class="bi bi-life-preserver"></i> <span class="sidebar-label">{{ __('Chamados') }}</span>
                    </a>
                    <a href="{{ route('admin.plans.index') }}" class="nav-link {{ request()->routeIs('admin.plans.*') ? 'active' : '' }}" title="{{ __('Planos') }}">
                        <i class="bi bi-box-seam"></i> <span class="sidebar-label">{{ __('Planos') }}</span>
                    </a>

                    <div class="app-sidebar-section sidebar-label">{{ __('Infraestrutura') }}</div>
                    <a href="{{ route('admin.servers.index') }}" class="nav-link {{ request()->routeIs('admin.servers.*') ? 'active' : '' }}" title="{{ __('Servidores') }}">
                        <i class="bi bi-server"></i> <span class="sidebar-label">{{ __('Servidores') }}</span>
                    </a>

                    <div class="app-sidebar-section sidebar-label">{{ __('Sistema') }}</div>
                    <a href="{{ route('admin.announcements.index') }}" class="nav-link {{ request()->routeIs('admin.announcements.*') ? 'active' : '' }}" title="{{ __('Mensagens do sistema') }}">
                        <i class="bi bi-megaphone"></i> <span class="sidebar-label">{{ __('Mensagens do sistema') }}</span>
                    </a>
                    <a href="{{ route('admin.api-clients.index') }}" class="nav-link {{ request()->routeIs('admin.api-clients.*') ? 'active' : '' }}" title="{{ __('Integrações de API') }}">
                        <i class="bi bi-plug"></i> <span class="sidebar-label">{{ __('Integrações de API') }}</span>
                    </a>
                    <a href="{{ route('admin.settings.edit') }}" class="nav-link {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}" title="{{ __('Configurações') }}">
                        <i class="bi bi-gear"></i> <span class="sidebar-label">{{ __('Configurações') }}</span>
                    </a>
                    <a href="{{ route('admin.audit-logs.index') }}" class="nav-link {{ request()->routeIs('admin.audit-logs.*') ? 'active' : '' }}" title="{{ __('Log de auditoria') }}">
                        <i class="bi bi-journal-text"></i> <span class="sidebar-label">{{ __('Log de auditoria') }}</span>
                    </a>
                </nav>

                @include('layouts.partials.sidebar-user')
            </aside>

            <div class="sidebar-backdrop" id="sidebar-backdrop"></div>

            <div class="flex flex-col flex-1 min-w-0">
                <header class="border-b border-line bg-panel">
                    <div class="flex items-center justify-between gap-3 px-4 md:px-6 py-3">
                        <button type="button" id="mobile-nav-toggle" class="mobile-nav-toggle shrink-0 items-center justify-center w-9 h-9 rounded border border-line-strong text-text-dim hover:border-accent hover:text-accent" title="{{ __('Abrir menu') }}">
                            <i class="bi bi-list" style="font-size: 1.1rem;"></i>
                        </button>

                        <div class="flex-1 min-w-0">
                            {{-- Qualquer rota com {hosting_account} (SSH, PHP, FTP, Mail,
                                 Recursos, etc.) ganha esse link de volta automaticamente — sem
                                 isso, a única forma de voltar era passar pela lista de
                                 hospedagens de novo e reselecionar a conta. Lido direto do
                                 parâmetro da rota (já resolvido pelo binding do Laravel antes da
                                 view renderizar), não da variável $account da view — um
                                 <x-admin-layout> é um componente Blade à parte, não herda
                                 variável nenhuma da view que o chama a menos que seja passada
                                 explicitamente. Escondido na própria página da conta (senão vira
                                 link pra ela mesma). --}}
                            @php $navAccount = request()->route('hosting_account'); @endphp
                            @if ($navAccount instanceof \App\Domain\Hosting\Models\HostingAccount && ! request()->routeIs('*.hosting-accounts.show'))
                                <a href="{{ route('admin.hosting-accounts.show', $navAccount) }}"
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

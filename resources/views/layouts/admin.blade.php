<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @include('layouts.partials.theme-init')

        <title>{{ config('app.name', 'Arcn Panel') }} — Admin</title>

        @vite(['resources/css/app.scss', 'resources/js/app.js', 'resources/js/file-manager.js'])
    </head>
    <body>
        <div class="d-flex" style="min-height: 100vh;">
            <aside class="app-sidebar p-3 d-flex flex-column">
                <a class="app-sidebar-brand d-flex align-items-center gap-2 text-decoration-none mb-2" href="{{ route('admin.dashboard') }}">
                    <x-application-logo style="width: 1.75rem; height: 1.75rem; fill: currentColor; flex-shrink: 0;" />
                    <span class="fw-semibold sidebar-label">{{ config('app.name', 'Arcn Panel') }}</span>
                </a>

                <button type="button" id="sidebar-toggle" class="btn btn-sm btn-link text-white-50 d-flex align-items-center gap-2 px-0 mb-3 text-decoration-none" title="{{ __('Expandir/retrair menu') }}">
                    <i class="bi bi-chevron-double-left"></i>
                    <span class="sidebar-label small">{{ __('Retrair menu') }}</span>
                </button>

                <nav class="nav flex-column flex-grow-1 overflow-auto">
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
                </nav>

                @include('layouts.partials.sidebar-user')
            </aside>

            <div class="flex-grow-1 d-flex flex-column">
                <header class="bg-body border-bottom">
                    <div class="container-fluid px-4 py-3 d-flex align-items-center justify-content-between gap-3">
                        <div class="flex-grow-1" style="min-width: 0;">
                            @isset($header)
                                {{ $header }}
                            @endisset
                        </div>

                        <button type="button" id="theme-toggle" class="btn btn-sm btn-outline-secondary flex-shrink-0" title="{{ __('Alternar tema claro/escuro') }}">
                            <i class="bi bi-moon-stars theme-icon-light"></i>
                            <i class="bi bi-sun theme-icon-dark"></i>
                        </button>
                    </div>
                </header>

                <main class="flex-grow-1 container-fluid px-4 py-4">
                    @include('layouts.partials.announcements')

                    {{ $slot }}
                </main>
            </div>
        </div>

        @stack('scripts')
    </body>
</html>

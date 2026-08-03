<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Arcn Panel') }} — Admin</title>

        @vite(['resources/css/app.scss', 'resources/js/app.js'])
    </head>
    <body>
        <div class="d-flex" style="min-height: 100vh;">
            <aside class="app-sidebar p-3">
                <a class="app-sidebar-brand d-flex align-items-center gap-2 text-decoration-none mb-2" href="{{ route('admin.dashboard') }}">
                    <x-application-logo style="width: 1.75rem; height: 1.75rem; fill: currentColor;" />
                    <span class="fw-semibold">{{ config('app.name', 'Arcn Panel') }}</span>
                </a>

                <nav class="nav flex-column">
                    <div class="app-sidebar-section">{{ __('Geral') }}</div>
                    <a href="{{ route('admin.dashboard') }}" class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i> {{ __('Dashboard') }}
                    </a>

                    <div class="app-sidebar-section">{{ __('Clientes e hospedagem') }}</div>
                    <a href="{{ route('admin.clients.index') }}" class="nav-link {{ request()->routeIs('admin.clients.*') ? 'active' : '' }}">
                        <i class="bi bi-people"></i> {{ __('Clientes') }}
                    </a>
                    <a href="{{ route('admin.hosting-accounts.index') }}" class="nav-link {{ request()->routeIs('admin.hosting-accounts.*') ? 'active' : '' }}">
                        <i class="bi bi-hdd-stack"></i> {{ __('Hospedagens') }}
                    </a>
                    <a href="{{ route('admin.plans.index') }}" class="nav-link {{ request()->routeIs('admin.plans.*') ? 'active' : '' }}">
                        <i class="bi bi-box-seam"></i> {{ __('Planos') }}
                    </a>

                    <div class="app-sidebar-section">{{ __('Infraestrutura') }}</div>
                    <a href="{{ route('admin.servers.index') }}" class="nav-link {{ request()->routeIs('admin.servers.*') ? 'active' : '' }}">
                        <i class="bi bi-server"></i> {{ __('Servidores') }}
                    </a>

                    <div class="app-sidebar-section">{{ __('Sistema') }}</div>
                    <a href="{{ route('admin.api-clients.index') }}" class="nav-link {{ request()->routeIs('admin.api-clients.*') ? 'active' : '' }}">
                        <i class="bi bi-plug"></i> {{ __('Integrações de API') }}
                    </a>
                </nav>
            </aside>

            <div class="flex-grow-1 d-flex flex-column">
                @include('layouts.partials.topbar')

                @isset($header)
                    <header class="bg-white border-bottom">
                        <div class="container-fluid px-4 py-3">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <main class="flex-grow-1 container-fluid px-4 py-4">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>

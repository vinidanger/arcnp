<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Arcn Panel') }}</title>

        @vite(['resources/css/app.scss', 'resources/js/app.js'])
    </head>
    <body>
        <div class="d-flex" style="min-height: 100vh;">
            <aside class="app-sidebar p-3">
                <a class="app-sidebar-brand d-flex align-items-center gap-2 text-decoration-none mb-2" href="{{ route('client.dashboard') }}">
                    <x-application-logo style="width: 1.75rem; height: 1.75rem; fill: currentColor;" />
                    <span class="fw-semibold">{{ config('app.name', 'Arcn Panel') }}</span>
                </a>

                <nav class="nav flex-column">
                    <div class="app-sidebar-section">{{ __('Geral') }}</div>
                    <a href="{{ route('client.dashboard') }}" class="nav-link {{ request()->routeIs('client.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i> {{ __('Dashboard') }}
                    </a>

                    <div class="app-sidebar-section">{{ __('Hospedagem') }}</div>
                    <a href="{{ route('client.hosting-accounts.index') }}" class="nav-link {{ request()->routeIs('client.hosting-accounts.*') ? 'active' : '' }}">
                        <i class="bi bi-hdd-stack"></i> {{ __('Minhas hospedagens') }}
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

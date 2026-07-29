<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Arcn Panel') }} — Admin</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="d-flex" style="min-height: 100vh;">
            <aside class="bg-dark text-white p-3" style="width: 240px; flex-shrink: 0;">
                <a class="d-flex align-items-center gap-2 text-white text-decoration-none mb-4" href="{{ route('admin.dashboard') }}">
                    <x-application-logo style="width: 1.75rem; height: 1.75rem; fill: currentColor;" />
                    <span class="fw-semibold">{{ config('app.name', 'Arcn Panel') }}</span>
                </a>

                <ul class="nav nav-pills flex-column gap-1">
                    <li class="nav-item">
                        <a href="{{ route('admin.dashboard') }}" class="nav-link text-white {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                            {{ __('Dashboard') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.servers.index') }}" class="nav-link text-white {{ request()->routeIs('admin.servers.*') ? 'active' : '' }}">
                            {{ __('Servidores') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.hosting-accounts.index') }}" class="nav-link text-white {{ request()->routeIs('admin.hosting-accounts.*') ? 'active' : '' }}">
                            {{ __('Hospedagens') }}
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('admin.plans.index') }}" class="nav-link text-white {{ request()->routeIs('admin.plans.*') ? 'active' : '' }}">
                            {{ __('Planos') }}
                        </a>
                    </li>
                </ul>
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

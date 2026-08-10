<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @include('layouts.partials.theme-init')

        <title>{{ config('app.name', 'Arcn Panel') }} — {{ __('Documentação da API') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen">
        <div class="flex flex-col min-h-screen">
            <header class="border-b border-line bg-panel">
                <div class="flex items-center justify-between gap-3 px-4 md:px-6 py-3">
                    <a href="/" class="flex items-center gap-2 no-underline text-text">
                        <x-application-logo class="w-7 h-7 shrink-0" style="fill: currentColor;" />
                        <span class="font-display font-semibold">{{ config('app.name', 'Arcn Panel') }}</span>
                    </a>

                    <button type="button" id="theme-toggle" class="shrink-0 inline-flex items-center justify-center w-9 h-9 rounded border border-line-strong text-text-dim hover:border-accent hover:text-accent" title="{{ __('Alternar tema claro/escuro') }}">
                        <i class="bi bi-moon-stars theme-icon-light"></i>
                        <i class="bi bi-sun theme-icon-dark"></i>
                    </button>
                </div>
            </header>

            <main class="flex-1 px-4 md:px-6 py-5" style="max-width: 60rem; margin: 0 auto; width: 100%;">
                {{ $slot }}
            </main>
        </div>

        @stack('scripts')
    </body>
</html>

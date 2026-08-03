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
        <div class="min-vh-100 d-flex flex-column justify-content-center align-items-center py-4">
            <div class="mb-4">
                <a href="/" class="text-decoration-none">
                    <x-application-logo style="width: 3rem; height: 3rem; fill: currentColor;" class="text-secondary" />
                </a>
            </div>

            <div class="card shadow-sm w-100" style="max-width: 28rem;">
                <div class="card-body p-4">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>

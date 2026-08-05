<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        @include('layouts.partials.theme-init')

        <title>{{ config('app.name', 'Arcn Panel') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="min-h-screen bg-ink">
        <div class="min-h-screen flex flex-col items-center justify-center py-8 px-4">
            <a href="/" class="mb-6 no-underline">
                <x-application-logo class="w-10 h-10 text-text-dim" style="fill: currentColor;" />
            </a>

            <div class="panel w-full" style="max-width: 26rem;">
                <div class="p-6">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>

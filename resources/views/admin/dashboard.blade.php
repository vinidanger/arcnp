<x-admin-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Dashboard') }}</h1>
    </x-slot>

    <div class="card">
        <div class="card-body">
            {{ __('Bem-vindo, :name.', ['name' => auth()->user()->name]) }}
        </div>
    </div>
</x-admin-layout>

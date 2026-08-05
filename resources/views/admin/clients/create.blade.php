<x-admin-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Novo cliente') }}</h1>
    </x-slot>

    <div class="card" style="max-width: 32rem;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.clients.store') }}">
                @csrf

                <div class="mb-3">
                    <x-input-label for="name" value="{{ __('Nome') }}" />
                    <x-text-input id="name" name="name" type="text" :value="old('name')" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div class="mb-3">
                    <x-input-label for="email" value="{{ __('E-mail (opcional, só contato)') }}" />
                    <x-text-input id="email" name="email" type="email" :value="old('email')" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <button type="submit" class="btn btn-primary">{{ __('Criar') }}</button>
            </form>
        </div>
    </div>
</x-admin-layout>

<x-admin-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Nova credencial de API') }}</h1>
    </x-slot>

    <div class="card" style="max-width: 40rem;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.api-clients.store') }}">
                @csrf

                <div class="mb-3">
                    <x-input-label for="name" value="{{ __('Nome') }}" />
                    <x-text-input id="name" name="name" type="text" :value="old('name')" placeholder="Painel de vendas" required autofocus />
                    <div class="form-text">{{ __('Só pra você identificar depois quem/o que usa esse token.') }}</div>
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <button type="submit" class="btn btn-primary">{{ __('Gerar credencial') }}</button>
                <a href="{{ route('admin.api-clients.index') }}" class="btn btn-outline-secondary">{{ __('Cancelar') }}</a>
            </form>
        </div>
    </div>
</x-admin-layout>

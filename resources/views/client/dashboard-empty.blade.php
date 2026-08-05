<x-client-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Bem-vindo') }}</h1>
    </x-slot>

    <div class="panel p-6 text-center">
        <i class="bi bi-hourglass-split text-3xl text-text-dim"></i>
        <h2 class="h5 mt-3 mb-1">{{ __('Sua hospedagem está sendo preparada') }}</h2>
        <p class="text-sm text-text-dim mb-0">
            {{ __('Assim que estiver pronta, você já cai direto nela ao entrar no painel.') }}
        </p>
    </div>
</x-client-layout>

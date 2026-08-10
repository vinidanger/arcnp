<x-admin-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">{{ __('Documentação da API') }}</h1>
            <a href="{{ route('admin.api-clients.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('Voltar') }}</a>
        </div>
    </x-slot>

    @include('api-docs._content')
</x-admin-layout>

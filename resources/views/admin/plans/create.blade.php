<x-admin-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Novo plano') }}</h1>
    </x-slot>

    <div class="card" style="max-width: 52rem;">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.plans.store') }}">
                @csrf
                @include('admin.plans._form')
                <hr class="my-4">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ __('Criar plano') }}</button>
                    <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-secondary">{{ __('Cancelar') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>

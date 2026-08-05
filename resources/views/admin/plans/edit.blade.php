<x-admin-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Editar plano') }}</h1>
    </x-slot>

    <div class="card">
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.plans.update', $plan) }}">
                @csrf
                @method('PUT')
                @include('admin.plans._form')
                <hr class="my-4">
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ __('Salvar') }}</button>
                    <a href="{{ route('admin.plans.index') }}" class="btn btn-outline-secondary">{{ __('Cancelar') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>

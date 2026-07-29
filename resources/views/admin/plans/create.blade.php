<x-admin-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Novo plano') }}</h1>
    </x-slot>

    <div class="card" style="max-width: 32rem;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.plans.store') }}">
                @csrf
                @include('admin.plans._form')
                <button type="submit" class="btn btn-primary">{{ __('Criar') }}</button>
            </form>
        </div>
    </div>
</x-admin-layout>

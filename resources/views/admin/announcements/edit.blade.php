<x-admin-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Editar mensagem') }}</h1>
    </x-slot>

    <div class="card" style="max-width: 40rem;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.announcements.update', $announcement) }}">
                @csrf
                @method('PUT')
                @include('admin.announcements._form')
                <button type="submit" class="btn btn-primary">{{ __('Salvar') }}</button>
                <a href="{{ route('admin.announcements.index') }}" class="btn btn-outline-secondary">{{ __('Cancelar') }}</a>
            </form>
        </div>
    </div>
</x-admin-layout>

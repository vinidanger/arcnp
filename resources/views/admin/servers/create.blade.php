<x-admin-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Novo servidor') }}</h1>
    </x-slot>

    <div class="card" style="max-width: 40rem;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.servers.store') }}">
                @csrf
                @include('admin.servers._form')

                <button type="submit" class="btn btn-primary">{{ __('Cadastrar') }}</button>
            </form>
        </div>
    </div>
</x-admin-layout>

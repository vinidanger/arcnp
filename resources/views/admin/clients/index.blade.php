<x-admin-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">{{ __('Clientes') }}</h1>
            <a href="{{ route('admin.clients.create') }}" class="btn btn-primary btn-sm">{{ __('Novo cliente') }}</a>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Nome') }}</th>
                        <th>{{ __('E-mail') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($clients as $client)
                        <tr>
                            <td>{{ $client->name }}</td>
                            <td>{{ $client->email }}</td>
                            <td>
                                <span class="badge text-bg-{{ $client->status === 'active' ? 'success' : 'secondary' }}">
                                    {{ $client->status }}
                                </span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.clients.edit', $client) }}" class="btn btn-sm btn-outline-secondary">{{ __('Editar') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-secondary py-4">{{ __('Nenhum cliente cadastrado.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $clients->links() }}
    </div>
</x-admin-layout>

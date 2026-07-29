<x-admin-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">{{ __('Servidores') }}</h1>
            <a href="{{ route('admin.servers.create') }}" class="btn btn-primary btn-sm">
                {{ __('Novo servidor') }}
            </a>
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
                        <th>{{ __('IP') }}</th>
                        <th>{{ __('SO') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Último heartbeat') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($servers as $server)
                        <tr>
                            <td>
                                <a href="{{ route('admin.servers.show', $server) }}">{{ $server->name }}</a>
                            </td>
                            <td>{{ $server->ip_address }}</td>
                            <td>{{ $server->os ?? '—' }}</td>
                            <td>
                                @php
                                    $badge = match ($server->agent_status) {
                                        'online' => 'success',
                                        'offline' => 'danger',
                                        default => 'secondary',
                                    };
                                @endphp
                                <span class="badge text-bg-{{ $badge }}">{{ $server->agent_status }}</span>
                            </td>
                            <td>{{ $server->last_heartbeat_at?->diffForHumans() ?? '—' }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.servers.edit', $server) }}" class="btn btn-sm btn-outline-secondary">{{ __('Editar') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-4">
                                {{ __('Nenhum servidor cadastrado ainda.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $servers->links() }}
    </div>
</x-admin-layout>

<x-client-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">{{ __('Chamados') }}</h1>
            <a href="{{ route('client.tickets.create') }}" class="btn btn-primary btn-sm">{{ __('Novo chamado') }}</a>
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
                        <th>{{ __('Assunto') }}</th>
                        <th>{{ __('Conta') }}</th>
                        <th>{{ __('Prioridade') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Atualizado') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tickets as $ticket)
                        <tr>
                            <td><a href="{{ route('client.tickets.show', $ticket) }}">{{ $ticket->subject }}</a></td>
                            <td>{{ $ticket->hostingAccount?->primary_domain ?? '—' }}</td>
                            <td>
                                @php
                                    $priorityBadge = match ($ticket->priority) {
                                        'high' => 'danger',
                                        'low' => 'secondary',
                                        default => 'info',
                                    };
                                @endphp
                                <span class="badge text-bg-{{ $priorityBadge }}">{{ $ticket->priority }}</span>
                            </td>
                            <td>
                                @php
                                    $statusBadge = match ($ticket->status) {
                                        'open' => 'warning',
                                        'answered' => 'success',
                                        default => 'secondary',
                                    };
                                @endphp
                                <span class="badge text-bg-{{ $statusBadge }}">{{ $ticket->status }}</span>
                            </td>
                            <td class="small text-secondary">{{ $ticket->updated_at->diffForHumans() }}</td>
                            <td class="text-end">
                                <a href="{{ route('client.tickets.show', $ticket) }}" class="btn btn-sm btn-outline-secondary">{{ __('Ver') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-4">{{ __('Nenhum chamado ainda.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $tickets->links() }}
    </div>
</x-client-layout>

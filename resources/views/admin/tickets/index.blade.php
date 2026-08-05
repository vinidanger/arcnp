<x-admin-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Chamados') }}</h1>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-auto">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">{{ __('Todos os status') }}</option>
                        @foreach (['open' => 'Aberto', 'answered' => 'Respondido', 'closed' => 'Fechado'] as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ __($label) }}</option>
                        @endforeach
                    </select>
                </div>
                @if ($status)
                    <div class="col-auto">
                        <a href="{{ route('admin.tickets.index') }}" class="btn btn-sm btn-link">{{ __('Limpar') }}</a>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Assunto') }}</th>
                        <th>{{ __('Cliente') }}</th>
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
                            <td><a href="{{ route('admin.tickets.show', $ticket) }}">{{ $ticket->subject }}</a></td>
                            <td>{{ $ticket->user->name }}</td>
                            <td>{{ $ticket->hostingAccount?->primary_domain ?? '—' }}</td>
                            <td>
                                @php
                                    $priorityBadge = match ($ticket->priority) {
                                        'high' => 'danger',
                                        'low' => 'secondary',
                                        default => 'info',
                                    };
                                @endphp
                                <span class="badge text-bg-{{ $priorityBadge }}">{{ status_label($ticket->priority) }}</span>
                            </td>
                            <td>
                                @php
                                    $statusBadge = match ($ticket->status) {
                                        'open' => 'warning',
                                        'answered' => 'success',
                                        default => 'secondary',
                                    };
                                @endphp
                                <span class="badge text-bg-{{ $statusBadge }}">{{ status_label($ticket->status) }}</span>
                            </td>
                            <td class="small text-secondary">{{ $ticket->updated_at->diffForHumans() }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.tickets.show', $ticket) }}" class="btn btn-sm btn-outline-secondary">{{ __('Ver') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-4">{{ __('Nenhum chamado ainda.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $tickets->links() }}
    </div>
</x-admin-layout>

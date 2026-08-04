<x-admin-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0 text-break">{{ $ticket->subject }}</h1>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body py-2 d-flex flex-wrap gap-3 align-items-center small text-secondary">
            <span>{{ __('Cliente') }}: <strong class="text-body">{{ $ticket->user->name }}</strong></span>
            <span>{{ __('Conta') }}: <strong class="text-body">{{ $ticket->hostingAccount?->primary_domain ?? '—' }}</strong></span>
            <span>{{ __('Prioridade') }}: <strong class="text-body">{{ $ticket->priority }}</strong></span>
            <span>{{ __('Status') }}: <strong class="text-body">{{ $ticket->status }}</strong></span>
            <div class="ms-auto d-flex gap-2">
                @if ($ticket->status !== 'closed')
                    <form method="POST" action="{{ route('admin.tickets.close', $ticket) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('Fechar chamado') }}</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.tickets.reopen', $ticket) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('Reabrir') }}</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    @foreach ($ticket->messages as $message)
        <div class="card mb-2 {{ $message->user->isAdmin() ? 'border-primary-subtle' : '' }}">
            <div class="card-body py-2">
                <div class="d-flex justify-content-between small text-secondary mb-1">
                    <strong class="text-body">{{ $message->user->name }} {{ $message->user->isAdmin() ? '(admin)' : '' }}</strong>
                    <span>{{ $message->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div style="white-space: pre-wrap;">{{ $message->body }}</div>
            </div>
        </div>
    @endforeach

    @if ($ticket->status !== 'closed')
        <div class="card mt-3">
            <div class="card-body">
                <form method="POST" action="{{ route('admin.tickets.messages.store', $ticket) }}">
                    @csrf
                    <textarea name="body" rows="4" class="form-control" required></textarea>
                    <x-input-error :messages="$errors->get('body')" class="mt-2" />
                    <button type="submit" class="btn btn-primary mt-2">{{ __('Responder') }}</button>
                </form>
            </div>
        </div>
    @else
        <div class="alert alert-secondary mt-3">{{ __('Esse chamado está fechado.') }}</div>
    @endif
</x-admin-layout>

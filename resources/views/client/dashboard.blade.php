<x-client-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Dashboard') }}</h1>
    </x-slot>

    <div class="card mb-3">
        <div class="card-body">
            {{ __('Bem-vindo, :name.', ['name' => auth()->user()->name]) }}
        </div>
    </div>

    @php $accounts = auth()->user()->hostingAccounts()->with('plan')->latest()->get(); @endphp

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h6 mb-0">{{ __('Minhas hospedagens') }}</h2>
                <a href="{{ route('client.hosting-accounts.index') }}" class="btn btn-sm btn-outline-primary">{{ __('Ver todas') }}</a>
            </div>

            @forelse ($accounts->take(5) as $account)
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <div>
                        <a href="{{ route('client.hosting-accounts.show', $account) }}">{{ $account->primary_domain }}</a>
                        <div class="small text-secondary">{{ $account->plan->name }}</div>
                    </div>
                    @php
                        $badge = match ($account->status) {
                            'active' => 'success',
                            'suspended' => 'warning',
                            'error' => 'danger',
                            default => 'secondary',
                        };
                    @endphp
                    <span class="badge text-bg-{{ $badge }}">{{ $account->status }}</span>
                </div>
            @empty
                <p class="small text-secondary mb-0">{{ __('Você ainda não tem nenhuma hospedagem.') }}</p>
            @endforelse
        </div>
    </div>
</x-client-layout>

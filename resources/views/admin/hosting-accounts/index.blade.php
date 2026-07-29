<x-admin-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">{{ __('Contas de hospedagem') }}</h1>
            <a href="{{ route('admin.hosting-accounts.create') }}" class="btn btn-primary btn-sm">
                {{ __('Nova conta') }}
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
                        <th>{{ __('Domínio') }}</th>
                        <th>{{ __('Cliente') }}</th>
                        <th>{{ __('Servidor') }}</th>
                        <th>{{ __('Plano') }}</th>
                        <th>{{ __('Username') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($accounts as $account)
                        <tr>
                            <td><a href="{{ route('admin.hosting-accounts.show', $account) }}">{{ $account->primary_domain }}</a></td>
                            <td>{{ $account->client->name }}</td>
                            <td>{{ $account->server->name }}</td>
                            <td>{{ $account->plan->name }}</td>
                            <td><code>{{ $account->linux_username }}</code></td>
                            <td>
                                @php
                                    $badge = match ($account->status) {
                                        'active' => 'success',
                                        'error' => 'danger',
                                        default => 'secondary',
                                    };
                                @endphp
                                <span class="badge text-bg-{{ $badge }}">{{ $account->status }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.hosting-accounts.show', $account) }}" class="btn btn-sm btn-outline-secondary">{{ __('Ver') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-secondary py-4">{{ __('Nenhuma conta de hospedagem ainda.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $accounts->links() }}
    </div>
</x-admin-layout>

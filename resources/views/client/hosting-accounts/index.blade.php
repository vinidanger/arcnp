<x-client-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Minhas hospedagens') }}</h1>
    </x-slot>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Domínio') }}</th>
                        <th>{{ __('Plano') }}</th>
                        <th>{{ __('SSL') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($accounts as $account)
                        <tr>
                            <td><a href="{{ route('client.hosting-accounts.show', $account) }}">{{ $account->primary_domain }}</a></td>
                            <td>{{ $account->plan->name }}</td>
                            <td>
                                @php
                                    $sslBadge = match ($account->ssl_status) {
                                        'active' => 'success',
                                        'pending' => 'info',
                                        'failed' => 'danger',
                                        default => 'secondary',
                                    };
                                @endphp
                                <span class="badge text-bg-{{ $sslBadge }}">{{ $account->ssl_status }}</span>
                            </td>
                            <td>
                                @php
                                    $badge = match ($account->status) {
                                        'active' => 'success',
                                        'suspended' => 'warning',
                                        'error' => 'danger',
                                        default => 'secondary',
                                    };
                                @endphp
                                <span class="badge text-bg-{{ $badge }}">{{ $account->status }}</span>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('client.hosting-accounts.show', $account) }}" class="btn btn-sm btn-outline-secondary">{{ __('Gerenciar') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-4">{{ __('Você ainda não tem nenhuma hospedagem.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-client-layout>

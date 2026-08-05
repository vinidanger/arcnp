<x-client-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Minhas hospedagens') }}</h1>
    </x-slot>

    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-auto">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">{{ __('Todos os status') }}</option>
                        @foreach (['active' => 'Ativo', 'suspended' => 'Suspenso', 'error' => 'Erro'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ __($label) }}</option>
                        @endforeach
                    </select>
                </div>
                @if (request('status'))
                    <div class="col-auto">
                        <a href="{{ route('client.hosting-accounts.index') }}" class="btn btn-sm btn-link">{{ __('Limpar') }}</a>
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
                                <span class="badge text-bg-{{ $sslBadge }}">{{ status_label($account->ssl_status) }}</span>
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
                                <span class="badge text-bg-{{ $badge }}">{{ status_label($account->status) }}</span>
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

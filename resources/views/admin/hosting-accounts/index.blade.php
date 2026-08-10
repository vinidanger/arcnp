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

    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-auto flex-grow-1" style="max-width: 20rem;">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="{{ __('Buscar por domínio...') }}">
                </div>
                <div class="col-auto">
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">{{ __('Todos os status') }}</option>
                        @foreach (['active' => 'Ativo', 'suspended' => 'Suspenso', 'error' => 'Erro', 'creating' => 'Criando'] as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') === $value)>{{ __($label) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('Filtrar') }}</button>
                    @if (request('search') || request('status'))
                        <a href="{{ route('admin.hosting-accounts.index') }}" class="btn btn-sm btn-link">{{ __('Limpar') }}</a>
                    @endif
                </div>
            </form>
        </div>
    </div>

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
                        <th>{{ __('Segurança') }}</th>
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
                                        'suspended' => 'warning',
                                        'error' => 'danger',
                                        default => 'secondary',
                                    };
                                @endphp
                                <span class="badge text-bg-{{ $badge }}">{{ status_label($account->status) }}</span>
                            </td>
                            <td><x-security-score-badge :account="$account" /></td>
                            <td class="text-end">
                                <a href="{{ route('admin.hosting-accounts.show', $account) }}" class="btn btn-sm btn-outline-secondary">{{ __('Ver') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-secondary py-4">{{ __('Nenhuma conta de hospedagem ainda.') }}</td>
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

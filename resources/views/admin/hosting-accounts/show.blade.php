<x-admin-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">{{ $account->primary_domain }}</h1>
            <div class="d-flex gap-2">
                @if ($account->status === 'error')
                    <form method="POST" action="{{ route('admin.hosting-accounts.retry', $account) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-warning">{{ __('Tentar provisionar novamente') }}</button>
                    </form>
                @endif
                <form method="POST" action="{{ route('admin.hosting-accounts.destroy', $account) }}"
                      onsubmit="return confirm('{{ __('Isso remove o usuário Linux, vhost e pool PHP-FPM do servidor. Continuar?') }}')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Excluir') }}</button>
                </form>
            </div>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($account->status === 'error' && $account->last_provision_error)
        <div class="alert alert-danger">
            <strong>{{ __('Erro no provisionamento:') }}</strong> {{ $account->last_provision_error }}
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <dl class="row mb-0 small">
                <dt class="col-3">{{ __('Cliente') }}</dt>
                <dd class="col-9">{{ $account->client->name }} ({{ $account->client->email }})</dd>

                <dt class="col-3">{{ __('Servidor') }}</dt>
                <dd class="col-9">
                    <a href="{{ route('admin.servers.show', $account->server) }}">{{ $account->server->name }}</a>
                </dd>

                <dt class="col-3">{{ __('Plano') }}</dt>
                <dd class="col-9">{{ $account->plan->name }}</dd>

                <dt class="col-3">{{ __('Username Linux') }}</dt>
                <dd class="col-9"><code>{{ $account->linux_username }}</code></dd>

                <dt class="col-3">{{ __('Versão PHP') }}</dt>
                <dd class="col-9">{{ $account->php_version }}</dd>

                <dt class="col-3">{{ __('Status') }}</dt>
                <dd class="col-9">
                    @php
                        $badge = match ($account->status) {
                            'active' => 'success',
                            'error' => 'danger',
                            default => 'secondary',
                        };
                    @endphp
                    <span class="badge text-bg-{{ $badge }}">{{ $account->status }}</span>
                </dd>
            </dl>
        </div>
    </div>
</x-admin-layout>

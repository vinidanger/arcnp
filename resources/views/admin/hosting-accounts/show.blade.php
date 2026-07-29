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

                @if ($account->status === 'active')
                    <form method="POST" action="{{ route('admin.hosting-accounts.suspend', $account) }}"
                          onsubmit="return confirm('{{ __('Isso desativa o site e o e-mail do cliente até reativar. Continuar?') }}')">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('Suspender') }}</button>
                    </form>
                @elseif ($account->status === 'suspended')
                    <form method="POST" action="{{ route('admin.hosting-accounts.reactivate', $account) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-success">{{ __('Reativar') }}</button>
                    </form>
                @endif

                <form method="POST" action="{{ route('admin.hosting-accounts.destroy', $account) }}"
                      onsubmit="return confirm('{{ __('Isso remove o usuário Linux, vhost, pool PHP-FPM e banco de dados do servidor. Continuar?') }}')">
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

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($account->status === 'error' && $account->last_provision_error)
        <div class="alert alert-danger">
            <strong>{{ __('Erro no provisionamento:') }}</strong> {{ $account->last_provision_error }}
        </div>
    @endif

    @if (session('plain_db_password'))
        <div class="alert alert-warning">
            <strong>{{ __('Credenciais do banco de dados — copie agora, não aparecem de novo.') }}</strong>
            <pre class="mb-0 mt-2 bg-white p-2 rounded border small">DB_DATABASE={{ $account->database->db_name }}
DB_USERNAME={{ $account->database->db_username }}
DB_PASSWORD={{ session('plain_db_password') }}</pre>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-4">{{ __('Cliente') }}</dt>
                        <dd class="col-8">{{ $account->client->name }} ({{ $account->client->email }})</dd>

                        <dt class="col-4">{{ __('Servidor') }}</dt>
                        <dd class="col-8">
                            <a href="{{ route('admin.servers.show', $account->server) }}">{{ $account->server->name }}</a>
                        </dd>

                        <dt class="col-4">{{ __('Plano') }}</dt>
                        <dd class="col-8">{{ $account->plan->name }}</dd>

                        <dt class="col-4">{{ __('Username Linux') }}</dt>
                        <dd class="col-8"><code>{{ $account->linux_username }}</code></dd>

                        <dt class="col-4">{{ __('Versão PHP') }}</dt>
                        <dd class="col-8">{{ $account->php_version }}</dd>

                        <dt class="col-4">{{ __('Status') }}</dt>
                        <dd class="col-8">
                            @php
                                $badge = match ($account->status) {
                                    'active' => 'success',
                                    'suspended' => 'warning',
                                    'error' => 'danger',
                                    default => 'secondary',
                                };
                            @endphp
                            <span class="badge text-bg-{{ $badge }}">{{ $account->status }}</span>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6">{{ __('Banco de dados') }}</h2>

                    @if ($account->database)
                        <dl class="row mb-3 small">
                            <dt class="col-4">{{ __('Banco') }}</dt>
                            <dd class="col-8"><code>{{ $account->database->db_name }}</code></dd>
                            <dt class="col-4">{{ __('Usuário') }}</dt>
                            <dd class="col-8"><code>{{ $account->database->db_username }}</code></dd>
                        </dl>
                        <form method="POST" action="{{ route('admin.hosting-accounts.database.destroy', $account) }}"
                              onsubmit="return confirm('{{ __('Remove o banco e o usuário MySQL. Continuar?') }}')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Remover banco') }}</button>
                        </form>
                    @elseif ($account->status === 'active')
                        <p class="small text-secondary">{{ __('Essa conta ainda não tem banco de dados.') }}</p>
                        <form method="POST" action="{{ route('admin.hosting-accounts.database.store', $account) }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('Criar banco de dados') }}</button>
                        </form>
                    @else
                        <p class="small text-secondary">{{ __('Disponível quando a conta estiver ativa.') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>

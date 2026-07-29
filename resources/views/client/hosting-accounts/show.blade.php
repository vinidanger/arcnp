<x-client-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">{{ $account->primary_domain }}</h1>
            @if ($account->status === 'active' && $account->ssl_status !== 'active')
                <form method="POST" action="{{ route('client.hosting-accounts.ssl.store', $account) }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('Emitir SSL') }}</button>
                </form>
            @endif
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($account->status === 'suspended')
        <div class="alert alert-warning">
            {{ __('Essa conta está suspensa. Entre em contato com o suporte se isso for inesperado.') }}
        </div>
    @endif

    @if (session('plain_db_password'))
        <div class="alert alert-warning">
            <strong>{{ __('Credenciais do banco de dados — copie agora, não aparecem de novo.') }}</strong>
            <pre class="mb-0 mt-2 bg-white p-2 rounded border small">DB_DATABASE={{ session('plain_db_name') }}
DB_USERNAME={{ session('plain_db_username') }}
DB_PASSWORD={{ session('plain_db_password') }}</pre>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-4">{{ __('Plano') }}</dt>
                        <dd class="col-8">{{ $account->plan->name }}</dd>

                        <dt class="col-4">{{ __('Versão PHP') }}</dt>
                        <dd class="col-8">
                            @if ($account->status === 'active')
                                <form method="POST" action="{{ route('client.hosting-accounts.php-version.update', $account) }}" class="d-flex gap-2">
                                    @csrf
                                    <select name="php_version" class="form-select form-select-sm w-auto">
                                        @foreach (config('hosting.php_versions') as $version)
                                            <option value="{{ $version }}" @selected($account->php_version === $version)>{{ $version }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('Trocar') }}</button>
                                </form>
                            @else
                                {{ $account->php_version }}
                            @endif
                        </dd>

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

                        <dt class="col-4">{{ __('SSL') }}</dt>
                        <dd class="col-8">
                            @php
                                $sslBadge = match ($account->ssl_status) {
                                    'active' => 'success',
                                    'pending' => 'info',
                                    'failed' => 'danger',
                                    default => 'secondary',
                                };
                            @endphp
                            <span class="badge text-bg-{{ $sslBadge }}">{{ $account->ssl_status }}</span>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6">{{ __('Bancos de dados') }}</h2>

                    @if ($account->databases->isNotEmpty())
                        <div class="table-responsive mb-3">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>{{ __('Banco') }}</th>
                                        <th>{{ __('Usuário') }}</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($account->databases as $database)
                                        <tr>
                                            <td><code>{{ $database->db_name }}</code></td>
                                            <td><code>{{ $database->db_username }}</code></td>
                                            <td class="text-end">
                                                <a href="{{ route('client.hosting-accounts.databases.phpmyadmin', [$account, $database]) }}"
                                                   class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">{{ __('phpMyAdmin') }}</a>
                                                <form method="POST" action="{{ route('client.hosting-accounts.databases.destroy', [$account, $database]) }}"
                                                      class="d-inline"
                                                      onsubmit="return confirm('{{ __('Remove o banco e o usuário MySQL. Continuar?') }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Remover') }}</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if ($account->status === 'active')
                        <form method="POST" action="{{ route('client.hosting-accounts.databases.store', $account) }}" class="row g-2 align-items-end">
                            @csrf
                            <div class="col-auto">
                                <x-input-label for="db_name" value="{{ __('Nome do banco') }}" class="small mb-1" />
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">{{ $account->linux_username }}_</span>
                                    <input id="db_name" name="name" type="text" class="form-control" placeholder="loja" required>
                                </div>
                            </div>
                            <div class="col-auto">
                                <x-input-label for="db_username" value="{{ __('Usuário (opcional)') }}" class="small mb-1" />
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">{{ $account->linux_username }}_</span>
                                    <input id="db_username" name="username" type="text" class="form-control" placeholder="{{ __('igual ao nome do banco') }}">
                                </div>
                            </div>
                            <div class="col-auto">
                                <x-input-label for="db_password" value="{{ __('Senha (opcional)') }}" class="small mb-1" />
                                <input id="db_password" name="password" type="text" class="form-control form-control-sm" placeholder="{{ __('gerar automaticamente') }}">
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('Criar banco') }}</button>
                            </div>
                        </form>
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        <x-input-error :messages="$errors->get('username')" class="mt-2" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    @else
                        <p class="small text-secondary mb-0">{{ __('Disponível quando a conta estiver ativa.') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <h2 class="h6">{{ __('Domínios adicionais / subdomínios') }}</h2>

            @if ($account->domains->isNotEmpty())
                <div class="table-responsive mb-3">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Domínio') }}</th>
                                <th>{{ __('Tipo') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('SSL') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($account->domains as $domain)
                                <tr>
                                    <td>{{ $domain->domain }}</td>
                                    <td>{{ $domain->type === 'addon' ? __('Adicional') : __('Subdomínio') }}</td>
                                    <td>
                                        @php
                                            $domainBadge = match ($domain->status) {
                                                'active' => 'success',
                                                'error' => 'danger',
                                                default => 'secondary',
                                            };
                                        @endphp
                                        <span class="badge text-bg-{{ $domainBadge }}">{{ $domain->status }}</span>
                                        @if ($domain->status === 'error' && $domain->last_error)
                                            <div class="small text-danger">{{ $domain->last_error }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $domainSslBadge = match ($domain->ssl_status) {
                                                'active' => 'success',
                                                'pending' => 'info',
                                                'failed' => 'danger',
                                                default => 'secondary',
                                            };
                                        @endphp
                                        <span class="badge text-bg-{{ $domainSslBadge }}">{{ $domain->ssl_status }}</span>
                                    </td>
                                    <td class="text-end">
                                        <form method="POST" action="{{ route('client.hosting-accounts.domains.destroy', [$account, $domain]) }}"
                                              onsubmit="return confirm('{{ __('Remove esse domínio. Continuar?') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Remover') }}</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            @if ($account->status === 'active')
                <form method="POST" action="{{ route('client.hosting-accounts.domains.store', $account) }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-auto">
                        <x-input-label for="domain" value="{{ __('Domínio') }}" class="visually-hidden" />
                        <x-text-input id="domain" name="domain" type="text" placeholder="blog.{{ $account->primary_domain }}" required />
                    </div>
                    <div class="col-auto">
                        <select name="type" class="form-select">
                            <option value="subdomain">{{ __('Subdomínio') }}</option>
                            <option value="addon">{{ __('Domínio adicional') }}</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-outline-primary">{{ __('Adicionar') }}</button>
                    </div>
                </form>
                <x-input-error :messages="$errors->get('domain')" class="mt-2" />
            @else
                <p class="small text-secondary mb-0">{{ __('Disponível quando a conta estiver ativa.') }}</p>
            @endif
        </div>
    </div>
</x-client-layout>

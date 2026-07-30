<x-admin-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">{{ $account->primary_domain }}</h1>
            <div class="d-flex gap-2">
                @if ($account->status === 'active')
                    <a href="{{ route('admin.hosting-accounts.files.index', $account) }}" class="btn btn-sm btn-outline-secondary">{{ __('Arquivos') }}</a>
                    <a href="{{ route('admin.hosting-accounts.cron.index', $account) }}" class="btn btn-sm btn-outline-secondary">{{ __('Cron') }}</a>
                    <a href="{{ route('admin.hosting-accounts.ssh.index', $account) }}" class="btn btn-sm btn-outline-secondary">{{ __('SSH') }}</a>
                    <a href="{{ route('admin.hosting-accounts.dns.index', $account) }}" class="btn btn-sm btn-outline-secondary">{{ __('DNS') }}</a>
                    <a href="{{ route('admin.hosting-accounts.mail.index', $account) }}" class="btn btn-sm btn-outline-secondary">{{ __('E-mail') }}</a>
                @endif

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

                @if ($account->status === 'active' && $account->ssl_status !== 'active')
                    <form method="POST" action="{{ route('admin.hosting-accounts.ssl.store', $account) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('Emitir SSL') }}</button>
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
            <pre class="mb-0 mt-2 bg-white p-2 rounded border small">DB_DATABASE={{ session('plain_db_name') }}
DB_USERNAME={{ session('plain_db_username') }}
DB_PASSWORD={{ session('plain_db_password') }}</pre>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6">{{ __('Uso do plano') }} — {{ $account->plan->name }}</h2>
            @php
                $diskUsed = $account->disk_usage_mb ?? 0;
                $diskQuota = max($account->plan->disk_quota_mb, 1);
                $diskPercent = min(100, (int) round(($diskUsed / $diskQuota) * 100));
                $diskBar = $diskPercent >= 90 ? 'bg-danger' : ($diskPercent >= 70 ? 'bg-warning' : 'bg-success');
                $dbCount = $account->databases->count();
                $domainCount = $account->domains->count();
                $cronCount = $account->cronJobs->count();
            @endphp
            <div class="row g-3 small">
                <div class="col-md-6">
                    <div class="d-flex justify-content-between">
                        <span>{{ __('Disco') }}</span>
                        <span>{{ $diskUsed }} / {{ $diskQuota }} MB</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar {{ $diskBar }}" style="width: {{ $diskPercent }}%"></div>
                    </div>
                    @if (! $account->disk_usage_checked_at)
                        <div class="text-secondary mt-1">{{ __('Ainda não calculado.') }}</div>
                    @else
                        <div class="text-secondary mt-1">{{ __('Atualizado em') }} {{ $account->disk_usage_checked_at->format('d/m/Y H:i') }}</div>
                    @endif
                </div>
                <div class="col-md-2">
                    {{ __('Bancos') }}: <strong>{{ $dbCount }} / {{ $account->plan->max_databases }}</strong>
                </div>
                <div class="col-md-2">
                    {{ __('Domínios') }}: <strong>{{ $domainCount }} / {{ $account->plan->max_addon_domains }}</strong>
                </div>
                <div class="col-md-2">
                    {{ __('Cron') }}: <strong>{{ $cronCount }} / {{ $account->plan->max_cron_jobs }}</strong>
                </div>
            </div>
        </div>
    </div>

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
                        <dd class="col-8">
                            @if ($account->status === 'active')
                                <form method="POST" action="{{ route('admin.hosting-accounts.php-version.update', $account) }}" class="d-flex gap-2">
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
                            @if ($account->ssl_status === 'failed' && $account->ssl_error)
                                <div class="small text-danger mt-1">{{ $account->ssl_error }}</div>
                            @endif
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
                                                <a href="{{ route('admin.hosting-accounts.databases.phpmyadmin', [$account, $database]) }}"
                                                   class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">{{ __('phpMyAdmin') }}</a>
                                                <form method="POST" action="{{ route('admin.hosting-accounts.databases.destroy', [$account, $database]) }}"
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
                        <form method="POST" action="{{ route('admin.hosting-accounts.databases.store', $account) }}" class="row g-2 align-items-end">
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
                                <th>{{ __('Document root') }}</th>
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
                                        @if ($domain->isOutsidePublicHtml())
                                            <code>domains/{{ $domain->domain }}/public_html</code>
                                        @else
                                            <code>public_html/{{ $domain->subdirectory }}</code>
                                        @endif
                                    </td>
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
                                        <form method="POST" action="{{ route('admin.hosting-accounts.domains.destroy', [$account, $domain]) }}"
                                              onsubmit="return confirm('{{ __('Remove o vhost e o diretório desse domínio. Continuar?') }}')">
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
                <form method="POST" action="{{ route('admin.hosting-accounts.domains.store', $account) }}" class="row g-2 align-items-end">
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
                        <select name="location" class="form-select">
                            <option value="inside_public_html">{{ __('Dentro de public_html') }}</option>
                            <option value="outside_public_html">{{ __('Fora de public_html (domains/)') }}</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-outline-primary">{{ __('Adicionar') }}</button>
                    </div>
                </form>
                <x-input-error :messages="$errors->get('domain')" class="mt-2" />
                <x-input-error :messages="$errors->get('location')" class="mt-2" />
            @else
                <p class="small text-secondary mb-0">{{ __('Disponível quando a conta estiver ativa.') }}</p>
            @endif
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h6 mb-0">{{ __('Backups') }}</h2>
                @if ($account->status === 'active')
                    <div class="d-flex gap-2">
                        <form method="POST" action="{{ route('admin.hosting-accounts.backup-frequency.update', $account) }}" class="d-flex gap-1">
                            @csrf
                            <select name="backup_frequency" class="form-select form-select-sm" onchange="this.form.submit()">
                                @foreach (config('hosting.backup_frequencies') as $frequency)
                                    <option value="{{ $frequency }}" @selected($account->backup_frequency === $frequency)>
                                        {{ match ($frequency) { 'daily' => __('Automático diário'), 'weekly' => __('Automático semanal'), default => __('Desativado') } }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                        <form method="POST" action="{{ route('admin.hosting-accounts.backups.store', $account) }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('Criar backup agora') }}</button>
                        </form>
                    </div>
                @endif
            </div>

            @if ($account->backups->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>{{ __('Data') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Arquivos') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($account->backups as $backup)
                                <tr>
                                    <td>{{ $backup->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        @php
                                            $backupBadge = match ($backup->status) {
                                                'completed' => 'success',
                                                'failed' => 'danger',
                                                default => 'info',
                                            };
                                        @endphp
                                        <span class="badge text-bg-{{ $backupBadge }}">{{ $backup->status }}</span>
                                        @if ($backup->status === 'failed' && $backup->error)
                                            <div class="small text-danger">{{ $backup->error }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @foreach ($backup->files ?? [] as $file)
                                            <a href="{{ route('admin.hosting-accounts.backups.download', [$account, $backup, $file['filename']]) }}"
                                               class="d-block small">{{ $file['filename'] }} ({{ number_format($file['size'] / 1048576, 1) }} MB)</a>
                                        @endforeach
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="small text-secondary mb-0">{{ __('Nenhum backup ainda.') }}</p>
            @endif
        </div>
    </div>
</x-admin-layout>

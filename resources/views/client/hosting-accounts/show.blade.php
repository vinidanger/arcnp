<x-client-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">{{ $account->primary_domain }}</h1>
            <div class="d-flex gap-2">
                @if ($account->status === 'active')
                    <a href="{{ route('client.hosting-accounts.files.index', $account) }}" class="btn btn-sm btn-outline-secondary">{{ __('Arquivos') }}</a>
                    <a href="{{ route('client.hosting-accounts.cron.index', $account) }}" class="btn btn-sm btn-outline-secondary">{{ __('Cron') }}</a>
                    <a href="{{ route('client.hosting-accounts.ssh.index', $account) }}" class="btn btn-sm btn-outline-secondary">{{ __('SSH') }}</a>
                    <a href="{{ route('client.hosting-accounts.dns.index', $account) }}" class="btn btn-sm btn-outline-secondary">{{ __('DNS') }}</a>
                    <a href="{{ route('client.hosting-accounts.mail.index', $account) }}" class="btn btn-sm btn-outline-secondary">{{ __('E-mail') }}</a>
                @endif

                @if ($account->status === 'active' && $account->ssl_status !== 'active')
                    <form method="POST" action="{{ route('client.hosting-accounts.ssl.store', $account) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-primary">{{ __('Emitir SSL') }}</button>
                    </form>
                @endif
            </div>
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
                $backupCount = $account->backups->whereIn('status', ['pending', 'completed'])->count();
                $backupLimitReached = $backupCount >= $account->plan->max_backups;
            @endphp
            <div class="row g-3 small">
                <div class="col-md-4">
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
                <div class="col-6 col-md-2">
                    {{ __('Bancos') }}: <strong>{{ $dbCount }} / {{ $account->plan->max_databases }}</strong>
                </div>
                <div class="col-6 col-md-2">
                    {{ __('Domínios') }}: <strong>{{ $domainCount }} / {{ $account->plan->max_addon_domains }}</strong>
                </div>
                <div class="col-6 col-md-2">
                    {{ __('Cron') }}: <strong>{{ $cronCount }} / {{ $account->plan->max_cron_jobs }}</strong>
                </div>
                <div class="col-6 col-md-2">
                    {{ __('Backups') }}: <strong>{{ $backupCount }} / {{ $account->plan->max_backups }}</strong>
                </div>
            </div>
        </div>
    </div>

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
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h2 class="h6 mb-0">{{ __('Bancos de dados') }}</h2>
                        @if ($account->status === 'active')
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#add-database-modal">
                                <i class="bi bi-plus-lg"></i> {{ __('Criar banco') }}
                            </button>
                        @endif
                    </div>

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

                    @unless ($account->status === 'active')
                        <p class="small text-secondary mb-0">{{ __('Disponível quando a conta estiver ativa.') }}</p>
                    @endunless
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h2 class="h6 mb-0">{{ __('Domínios adicionais / subdomínios') }}</h2>
                @if ($account->status === 'active')
                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#add-domain-modal">
                        <i class="bi bi-plus-lg"></i> {{ __('Adicionar domínio') }}
                    </button>
                @endif
            </div>

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

            @unless ($account->status === 'active')
                <p class="small text-secondary mb-0">{{ __('Disponível quando a conta estiver ativa.') }}</p>
            @endunless
        </div>
    </div>

    <div class="card mt-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="d-flex align-items-center gap-2">
                    <h2 class="h6 mb-0">{{ __('Backups') }}</h2>
                    <span class="badge text-bg-{{ $backupLimitReached ? 'warning' : 'secondary' }} rounded-pill">{{ $backupCount }} / {{ $account->plan->max_backups }}</span>
                </div>
                @if ($account->status === 'active')
                    <div class="d-flex gap-2">
                        <form method="POST" action="{{ route('client.hosting-accounts.backup-frequency.update', $account) }}" class="d-flex gap-1">
                            @csrf
                            <select name="backup_frequency" class="form-select form-select-sm" onchange="this.form.submit()">
                                @foreach (config('hosting.backup_frequencies') as $frequency)
                                    <option value="{{ $frequency }}" @selected($account->backup_frequency === $frequency)>
                                        {{ match ($frequency) { 'daily' => __('Automático diário'), 'weekly' => __('Automático semanal'), default => __('Desativado') } }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                        <form method="POST" action="{{ route('client.hosting-accounts.backups.store', $account) }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-primary" @disabled($backupLimitReached) title="{{ $backupLimitReached ? __('Limite de backups do plano atingido') : '' }}">{{ __('Criar backup agora') }}</button>
                        </form>
                    </div>
                @endif
            </div>

            @if ($account->backups->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-sm mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>{{ __('Data') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Tamanho') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($account->backups as $backup)
                                @php
                                    $backupBadge = match ($backup->status) {
                                        'completed' => 'success',
                                        'failed' => 'danger',
                                        default => 'info',
                                    };
                                    $hasFilesArchive = collect($backup->files)->contains(fn ($f) => str_starts_with($f['filename'], 'files-'));
                                    $hasDatabaseArchives = collect($backup->files)->contains(fn ($f) => str_starts_with($f['filename'], 'db-'));
                                    $totalSize = collect($backup->files)->sum('size');
                                @endphp
                                <tr>
                                    <td>{{ $backup->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <span class="badge text-bg-{{ $backupBadge }}">{{ $backup->status }}</span>
                                        @if ($backup->status === 'failed' && $backup->error)
                                            <div class="small text-danger">{{ $backup->error }}</div>
                                        @endif
                                    </td>
                                    <td class="small text-secondary">{{ $backup->status === 'completed' ? number_format($totalSize / 1048576, 1).' MB' : '—' }}</td>
                                    <td class="text-end">
                                        @if ($backup->status === 'completed')
                                            <div class="dropdown d-inline-block">
                                                <button class="btn btn-sm btn-outline-primary dropdown-toggle d-inline-flex align-items-center gap-1" type="button" data-bs-toggle="dropdown">
                                                    <i class="bi bi-download"></i> {{ __('Baixar') }}
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end">
                                                    @if ($hasFilesArchive)
                                                        <li><a class="dropdown-item" href="{{ route('client.hosting-accounts.backups.bundle', [$account, $backup, 'files']) }}">{{ __('Arquivos') }}</a></li>
                                                    @endif
                                                    @if ($hasDatabaseArchives)
                                                        <li><a class="dropdown-item" href="{{ route('client.hosting-accounts.backups.bundle', [$account, $backup, 'databases']) }}">{{ __('Bancos de dados') }}</a></li>
                                                    @endif
                                                    <li><a class="dropdown-item" href="{{ route('client.hosting-accounts.backups.bundle', [$account, $backup, 'all']) }}">{{ __('Completo') }}</a></li>
                                                </ul>
                                            </div>
                                        @endif
                                        <form method="POST" action="{{ route('client.hosting-accounts.backups.destroy', [$account, $backup]) }}" class="d-inline"
                                              onsubmit="return confirm('{{ __('Remove esse backup e seus arquivos do servidor. Continuar?') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="{{ __('Remover') }}"><i class="bi bi-trash"></i></button>
                                        </form>
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

    {{-- Modal: adicionar domínio --}}
    <x-modal name="add-domain-modal" maxWidth="sm" :show="$errors->has('domain') || $errors->has('location')">
        <form method="POST" action="{{ route('client.hosting-accounts.domains.store', $account) }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Adicionar domínio') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <x-input-label for="domain" value="{{ __('Domínio') }}" class="small mb-1" />
                    <x-text-input id="domain" name="domain" type="text" placeholder="blog.{{ $account->primary_domain }}" :value="old('domain')" required autofocus />
                    <x-input-error :messages="$errors->get('domain')" class="mt-2" />
                </div>
                <div class="mb-3">
                    <x-input-label for="domain-type" value="{{ __('Tipo') }}" class="small mb-1" />
                    <select id="domain-type" name="type" class="form-select">
                        <option value="subdomain">{{ __('Subdomínio') }}</option>
                        <option value="addon">{{ __('Domínio adicional') }}</option>
                    </select>
                </div>
                <div class="mb-0">
                    <x-input-label for="domain-location" value="{{ __('Localização') }}" class="small mb-1" />
                    <select id="domain-location" name="location" class="form-select">
                        <option value="inside_public_html">{{ __('Dentro de public_html') }}</option>
                        <option value="outside_public_html">{{ __('Fora de public_html (domains/)') }}</option>
                    </select>
                    <x-input-error :messages="$errors->get('location')" class="mt-2" />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancelar') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('Adicionar') }}</button>
            </div>
        </form>
    </x-modal>

    {{-- Modal: criar banco de dados --}}
    <x-modal name="add-database-modal" maxWidth="sm" :show="$errors->has('name') || $errors->has('username') || $errors->has('password')">
        <form method="POST" action="{{ route('client.hosting-accounts.databases.store', $account) }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Criar banco de dados') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <x-input-label for="db_name" value="{{ __('Nome do banco') }}" class="small mb-1" />
                    <div class="input-group">
                        <span class="input-group-text">{{ $account->linux_username }}_</span>
                        <input id="db_name" name="name" type="text" class="form-control" placeholder="loja" value="{{ old('name') }}" required autofocus>
                    </div>
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>
                <div class="mb-3">
                    <x-input-label for="db_username" value="{{ __('Usuário (opcional)') }}" class="small mb-1" />
                    <div class="input-group">
                        <span class="input-group-text">{{ $account->linux_username }}_</span>
                        <input id="db_username" name="username" type="text" class="form-control" placeholder="{{ __('igual ao nome do banco') }}" value="{{ old('username') }}">
                    </div>
                    <x-input-error :messages="$errors->get('username')" class="mt-2" />
                </div>
                <div class="mb-0">
                    <x-input-label for="db_password" value="{{ __('Senha (opcional)') }}" class="small mb-1" />
                    <input id="db_password" name="password" type="text" class="form-control" placeholder="{{ __('gerar automaticamente') }}">
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancelar') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('Criar banco') }}</button>
            </div>
        </form>
    </x-modal>
</x-client-layout>

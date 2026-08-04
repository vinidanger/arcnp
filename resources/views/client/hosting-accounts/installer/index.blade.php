<x-client-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Instalador de apps') }} — {{ $account->primary_domain }}</h1>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6">{{ $catalog['wordpress']['name'] }}</h2>
                    <p class="small text-secondary">{{ __('Cria um banco de dados dedicado, baixa o WordPress e conclui a instalação com o usuário admin que você definir.') }}</p>
                    @unless ($wordpressPhpOk)
                        <div class="alert alert-warning small py-2">{{ __('Essa conta está numa versão de PHP anterior à mínima exigida pelo WordPress (').config('app_catalog.wordpress.min_php_version').__(') — troque a versão de PHP da conta antes de instalar.') }}</div>
                    @endunless
                    <form method="POST" action="{{ route('client.hosting-accounts.installer.wordpress', $account) }}">
                        @csrf
                        <fieldset @disabled(! $wordpressPhpOk)>
                            <div class="mb-2">
                                <x-input-label for="wp-domain" value="{{ __('Domínio') }}" class="small mb-1" />
                                <select id="wp-domain" name="domain" class="form-select form-select-sm" required>
                                    @foreach ($domains as $domain)
                                        <option value="{{ $domain }}">{{ $domain }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-2">
                                <x-input-label for="wp-path" value="{{ __('Pasta (opcional)') }}" class="small mb-1" />
                                <input type="text" id="wp-path" name="path" class="form-control form-control-sm" placeholder="blog">
                            </div>
                            <div class="mb-2">
                                <x-input-label for="wp-title" value="{{ __('Título do site') }}" class="small mb-1" />
                                <input type="text" id="wp-title" name="site_title" class="form-control form-control-sm" required>
                            </div>
                            <div class="mb-2">
                                <x-input-label for="wp-admin-user" value="{{ __('Usuário admin') }}" class="small mb-1" />
                                <input type="text" id="wp-admin-user" name="admin_user" class="form-control form-control-sm" required>
                            </div>
                            <div class="mb-2">
                                <x-input-label for="wp-admin-password" value="{{ __('Senha do admin') }}" class="small mb-1" />
                                <input type="password" id="wp-admin-password" name="admin_password" class="form-control form-control-sm" minlength="8" required>
                            </div>
                            <div class="mb-2">
                                <x-input-label for="wp-admin-email" value="{{ __('E-mail do admin') }}" class="small mb-1" />
                                <input type="email" id="wp-admin-email" name="admin_email" class="form-control form-control-sm" required>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary">{{ __('Instalar WordPress') }}</button>
                        </fieldset>
                    </form>
                    <x-input-error :messages="$errors->get('domain')" class="mt-2" />
                    <x-input-error :messages="$errors->get('site_title')" class="mt-2" />
                    <x-input-error :messages="$errors->get('admin_user')" class="mt-2" />
                    <x-input-error :messages="$errors->get('admin_password')" class="mt-2" />
                    <x-input-error :messages="$errors->get('admin_email')" class="mt-2" />
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6">{{ $catalog['generic_zip']['name'] }}</h2>
                    <p class="small text-secondary">{{ __('Envia um .zip e extrai direto no domínio escolhido — sem banco de dados nem usuário admin, é só o app do jeito que você mandar.') }}</p>
                    <form method="POST" action="{{ route('client.hosting-accounts.installer.generic-zip', $account) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-2">
                            <x-input-label for="zip-domain" value="{{ __('Domínio') }}" class="small mb-1" />
                            <select id="zip-domain" name="domain" class="form-select form-select-sm" required>
                                @foreach ($domains as $domain)
                                    <option value="{{ $domain }}">{{ $domain }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <x-input-label for="zip-path" value="{{ __('Pasta (opcional)') }}" class="small mb-1" />
                            <input type="text" id="zip-path" name="path" class="form-control form-control-sm" placeholder="app">
                        </div>
                        <div class="mb-2">
                            <x-input-label for="zip-file" value="{{ __('Arquivo .zip') }}" class="small mb-1" />
                            <input type="file" id="zip-file" name="zip" accept=".zip" class="form-control form-control-sm" required>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary">{{ __('Extrair app') }}</button>
                    </form>
                    <x-input-error :messages="$errors->get('zip')" class="mt-2" />
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Domínio') }}</th>
                        <th>{{ __('Tipo') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($account->appInstallations as $installation)
                        <tr>
                            <td>{{ $installation->domain }}{{ $installation->path ? '/'.$installation->path : '' }}</td>
                            <td>{{ $installation->catalogEntry()['name'] ?? $installation->catalog_slug }}</td>
                            <td>
                                @php
                                    $badge = match ($installation->status) {
                                        'active' => 'bg-success',
                                        'failed' => 'bg-danger',
                                        default => 'bg-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $badge }}">{{ $installation->status }}</span>
                                @if ($installation->status === 'failed' && $installation->error)
                                    <div class="small text-danger mt-1">{{ $installation->error }}</div>
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($installation->status === 'active' && $installation->catalog_slug === 'wordpress')
                                    <a href="{{ $installation->siteUrl() }}/wp-admin/" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">{{ __('Acessar admin') }}</a>
                                @endif
                                <form method="POST" action="{{ route('client.hosting-accounts.installer.destroy', [$account, $installation]) }}"
                                      class="d-inline-block" onsubmit="return confirm('{{ __('Remove os arquivos (e o banco de dados, se tiver) desse app. Continuar?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Remover') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-secondary py-4">{{ __('Nenhum app instalado.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-client-layout>

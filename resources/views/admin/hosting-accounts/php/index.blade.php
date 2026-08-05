<x-admin-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('PHP') }} — {{ $domain ? $domain->domain : $account->primary_domain }}</h1>
    </x-slot>

    @php
        $versionRoute = $domain ? 'admin.hosting-accounts.domains.php.version.update' : 'admin.hosting-accounts.php.version.update';
        $settingsRoute = $domain ? 'admin.hosting-accounts.domains.php.settings.update' : 'admin.hosting-accounts.php.settings.update';
        $zendRoute = $domain ? 'admin.hosting-accounts.domains.php.zend-extensions.update' : 'admin.hosting-accounts.php.zend-extensions.update';
        $routeParams = $domain ? [$account, $domain] : $account;
    @endphp

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @unless ($account->status === 'active')
        <div class="alert alert-warning mb-3">{{ __('Disponível quando a conta estiver ativa.') }}</div>
    @endunless

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6">{{ __('Versão do PHP') }}</h2>

            @if ($account->status === 'active')
                <form method="POST" action="{{ route($versionRoute, $routeParams) }}" class="d-flex gap-2">
                    @csrf
                    <select name="php_version" class="form-select form-select-sm w-auto">
                        @foreach (config('hosting.php_versions') as $version)
                            <option value="{{ $version }}" @selected($phpVersion === $version)>{{ $version }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('Trocar') }}</button>
                </form>
            @else
                <p class="mb-0">{{ $phpVersion }}</p>
            @endif
        </div>
    </div>

    @if ($account->status === 'active')
        @php $s = $settings; @endphp
        <div class="card">
            <div class="card-body">
                <h2 class="h6">{{ __('Configurações') }}</h2>
                <p class="small text-secondary">{{ __('Aplicadas no pool PHP-FPM desse domínio — equivalente a um php.ini próprio.') }}</p>

                <form method="POST" action="{{ route($settingsRoute, $routeParams) }}">
                    @csrf

                    <div class="mb-2 small text-uppercase text-secondary fw-semibold" style="letter-spacing: .04em;">{{ __('Limites de recursos') }}</div>
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <x-input-label for="memory_limit" value="memory_limit (MB)" class="small mb-1" />
                            <x-text-input id="memory_limit" name="memory_limit" type="number" min="32" max="2048" :value="old('memory_limit', $s['memory_limit'])" required />
                        </div>
                        <div class="col-6 col-md-3">
                            <x-input-label for="upload_max_filesize" value="upload_max_filesize (MB)" class="small mb-1" />
                            <x-text-input id="upload_max_filesize" name="upload_max_filesize" type="number" min="1" max="2048" :value="old('upload_max_filesize', $s['upload_max_filesize'])" required />
                        </div>
                        <div class="col-6 col-md-3">
                            <x-input-label for="post_max_size" value="post_max_size (MB)" class="small mb-1" />
                            <x-text-input id="post_max_size" name="post_max_size" type="number" min="1" max="2048" :value="old('post_max_size', $s['post_max_size'])" required />
                            <x-input-error :messages="$errors->get('post_max_size')" class="mt-1" />
                        </div>
                        <div class="col-6 col-md-3">
                            <x-input-label for="max_execution_time" value="max_execution_time (s)" class="small mb-1" />
                            <x-text-input id="max_execution_time" name="max_execution_time" type="number" min="1" max="300" :value="old('max_execution_time', $s['max_execution_time'])" required />
                        </div>
                        <div class="col-6 col-md-3">
                            <x-input-label for="max_input_time" value="max_input_time (s)" class="small mb-1" />
                            <x-text-input id="max_input_time" name="max_input_time" type="number" min="1" max="300" :value="old('max_input_time', $s['max_input_time'])" required />
                        </div>
                        <div class="col-6 col-md-3">
                            <x-input-label for="max_input_vars" value="max_input_vars" class="small mb-1" />
                            <x-text-input id="max_input_vars" name="max_input_vars" type="number" min="100" max="10000" :value="old('max_input_vars', $s['max_input_vars'])" required />
                        </div>
                        <div class="col-6 col-md-3">
                            <x-input-label for="max_file_uploads" value="max_file_uploads" class="small mb-1" />
                            <x-text-input id="max_file_uploads" name="max_file_uploads" type="number" min="1" max="100" :value="old('max_file_uploads', $s['max_file_uploads'])" required />
                        </div>
                        <div class="col-6 col-md-3">
                            <x-input-label for="session_gc_maxlifetime" value="session.gc_maxlifetime (s)" class="small mb-1" />
                            <x-text-input id="session_gc_maxlifetime" name="session_gc_maxlifetime" type="number" min="60" max="86400" :value="old('session_gc_maxlifetime', $s['session_gc_maxlifetime'])" required />
                        </div>
                    </div>

                    <div class="mb-2 small text-uppercase text-secondary fw-semibold" style="letter-spacing: .04em;">{{ __('Comportamento') }}</div>
                    <div class="row g-3 mb-3">
                        <div class="col-6 col-md-3">
                            <x-input-label for="error_reporting" value="error_reporting" class="small mb-1" />
                            <select id="error_reporting" name="error_reporting" class="form-select form-select-sm">
                                @foreach (config('hosting.error_reporting_presets') as $key => $label)
                                    <option value="{{ $key }}" @selected(old('error_reporting', $s['error_reporting']) === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6 col-md-3 d-flex align-items-end">
                            <div class="form-check">
                                <input type="hidden" name="display_errors" value="0">
                                <input type="checkbox" class="form-check-input" id="display_errors" name="display_errors" value="1" @checked(old('display_errors', $s['display_errors']))>
                                <label class="form-check-label" for="display_errors">display_errors</label>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 d-flex align-items-end">
                            <div class="form-check">
                                <input type="hidden" name="log_errors" value="0">
                                <input type="checkbox" class="form-check-input" id="log_errors" name="log_errors" value="1" @checked(old('log_errors', $s['log_errors']))>
                                <label class="form-check-label" for="log_errors">log_errors</label>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 d-flex align-items-end">
                            <div class="form-check">
                                <input type="hidden" name="file_uploads" value="0">
                                <input type="checkbox" class="form-check-input" id="file_uploads" name="file_uploads" value="1" @checked(old('file_uploads', $s['file_uploads']))>
                                <label class="form-check-label" for="file_uploads">file_uploads</label>
                            </div>
                        </div>
                        <div class="col-6 col-md-3 d-flex align-items-end">
                            <div class="form-check">
                                <input type="hidden" name="short_open_tag" value="0">
                                <input type="checkbox" class="form-check-input" id="short_open_tag" name="short_open_tag" value="1" @checked(old('short_open_tag', $s['short_open_tag'] ?? false))>
                                <label class="form-check-label" for="short_open_tag">short_open_tag</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2 small text-uppercase text-secondary fw-semibold" style="letter-spacing: .04em;">{{ __('Segurança — funções desabilitadas') }}</div>
                    <p class="small text-secondary">{{ __('Desliga funções específicas do PHP só nesse domínio (disable_functions). Não afeta outros domínios nem remove a extensão em si.') }}</p>
                    <div class="row g-2 mb-3">
                        @foreach (config('hosting.disablable_php_functions') as $function)
                            <div class="col-6 col-md-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="disable_functions_{{ $function }}" name="disable_functions[]" value="{{ $function }}"
                                           @checked(in_array($function, old('disable_functions', $s['disable_functions'] ?? []), true))>
                                    <label class="form-check-label" for="disable_functions_{{ $function }}"><code>{{ $function }}</code></label>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mb-2 small text-uppercase text-secondary fw-semibold" style="letter-spacing: .04em;">{{ __('Já ativadas no servidor') }}</div>
                    <p class="small text-secondary">{{ __('Essas extensões já estão disponíveis pra esse domínio automaticamente — não é preciso (nem é possível) ativar por domínio, e desativar afetaria todas as contas do servidor.') }}</p>
                    @if (empty($activeExtensions))
                        <p class="small text-secondary mb-3">{{ __('Nenhuma informação disponível.') }}</p>
                    @else
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            @foreach ($activeExtensions as $extension)
                                <span class="badge text-bg-light border"><code>{{ $extension }}</code></span>
                            @endforeach
                        </div>
                    @endif

                    <div class="mb-2 small text-uppercase text-secondary fw-semibold" style="letter-spacing: .04em;">{{ __('Extensões extras') }}</div>
                    <p class="small text-secondary">{{ __('Ativa extensões PHP disponíveis no servidor só pra esse domínio, sem afetar os demais.') }}</p>
                    @if (empty($availableExtensions))
                        <p class="small text-secondary mb-3">{{ __('Nenhuma extensão disponível pra ativar por domínio nessa versão de PHP.') }}</p>
                    @else
                        <div class="row g-2 mb-3">
                            @foreach ($availableExtensions as $extension)
                                <div class="col-6 col-md-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="extra_extensions_{{ $extension }}" name="extra_extensions[]" value="{{ $extension }}"
                                               @checked(in_array($extension, old('extra_extensions', $s['extra_extensions'] ?? []), true))>
                                        <label class="form-check-label" for="extra_extensions_{{ $extension }}"><code>{{ $extension }}</code></label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <button type="submit" class="btn btn-sm btn-primary">{{ __('Salvar') }}</button>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <h2 class="h6">{{ __('Extensões Zend') }}</h2>
                <p class="small text-secondary">{{ __('zend_extension (ex.: ioncube_loader, opcache) carrega no boot do processo PHP desse domínio — diferente de uma extensão comum, ativar/desativar reinicia o PHP-FPM dele.') }}</p>

                <div class="mb-2 small text-uppercase text-secondary fw-semibold" style="letter-spacing: .04em;">{{ __('Já ativadas no servidor') }}</div>
                @if (empty($activeZendExtensions))
                    <p class="small text-secondary mb-3">{{ __('Nenhuma informação disponível.') }}</p>
                @else
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        @foreach ($activeZendExtensions as $extension)
                            <span class="badge text-bg-light border"><code>{{ $extension }}</code></span>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route($zendRoute, $routeParams) }}" onsubmit="return confirm('Isso reinicia o PHP desse domínio — o site fica fora do ar por um instante. Continuar?')">
                    @csrf

                    <div class="mb-2 small text-uppercase text-secondary fw-semibold" style="letter-spacing: .04em;">{{ __('Disponíveis por domínio') }}</div>
                    <p class="small text-secondary">{{ __('Ativa a extensão Zend só pra esse domínio (isolamento real — não afeta os demais).') }}</p>
                    @if (empty($availableZendExtensions))
                        <p class="small text-secondary mb-3">{{ __('Nenhuma extensão Zend disponível pra ativar por domínio nessa versão de PHP.') }}</p>
                    @else
                        <div class="row g-2 mb-3">
                            @foreach ($availableZendExtensions as $extension)
                                <div class="col-6 col-md-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="zend_extensions_{{ $extension }}" name="zend_extensions[]" value="{{ $extension }}"
                                               @checked(in_array($extension, old('zend_extensions', $s['zend_extensions'] ?? []), true))>
                                        <label class="form-check-label" for="zend_extensions_{{ $extension }}"><code>{{ $extension }}</code></label>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <button type="submit" class="btn btn-sm btn-primary">{{ __('Salvar') }}</button>
                    @endif
                </form>
            </div>
        </div>
    @endif
</x-admin-layout>

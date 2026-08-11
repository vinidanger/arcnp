<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h2 class="h6 mb-0">{{ __('Domínios adicionais / subdomínios') }}</h2>
            @if ($account->status === 'active')
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#add-domain-modal">
                    <i class="bi bi-plus-lg"></i> {{ __('Adicionar domínio') }}
                </button>
            @endif
        </div>

        <div class="table-responsive mb-3">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>{{ __('Domínio') }}</th>
                        <th>{{ __('Tipo') }}</th>
                        <th>{{ __('Document root') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('SSL') }}</th>
                        <th>{{ __('Disponibilidade') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $account->primary_domain }}</td>
                        <td><span class="badge text-bg-primary">{{ __('Principal') }}</span></td>
                        <td>
                            <code class="small">public_html{{ $account->public_path ? '/'.$account->public_path : '' }}</code>
                            @if ($account->status === 'active')
                                <button type="button" class="btn btn-sm btn-link p-0 ms-1" data-bs-toggle="modal" data-bs-target="#docroot-modal-primary" title="{{ __('Editar document root') }}">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            @endif
                        </td>
                        <td>
                            @php
                                $accountBadge = match ($account->status) {
                                    'active' => 'success',
                                    'error' => 'danger',
                                    default => 'secondary',
                                };
                            @endphp
                            <span class="badge text-bg-{{ $accountBadge }}">{{ status_label($account->status) }}</span>
                        </td>
                        <td>
                            <x-ssl-info :model="$account" />
                        </td>
                        <td>
                            <x-uptime-badge :model="$account" />
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex align-items-center gap-1">
                                @if ($account->cache_enabled)
                                    <span class="badge text-bg-success" title="{{ __('Cache ligado') }}"><i class="bi bi-speedometer2"></i></span>
                                @endif
                                <a href="{{ route('client.hosting-accounts.php.index', $account) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('PHP') }}">
                                    <i class="bi bi-filetype-php"></i>
                                </a>
                                <form method="POST" action="{{ route('client.hosting-accounts.waf.update', $account) }}" class="d-inline"
                                      onsubmit="return confirm('{{ __('Isso reescreve a configuração desse domínio no servidor — o site fica fora do ar por um instante. Continuar?') }}')">
                                    @csrf
                                    <input type="hidden" name="enabled" value="{{ $account->waf_enabled ? '0' : '1' }}">
                                    <button type="submit" class="btn btn-sm btn-outline-{{ $account->waf_enabled ? 'success' : 'secondary' }}" title="{{ __('WAF') }}: {{ $account->waf_enabled ? __('ligado') : __('desligado') }}">
                                        <i class="bi bi-shield-{{ $account->waf_enabled ? 'check' : 'slash' }}"></i>
                                    </button>
                                </form>
                                <x-dropdown align="end">
                                    <x-slot name="trigger">
                                        <button type="button" class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                    </x-slot>
                                    <x-slot name="content">
                                        <li>
                                            <form method="POST" action="{{ route('client.hosting-accounts.cache.update', $account) }}"
                                                  onsubmit="return confirm('{{ __('Isso reescreve a configuração desse domínio no servidor — o site fica fora do ar por um instante. Continuar?') }}')">
                                                @csrf
                                                <input type="hidden" name="enabled" value="{{ $account->cache_enabled ? '0' : '1' }}">
                                                <button type="submit" class="dropdown-item">
                                                    <i class="bi bi-speedometer2 me-1"></i>
                                                    {{ __('Cache') }}: {{ $account->cache_enabled ? __('ligado') : __('desligado') }}
                                                </button>
                                            </form>
                                        </li>
                                        @if ($account->cache_enabled)
                                            <li>
                                                <form method="POST" action="{{ route('client.hosting-accounts.cache.purge', $account) }}">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item"><i class="bi bi-arrow-clockwise me-1"></i> {{ __('Limpar cache') }}</button>
                                                </form>
                                            </li>
                                        @endif
                                    </x-slot>
                                </x-dropdown>
                            </div>
                        </td>
                    </tr>
                    @foreach ($account->domains as $domain)
                        @php
                            $domainRealBase = $domain->isOutsidePublicHtml()
                                ? "domains/{$domain->domain}/public_html"
                                : "public_html/{$domain->subdirectory}";
                            $domainShortBase = $domain->isOutsidePublicHtml()
                                ? 'public_html'
                                : "public_html/{$domain->subdirectory}";
                            $domainPathSuffix = $domain->public_path ? '/'.$domain->public_path : '';
                        @endphp
                        <tr>
                            <td>{{ $domain->domain }}</td>
                            <td>{{ $domain->type === 'addon' ? __('Adicional') : __('Subdomínio') }}</td>
                            <td>
                                <code class="small" title="{{ $domainRealBase }}{{ $domainPathSuffix }}">{{ $domainShortBase }}{{ $domainPathSuffix }}</code>
                                <button type="button" class="btn btn-sm btn-link p-0 ms-1" data-bs-toggle="modal" data-bs-target="#docroot-modal-{{ $domain->id }}" title="{{ __('Editar document root') }}">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                            </td>
                            <td>
                                @php
                                    $domainBadge = match ($domain->status) {
                                        'active' => 'success',
                                        'error' => 'danger',
                                        default => 'secondary',
                                    };
                                @endphp
                                <span class="badge text-bg-{{ $domainBadge }}">{{ status_label($domain->status) }}</span>
                                @if ($domain->status === 'error' && $domain->last_error)
                                    <div class="small text-danger">{{ $domain->last_error }}</div>
                                @endif
                            </td>
                            <td>
                                <x-ssl-info :model="$domain" />
                            </td>
                            <td>
                                <x-uptime-badge :model="$domain" />
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex align-items-center gap-1">
                                    @if ($domain->cache_enabled)
                                        <span class="badge text-bg-success" title="{{ __('Cache ligado') }}"><i class="bi bi-speedometer2"></i></span>
                                    @endif
                                    <a href="{{ route('client.hosting-accounts.domains.php.index', [$account, $domain]) }}" class="btn btn-sm btn-outline-secondary" title="{{ __('PHP') }}">
                                        <i class="bi bi-filetype-php"></i>
                                    </a>
                                    <form method="POST" action="{{ route('client.hosting-accounts.domains.waf.update', [$account, $domain]) }}" class="d-inline"
                                          onsubmit="return confirm('{{ __('Isso reescreve a configuração desse domínio no servidor — o site fica fora do ar por um instante. Continuar?') }}')">
                                        @csrf
                                        <input type="hidden" name="enabled" value="{{ $domain->waf_enabled ? '0' : '1' }}">
                                        <button type="submit" class="btn btn-sm btn-outline-{{ $domain->waf_enabled ? 'success' : 'secondary' }}" title="{{ __('WAF') }}: {{ $domain->waf_enabled ? __('ligado') : __('desligado') }}">
                                            <i class="bi bi-shield-{{ $domain->waf_enabled ? 'check' : 'slash' }}"></i>
                                        </button>
                                    </form>
                                    <x-dropdown align="end">
                                        <x-slot name="trigger">
                                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                                <i class="bi bi-three-dots"></i>
                                            </button>
                                        </x-slot>
                                        <x-slot name="content">
                                            <li>
                                                <form method="POST" action="{{ route('client.hosting-accounts.domains.cache.update', [$account, $domain]) }}"
                                                      onsubmit="return confirm('{{ __('Isso reescreve a configuração desse domínio no servidor — o site fica fora do ar por um instante. Continuar?') }}')">
                                                    @csrf
                                                    <input type="hidden" name="enabled" value="{{ $domain->cache_enabled ? '0' : '1' }}">
                                                    <button type="submit" class="dropdown-item">
                                                        <i class="bi bi-speedometer2 me-1"></i>
                                                        {{ __('Cache') }}: {{ $domain->cache_enabled ? __('ligado') : __('desligado') }}
                                                    </button>
                                                </form>
                                            </li>
                                            @if ($domain->cache_enabled)
                                                <li>
                                                    <form method="POST" action="{{ route('client.hosting-accounts.domains.cache.purge', [$account, $domain]) }}">
                                                        @csrf
                                                        <button type="submit" class="dropdown-item"><i class="bi bi-arrow-clockwise me-1"></i> {{ __('Limpar cache') }}</button>
                                                    </form>
                                                </li>
                                            @endif
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <form method="POST" action="{{ route('client.hosting-accounts.domains.destroy', [$account, $domain]) }}"
                                                      onsubmit="return confirm('{{ __('Remove esse domínio. Continuar?') }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger"><i class="bi bi-trash me-1"></i> {{ __('Remover') }}</button>
                                                </form>
                                            </li>
                                        </x-slot>
                                    </x-dropdown>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @unless ($account->status === 'active')
            <p class="small text-secondary mb-0">{{ __('Disponível quando a conta estiver ativa.') }}</p>
        @endunless
    </div>
</div>

@if ($account->status === 'active')
    <x-modal name="docroot-modal-primary" maxWidth="sm">
        <form method="POST" action="{{ route('client.hosting-accounts.public-path.update', $account) }}">
            @csrf
            @method('PATCH')
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Document root — :domain', ['domain' => $account->primary_domain]) }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <x-input-label for="public_path-primary" value="{{ __('Subpasta dentro de public_html') }}" class="small mb-1" />
                <x-text-input id="public_path-primary" name="public_path" type="text" :value="$account->public_path" placeholder="{{ __('vazio = raiz') }}" />
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancelar') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('Salvar') }}</button>
            </div>
        </form>
    </x-modal>
@endif

@foreach ($account->domains as $domain)
    @php
        $domainRealBase = $domain->isOutsidePublicHtml()
            ? "domains/{$domain->domain}/public_html"
            : "public_html/{$domain->subdirectory}";
    @endphp
    <x-modal name="docroot-modal-{{ $domain->id }}" maxWidth="sm">
        <form method="POST" action="{{ route('client.hosting-accounts.domains.public-path.update', [$account, $domain]) }}">
            @csrf
            @method('PATCH')
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Document root — :domain', ['domain' => $domain->domain]) }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <x-input-label for="public_path-{{ $domain->id }}" value="{{ __('Subpasta dentro de :base', ['base' => $domainRealBase]) }}" class="small mb-1" />
                <x-text-input id="public_path-{{ $domain->id }}" name="public_path" type="text" :value="$domain->public_path" placeholder="{{ __('vazio = raiz') }}" />
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancelar') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('Salvar') }}</button>
            </div>
        </form>
    </x-modal>
@endforeach

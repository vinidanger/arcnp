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
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $account->primary_domain }}</td>
                        <td><span class="badge text-bg-primary">{{ __('Principal') }}</span></td>
                        <td>
                            <form method="POST" action="{{ route('client.hosting-accounts.public-path.update', $account) }}" class="d-flex align-items-center gap-1">
                                @csrf
                                @method('PATCH')
                                <code class="small">public_html{{ $account->public_path ? '/'.$account->public_path : '' }}</code>
                                <input type="text" name="public_path" value="{{ $account->public_path }}" placeholder="public" class="form-control form-control-sm" style="width: 6rem;">
                                <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('OK') }}</button>
                            </form>
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
                        <td class="text-end">
                            <a href="{{ route('client.hosting-accounts.php.index', $account) }}" class="btn btn-sm btn-outline-secondary">{{ __('PHP') }}</a>
                            <form method="POST" action="{{ route('client.hosting-accounts.waf.update', $account) }}" class="d-inline"
                                  onsubmit="return confirm('{{ __('Isso reescreve a configuração desse domínio no servidor — o site fica fora do ar por um instante. Continuar?') }}')">
                                @csrf
                                <input type="hidden" name="enabled" value="{{ $account->waf_enabled ? '0' : '1' }}">
                                <button type="submit" class="btn btn-sm btn-outline-{{ $account->waf_enabled ? 'success' : 'secondary' }}" title="{{ __('Proteção contra exploits web (ModSecurity)') }}">
                                    {{ __('WAF') }}: {{ $account->waf_enabled ? __('ligado') : __('desligado') }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    @foreach ($account->domains as $domain)
                        <tr>
                            <td>{{ $domain->domain }}</td>
                            <td>{{ $domain->type === 'addon' ? __('Adicional') : __('Subdomínio') }}</td>
                            <td>
                                @php
                                    $domainBase = $domain->isOutsidePublicHtml()
                                        ? "domains/{$domain->domain}/public_html"
                                        : "public_html/{$domain->subdirectory}";
                                @endphp
                                <form method="POST" action="{{ route('client.hosting-accounts.domains.public-path.update', [$account, $domain]) }}" class="d-flex align-items-center gap-1">
                                    @csrf
                                    @method('PATCH')
                                    <code class="small">{{ $domainBase }}{{ $domain->public_path ? '/'.$domain->public_path : '' }}</code>
                                    <input type="text" name="public_path" value="{{ $domain->public_path }}" placeholder="public" class="form-control form-control-sm" style="width: 6rem;">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('OK') }}</button>
                                </form>
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
                            <td class="text-end">
                                <a href="{{ route('client.hosting-accounts.domains.php.index', [$account, $domain]) }}" class="btn btn-sm btn-outline-secondary">{{ __('PHP') }}</a>
                                <form method="POST" action="{{ route('client.hosting-accounts.domains.waf.update', [$account, $domain]) }}" class="d-inline"
                                      onsubmit="return confirm('{{ __('Isso reescreve a configuração desse domínio no servidor — o site fica fora do ar por um instante. Continuar?') }}')">
                                    @csrf
                                    <input type="hidden" name="enabled" value="{{ $domain->waf_enabled ? '0' : '1' }}">
                                    <button type="submit" class="btn btn-sm btn-outline-{{ $domain->waf_enabled ? 'success' : 'secondary' }}" title="{{ __('Proteção contra exploits web (ModSecurity)') }}">
                                        {{ __('WAF') }}: {{ $domain->waf_enabled ? __('ligado') : __('desligado') }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('client.hosting-accounts.domains.destroy', [$account, $domain]) }}"
                                      class="d-inline"
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

        @unless ($account->status === 'active')
            <p class="small text-secondary mb-0">{{ __('Disponível quando a conta estiver ativa.') }}</p>
        @endunless
    </div>
</div>

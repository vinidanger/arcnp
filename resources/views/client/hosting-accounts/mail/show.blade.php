<x-client-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">{{ __('E-mail') }} — {{ $mailDomain->domain }}</h1>
            <a href="{{ route('client.hosting-accounts.mail.index', $account) }}" class="btn btn-sm btn-outline-secondary">{{ __('Voltar') }}</a>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if (session('plain_mailbox_password'))
        <div class="alert alert-warning">
            <strong>{{ __('Senha da caixa — copie agora, não aparece de novo.') }}</strong>
            <pre class="mb-0 mt-2 bg-white p-2 rounded border small">{{ session('plain_mailbox_password') }}</pre>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h2 class="h6 mb-1">{{ __('Deliverability (SPF / DKIM / DMARC)') }}</h2>
                    <p class="small text-secondary mb-2">
                        {{ __('Registros TXT que ajudam seus e-mails a não caírem em spam. Se o domínio tem zona DNS gerenciada por esse Painel, dá pra criar automaticamente.') }}
                    </p>
                </div>
                @if ($hasDnsZone)
                    <form method="POST" action="{{ route('client.hosting-accounts.mail.dns-records.store', [$account, $mailDomain]) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-primary text-nowrap">{{ __('Criar registros na zona DNS') }}</button>
                    </form>
                @endif
            </div>

            @unless ($hasDnsZone)
                <p class="small text-warning">{{ __('Esse domínio não tem zona DNS gerenciada por esse Painel — copie os valores abaixo e cadastre manualmente onde o DNS dele estiver hospedado.') }}</p>
            @endunless

            <table class="table table-sm mb-0 align-middle">
                <tbody>
                    <tr>
                        <td class="text-nowrap"><code>TXT @</code></td>
                        <td><code class="small">{{ $mailDomain->spfRecordValue() }}</code></td>
                    </tr>
                    <tr>
                        <td class="text-nowrap"><code>TXT {{ $mailDomain->dkimSelector() }}._domainkey</code></td>
                        <td>
                            @if ($mailDomain->dkim_txt_value)
                                <code class="small">{{ \Illuminate\Support\Str::limit($mailDomain->dkim_txt_value, 80) }}</code>
                            @else
                                <span class="text-secondary small">{{ __('Ainda sincronizando...') }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-nowrap"><code>TXT _dmarc</code></td>
                        <td><code class="small">{{ $mailDomain->dmarcRecordValue() }}</code></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6">{{ __('Nova caixa de e-mail') }}</h2>
            <form method="POST" action="{{ route('client.hosting-accounts.mail.mailboxes.store', [$account, $mailDomain]) }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-auto">
                    <x-input-label for="local_part" value="{{ __('Caixa') }}" class="small mb-1" />
                    <div class="input-group input-group-sm">
                        <input type="text" id="local_part" name="local_part" value="{{ old('local_part') }}" class="form-control form-control-sm" placeholder="contato" required>
                        <span class="input-group-text">{{ '@'.$mailDomain->domain }}</span>
                    </div>
                </div>
                <div class="col-auto">
                    <x-input-label for="password" value="{{ __('Senha (opcional, gera uma se vazio)') }}" class="small mb-1" />
                    <input type="password" id="password" name="password" class="form-control form-control-sm" minlength="8">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">{{ __('Criar') }}</button>
                </div>
            </form>
            <x-input-error :messages="$errors->get('local_part')" class="mt-2" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
    </div>

    <div class="card mb-3">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Caixa') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($mailDomain->mailboxes as $mailbox)
                        <tr>
                            <td>{{ $mailbox->email() }}</td>
                            <td class="text-end">
                                <a href="{{ route('client.hosting-accounts.mail.mailboxes.webmail', [$account, $mailDomain, $mailbox]) }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">{{ __('Abrir webmail') }}</a>
                                <details class="d-inline-block">
                                    <summary class="btn btn-sm btn-outline-secondary">{{ __('Trocar senha') }}</summary>
                                    <form method="POST" action="{{ route('client.hosting-accounts.mail.mailboxes.password.update', [$account, $mailDomain, $mailbox]) }}" class="row g-2 align-items-end mt-2">
                                        @csrf
                                        @method('PUT')
                                        <div class="col-auto">
                                            <input type="password" name="password" class="form-control form-control-sm" placeholder="{{ __('Nova senha') }}" minlength="8" required>
                                        </div>
                                        <div class="col-auto">
                                            <input type="password" name="password_confirmation" class="form-control form-control-sm" placeholder="{{ __('Confirmar') }}" minlength="8" required>
                                        </div>
                                        <div class="col-auto">
                                            <button type="submit" class="btn btn-sm btn-primary">{{ __('Salvar') }}</button>
                                        </div>
                                    </form>
                                </details>
                                <form method="POST" action="{{ route('client.hosting-accounts.mail.mailboxes.destroy', [$account, $mailDomain, $mailbox]) }}"
                                      class="d-inline-block" onsubmit="return confirm('{{ __('Remove essa caixa. Continuar?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Remover') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="text-center text-secondary py-4">{{ __('Nenhuma caixa cadastrada.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6">{{ __('Novo encaminhamento') }}</h2>
            <p class="small text-secondary mb-2">{{ __('Redireciona um endereço @'.$mailDomain->domain.' pra outro e-mail, sem criar caixa própria.') }}</p>
            <form method="POST" action="{{ route('client.hosting-accounts.mail.forwarders.store', [$account, $mailDomain]) }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-auto">
                    <x-input-label for="forwarder_local_part" value="{{ __('De') }}" class="small mb-1" />
                    <div class="input-group input-group-sm">
                        <input type="text" id="forwarder_local_part" name="local_part" value="{{ old('local_part') }}" class="form-control form-control-sm" placeholder="vendas" required>
                        <span class="input-group-text">{{ '@'.$mailDomain->domain }}</span>
                    </div>
                </div>
                <div class="col-auto">
                    <x-input-label for="forwarder_destination" value="{{ __('Para') }}" class="small mb-1" />
                    <input type="email" id="forwarder_destination" name="destination" value="{{ old('destination') }}" class="form-control form-control-sm" placeholder="alguem@outrodominio.com" required>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">{{ __('Criar') }}</button>
                </div>
            </form>
            <x-input-error :messages="$errors->get('local_part')" class="mt-2" />
            <x-input-error :messages="$errors->get('destination')" class="mt-2" />
        </div>
    </div>

    <div class="card mb-3">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('De') }}</th>
                        <th>{{ __('Para') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($mailDomain->forwarders as $forwarder)
                        <tr>
                            <td>{{ $forwarder->source() }}</td>
                            <td>{{ $forwarder->destination }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('client.hosting-accounts.mail.forwarders.destroy', [$account, $mailDomain, $forwarder]) }}"
                                      onsubmit="return confirm('{{ __('Remove esse encaminhamento. Continuar?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Remover') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-secondary py-4">{{ __('Nenhum encaminhamento cadastrado.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card border-danger">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h6 mb-1">{{ __('Desativar e-mail nesse domínio') }}</h2>
                <p class="small text-secondary mb-0">
                    @if ($mailDomain->mailboxes->isNotEmpty() || $mailDomain->forwarders->isNotEmpty())
                        {{ __('Remova todas as caixas e encaminhamentos antes de desativar.') }}
                    @else
                        {{ __('Remove a assinatura DKIM e libera o domínio pra ativar de novo depois.') }}
                    @endif
                </p>
            </div>
            <form method="POST" action="{{ route('client.hosting-accounts.mail.destroy', [$account, $mailDomain]) }}"
                  onsubmit="return confirm('{{ __('Desativa o e-mail desse domínio. Continuar?') }}')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger" @disabled($mailDomain->mailboxes->isNotEmpty() || $mailDomain->forwarders->isNotEmpty())>{{ __('Desativar') }}</button>
            </form>
        </div>
    </div>
</x-client-layout>

<x-admin-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">{{ __('E-mail') }} — {{ $mailDomain->domain }}</h1>
            <a href="{{ route('admin.hosting-accounts.mail.index', $account) }}" class="btn btn-sm btn-outline-secondary">{{ __('Voltar') }}</a>
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
            <pre class="mb-0 mt-2 bg-body-secondary p-2 rounded border small">{{ session('plain_mailbox_password') }}</pre>
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
                    <form method="POST" action="{{ route('admin.hosting-accounts.mail.dns-records.store', [$account, $mailDomain]) }}">
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
            <h2 class="h6 mb-1">{{ __('Registros MX') }}</h2>
            <p class="small text-secondary mb-2">
                {{ __('Definem pra onde o e-mail desse domínio deve ser entregue. Se o e-mail é hospedado nesse servidor, aponte pro hostname de e-mail dele.') }}
            </p>

            @if ($dnsZone)
                <table class="table table-sm align-middle mb-3">
                    <thead>
                        <tr>
                            <th>{{ __('Prioridade') }}</th>
                            <th>{{ __('Servidor') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($dnsZone->records->where('type', 'MX') as $record)
                            <tr>
                                <td>{{ $record->priority }}</td>
                                <td><code class="small">{{ $record->content }}</code></td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('admin.hosting-accounts.dns.records.destroy', [$account, $dnsZone, $record]) }}"
                                          onsubmit="return confirm('{{ __('Remove esse registro MX. Continuar?') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Remover') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-secondary py-3">{{ __('Nenhum registro MX cadastrado.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <form method="POST" action="{{ route('admin.hosting-accounts.dns.records.store', [$account, $dnsZone]) }}" class="row g-2 align-items-end">
                    @csrf
                    <input type="hidden" name="type" value="MX">
                    <input type="hidden" name="name" value="@">
                    <input type="hidden" name="ttl" value="3600">
                    <div class="col-auto">
                        <x-input-label for="mx_priority" value="{{ __('Prioridade') }}" class="small mb-1" />
                        <input type="number" id="mx_priority" name="priority" value="{{ old('priority', 10) }}" class="form-control form-control-sm" style="width: 6rem;" min="0" max="65535" required>
                    </div>
                    <div class="col-auto">
                        <x-input-label for="mx_content" value="{{ __('Servidor de e-mail') }}" class="small mb-1" />
                        <input type="text" id="mx_content" name="content" value="{{ old('content') }}" class="form-control form-control-sm" placeholder="mail.{{ $mailDomain->domain }}" required>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-primary">{{ __('Adicionar') }}</button>
                    </div>
                </form>
                <x-input-error :messages="$errors->get('priority')" class="mt-2" />
                <x-input-error :messages="$errors->get('content')" class="mt-2" />
            @else
                <p class="small text-warning mb-0">{{ __('Esse domínio não tem zona DNS gerenciada por esse Painel — configure o registro MX manualmente onde o DNS dele estiver hospedado.') }}</p>
            @endif
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6">{{ __('Nova caixa de e-mail') }}</h2>
            <form method="POST" action="{{ route('admin.hosting-accounts.mail.mailboxes.store', [$account, $mailDomain]) }}" class="row g-2 align-items-end">
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
                                <a href="{{ route('admin.hosting-accounts.mail.mailboxes.webmail', [$account, $mailDomain, $mailbox]) }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">{{ __('Abrir webmail') }}</a>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#password-modal-{{ $mailbox->id }}">{{ __('Trocar senha') }}</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#vacation-modal-{{ $mailbox->id }}">{{ __('Aviso de férias') }}</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#filters-modal-{{ $mailbox->id }}">{{ __('Filtros') }}</button>
                                <form method="POST" action="{{ route('admin.hosting-accounts.mail.mailboxes.destroy', [$account, $mailDomain, $mailbox]) }}"
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

    {{-- Modais ficam fora da tabela de propósito: dentro de <td class="text-end">
         o text-align vazava por herança de CSS pros campos do formulário. --}}
    @foreach ($mailDomain->mailboxes as $mailbox)
        <x-modal name="password-modal-{{ $mailbox->id }}">
            <form method="POST" action="{{ route('admin.hosting-accounts.mail.mailboxes.password.update', [$account, $mailDomain, $mailbox]) }}">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Trocar senha') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-secondary text-truncate mb-3">{{ $mailbox->email() }}</p>
                    <div class="mb-3">
                        <x-input-label for="password-{{ $mailbox->id }}" value="{{ __('Nova senha') }}" class="small mb-1" />
                        <input type="password" id="password-{{ $mailbox->id }}" name="password" class="form-control form-control-sm" minlength="8" required>
                    </div>
                    <div class="mb-0">
                        <x-input-label for="password-confirmation-{{ $mailbox->id }}" value="{{ __('Confirmar') }}" class="small mb-1" />
                        <input type="password" id="password-confirmation-{{ $mailbox->id }}" name="password_confirmation" class="form-control form-control-sm" minlength="8" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancelar') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Salvar') }}</button>
                </div>
            </form>
        </x-modal>

        <x-modal name="vacation-modal-{{ $mailbox->id }}">
            <form method="POST" action="{{ route('admin.hosting-accounts.mail.mailboxes.vacation.update', [$account, $mailDomain, $mailbox]) }}">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Aviso de férias') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-secondary text-truncate mb-3">{{ $mailbox->email() }}</p>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="vacation_enabled" value="1" id="vacation-enabled-{{ $mailbox->id }}" class="form-check-input" @checked($mailbox->vacation?->enabled)>
                        <label class="form-check-label" for="vacation-enabled-{{ $mailbox->id }}">{{ __('Ativado') }}</label>
                    </div>
                    <div class="mb-3">
                        <x-input-label for="subject-{{ $mailbox->id }}" value="{{ __('Assunto') }}" class="small mb-1" />
                        <input type="text" id="subject-{{ $mailbox->id }}" name="subject" class="form-control form-control-sm" value="{{ old('subject', $mailbox->vacation?->subject) }}">
                    </div>
                    <div class="mb-0">
                        <x-input-label for="message-{{ $mailbox->id }}" value="{{ __('Mensagem') }}" class="small mb-1" />
                        <textarea id="message-{{ $mailbox->id }}" name="message" class="form-control form-control-sm" rows="4">{{ old('message', $mailbox->vacation?->message) }}</textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancelar') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Salvar') }}</button>
                </div>
            </form>
        </x-modal>

        <x-modal name="filters-modal-{{ $mailbox->id }}">
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Filtros') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="small text-secondary text-truncate mb-3">{{ $mailbox->email() }}</p>

                <table class="table table-sm align-middle mb-3">
                    <thead>
                        <tr>
                            <th>{{ __('Se') }}</th>
                            <th>{{ __('Então') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($mailbox->filters as $filter)
                            <tr>
                                <td class="small">
                                    {{ match ($filter->field) { 'from' => __('De'), 'subject' => __('Assunto'), 'to' => __('Para') } }}
                                    {{ __('contém') }} "<code>{{ $filter->value }}</code>"
                                </td>
                                <td class="small">
                                    @if ($filter->action === 'discard')
                                        {{ __('Descartar') }}
                                    @else
                                        {{ __('Mover pra') }} "<code>{{ $filter->folder }}</code>"
                                    @endif
                                </td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('admin.hosting-accounts.mail.mailboxes.filters.destroy', [$account, $mailDomain, $mailbox, $filter]) }}"
                                          onsubmit="return confirm('{{ __('Remove esse filtro. Continuar?') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Remover') }}</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-secondary small py-2">{{ __('Nenhum filtro cadastrado.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <hr>

                <form method="POST" action="{{ route('admin.hosting-accounts.mail.mailboxes.filters.store', [$account, $mailDomain, $mailbox]) }}">
                    @csrf
                    <div class="row g-2 align-items-end">
                        <div class="col-4">
                            <x-input-label for="field-{{ $mailbox->id }}" value="{{ __('Campo') }}" class="small mb-1" />
                            <select id="field-{{ $mailbox->id }}" name="field" class="form-select form-select-sm">
                                <option value="from">{{ __('De') }}</option>
                                <option value="subject">{{ __('Assunto') }}</option>
                                <option value="to">{{ __('Para') }}</option>
                            </select>
                        </div>
                        <div class="col-8">
                            <x-input-label for="value-{{ $mailbox->id }}" value="{{ __('Contém') }}" class="small mb-1" />
                            <input type="text" id="value-{{ $mailbox->id }}" name="value" class="form-control form-control-sm" required>
                        </div>
                    </div>
                    <div class="row g-2 align-items-end mt-1">
                        <div class="col-5">
                            <x-input-label for="action-{{ $mailbox->id }}" value="{{ __('Ação') }}" class="small mb-1" />
                            <select id="action-{{ $mailbox->id }}" name="action" class="form-select form-select-sm filter-action-select" data-target="#folder-wrap-{{ $mailbox->id }}">
                                <option value="discard">{{ __('Descartar') }}</option>
                                <option value="move_to_folder">{{ __('Mover pra pasta') }}</option>
                            </select>
                        </div>
                        <div class="col-7" id="folder-wrap-{{ $mailbox->id }}">
                            <x-input-label for="folder-{{ $mailbox->id }}" value="{{ __('Pasta') }}" class="small mb-1" />
                            <input type="text" id="folder-{{ $mailbox->id }}" name="folder" class="form-control form-control-sm" placeholder="Spam">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary mt-2">{{ __('Adicionar filtro') }}</button>
                </form>
                <x-input-error :messages="$errors->get('value')" class="mt-2" />
                <x-input-error :messages="$errors->get('folder')" class="mt-2" />
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Fechar') }}</button>
            </div>
        </x-modal>
    @endforeach

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6">{{ __('Novo encaminhamento') }}</h2>
            <p class="small text-secondary mb-2">{{ __('Redireciona um endereço @'.$mailDomain->domain.' pra outro e-mail, sem criar caixa própria.') }}</p>
            <form method="POST" action="{{ route('admin.hosting-accounts.mail.forwarders.store', [$account, $mailDomain]) }}" class="row g-2 align-items-end">
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
                                <form method="POST" action="{{ route('admin.hosting-accounts.mail.forwarders.destroy', [$account, $mailDomain, $forwarder]) }}"
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
            <form method="POST" action="{{ route('admin.hosting-accounts.mail.destroy', [$account, $mailDomain]) }}"
                  onsubmit="return confirm('{{ __('Desativa o e-mail desse domínio. Continuar?') }}')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger" @disabled($mailDomain->mailboxes->isNotEmpty() || $mailDomain->forwarders->isNotEmpty())>{{ __('Desativar') }}</button>
            </form>
        </div>
    </div>
</x-admin-layout>

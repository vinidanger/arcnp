<x-client-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Rastreamento de e-mails') }} — {{ $account->primary_domain }}</h1>
    </x-slot>

    @if ($logError)
        <div class="alert alert-danger">{{ $logError }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <p class="small text-secondary mb-2">
                {{ __('Mostra as entradas recentes do log de e-mail do servidor pra uma das suas caixas — não dá pra ver e-mails de outras contas.') }}
            </p>
            <form method="GET" action="{{ route('client.hosting-accounts.mail-log.index', $account) }}" class="row g-2 align-items-end">
                <div class="col-auto">
                    <x-input-label for="mailbox" value="{{ __('Caixa') }}" class="small mb-1" />
                    <select id="mailbox" name="mailbox" class="form-select form-select-sm">
                        <option value="">{{ __('Selecione...') }}</option>
                        @foreach ($mailboxes as $email)
                            <option value="{{ $email }}" @selected($mailbox === $email)>{{ $email }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <x-input-label for="lines" value="{{ __('Linhas') }}" class="small mb-1" />
                    <select id="lines" name="lines" class="form-select form-select-sm">
                        @foreach ([100, 200, 500, 1000] as $option)
                            <option value="{{ $option }}" @selected($lines === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">{{ __('Buscar') }}</button>
                </div>
            </form>
            @if (empty($mailboxes))
                <p class="small text-warning mt-2 mb-0">{{ __('Essa conta ainda não tem nenhuma caixa de e-mail cadastrada.') }}</p>
            @endif
        </div>
    </div>

    @if ($mailbox && ! $logError)
        <div class="card">
            <div class="card-body">
                <pre class="mb-0 small" style="max-height: 32rem; overflow: auto; white-space: pre-wrap; word-break: break-all;">{{ $content !== '' ? $content : __('(nenhuma entrada encontrada)') }}</pre>
            </div>
        </div>
    @endif
</x-client-layout>

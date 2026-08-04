<x-admin-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Rastreamento de e-mails') }} — {{ $account->primary_domain }}</h1>
    </x-slot>

    @if ($logError)
        <div class="alert alert-danger">{{ $logError }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <p class="small text-secondary mb-2">
                {{ __('Busca por um endereço no log de e-mail do servidor (grep nas últimas 5000 linhas). Sem busca, mostra o log inteiro recente do servidor.') }}
            </p>
            <form method="GET" action="{{ route('admin.hosting-accounts.mail-log.index', $account) }}" class="row g-2 align-items-end">
                <div class="col-auto">
                    <x-input-label for="search" value="{{ __('Buscar (endereço, domínio, etc)') }}" class="small mb-1" />
                    <input type="text" id="search" name="search" value="{{ $search }}" class="form-control form-control-sm" placeholder="contato@exemplo.com">
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
        </div>
    </div>

    @unless ($logError)
        <div class="card">
            <div class="card-body">
                <pre class="mb-0 small" style="max-height: 32rem; overflow: auto; white-space: pre-wrap; word-break: break-all;">{{ $content !== '' ? $content : __('(nenhuma entrada encontrada)') }}</pre>
            </div>
        </div>
    @endunless
</x-admin-layout>

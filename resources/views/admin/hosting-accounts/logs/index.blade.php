<x-admin-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Logs do domínio') }} — {{ $account->primary_domain }}</h1>
    </x-slot>

    @if ($logError)
        <div class="alert alert-danger">{{ $logError }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.hosting-accounts.logs.index', $account) }}" class="row g-2 align-items-end">
                <div class="col-auto">
                    <x-input-label for="domain" value="{{ __('Domínio') }}" class="small mb-1" />
                    <select id="domain" name="domain" class="form-select form-select-sm">
                        @foreach ($domains as $d)
                            <option value="{{ $d }}" @selected($domain === $d)>{{ $d }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <x-input-label for="type" value="{{ __('Tipo') }}" class="small mb-1" />
                    <select id="type" name="type" class="form-select form-select-sm">
                        <option value="access" @selected($type === 'access')>{{ __('Acesso') }}</option>
                        <option value="error" @selected($type === 'error')>{{ __('Erro') }}</option>
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
                    <button type="submit" class="btn btn-sm btn-primary">{{ __('Atualizar') }}</button>
                </div>
            </form>
        </div>
    </div>

    @unless ($logError)
        <div class="card">
            <div class="card-body">
                <pre class="mb-0 small" style="max-height: 32rem; overflow: auto; white-space: pre-wrap; word-break: break-all;">{{ $content !== '' ? $content : __('(vazio)') }}</pre>
            </div>
        </div>
    @endunless
</x-admin-layout>

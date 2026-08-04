<x-admin-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Proteção Hotlink') }} — {{ $account->primary_domain }}</h1>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <p class="small text-secondary mb-0">
                {{ __('Bloqueia outros sites de exibir suas imagens/arquivos direto (hotlinking) — só o próprio domínio (e quem estiver na lista de exceções) pode carregá-los.') }}
            </p>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Domínio') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Extensões') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($domains as $domain)
                        @php $protection = $protections->get($domain); @endphp
                        <tr>
                            <td>{{ $domain }}</td>
                            <td>
                                <span class="badge text-bg-{{ $protection?->enabled ? 'success' : 'secondary' }}">
                                    {{ $protection?->enabled ? __('Ativado') : __('Desativado') }}
                                </span>
                            </td>
                            <td class="small text-secondary">{{ $protection ? implode(', ', $protection->extensions) : '—' }}</td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#hotlink-modal-{{ \Illuminate\Support\Str::slug($domain) }}">{{ __('Configurar') }}</button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @foreach ($domains as $domain)
        @php $protection = $protections->get($domain); @endphp
        <x-modal name="hotlink-modal-{{ \Illuminate\Support\Str::slug($domain) }}">
            <form method="POST" action="{{ route('admin.hosting-accounts.hotlink-protection.update', $account) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="domain" value="{{ $domain }}">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Proteção Hotlink') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-secondary text-truncate mb-3">{{ $domain }}</p>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="enabled" value="1" id="enabled-{{ \Illuminate\Support\Str::slug($domain) }}" class="form-check-input" @checked($protection?->enabled)>
                        <label class="form-check-label" for="enabled-{{ \Illuminate\Support\Str::slug($domain) }}">{{ __('Ativado') }}</label>
                    </div>
                    <div class="mb-3">
                        <x-input-label for="extensions-{{ \Illuminate\Support\Str::slug($domain) }}" value="{{ __('Extensões protegidas (separadas por vírgula)') }}" class="small mb-1" />
                        <input type="text" id="extensions-{{ \Illuminate\Support\Str::slug($domain) }}" name="extensions" class="form-control form-control-sm"
                               value="{{ old('extensions', $protection ? implode(',', $protection->extensions) : 'jpg,jpeg,png,gif,webp,svg,bmp,ico') }}">
                    </div>
                    <div class="mb-0">
                        <x-input-label for="allowed-referrers-{{ \Illuminate\Support\Str::slug($domain) }}" value="{{ __('Domínios com exceção (opcional, separados por vírgula)') }}" class="small mb-1" />
                        <input type="text" id="allowed-referrers-{{ \Illuminate\Support\Str::slug($domain) }}" name="allowed_referrers" class="form-control form-control-sm"
                               placeholder="cdn.parceiro.com" value="{{ old('allowed_referrers', $protection ? implode(',', $protection->allowed_referrers ?? []) : '') }}">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancelar') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Salvar') }}</button>
                </div>
            </form>
        </x-modal>
    @endforeach
</x-admin-layout>

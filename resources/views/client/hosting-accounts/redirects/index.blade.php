<x-client-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Redirecionamentos de site') }} — {{ $account->primary_domain }}</h1>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6">{{ __('Novo redirecionamento') }}</h2>
            <p class="small text-secondary mb-2">
                {{ __('Quem acessar esse caminho é redirecionado pra URL de destino exata (não preserva o resto do caminho digitado).') }}
            </p>
            <form method="POST" action="{{ route('client.hosting-accounts.redirects.store', $account) }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-auto">
                    <x-input-label for="domain" value="{{ __('Domínio') }}" class="small mb-1" />
                    <select id="domain" name="domain" class="form-select form-select-sm">
                        @foreach ($domains as $domain)
                            <option value="{{ $domain }}" @selected(old('domain') === $domain)>{{ $domain }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <x-input-label for="path" value="{{ __('Caminho') }}" class="small mb-1" />
                    <input type="text" id="path" name="path" value="{{ old('path') }}" class="form-control form-control-sm" placeholder="/promocao" required>
                </div>
                <div class="col-auto">
                    <x-input-label for="destination" value="{{ __('Destino') }}" class="small mb-1" />
                    <input type="url" id="destination" name="destination" value="{{ old('destination') }}" class="form-control form-control-sm" placeholder="https://outrosite.com/pagina" required>
                </div>
                <div class="col-auto">
                    <x-input-label for="status_code" value="{{ __('Tipo') }}" class="small mb-1" />
                    <select id="status_code" name="status_code" class="form-select form-select-sm">
                        <option value="301" @selected(old('status_code', '301') === '301')>{{ __('301 (permanente)') }}</option>
                        <option value="302" @selected(old('status_code') === '302')>{{ __('302 (temporário)') }}</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">{{ __('Criar') }}</button>
                </div>
            </form>
            <x-input-error :messages="$errors->get('domain')" class="mt-2" />
            <x-input-error :messages="$errors->get('path')" class="mt-2" />
            <x-input-error :messages="$errors->get('destination')" class="mt-2" />
            <x-input-error :messages="$errors->get('status_code')" class="mt-2" />
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Domínio') }}</th>
                        <th>{{ __('Caminho') }}</th>
                        <th>{{ __('Destino') }}</th>
                        <th>{{ __('Tipo') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($account->siteRedirects as $redirect)
                        <tr>
                            <td>{{ $redirect->domain }}</td>
                            <td><code>{{ $redirect->path }}</code></td>
                            <td class="text-truncate" style="max-width: 20rem;"><code class="small">{{ $redirect->destination }}</code></td>
                            <td>{{ $redirect->status_code }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('client.hosting-accounts.redirects.destroy', [$account, $redirect]) }}"
                                      onsubmit="return confirm('{{ __('Remove esse redirecionamento. Continuar?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Remover') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-secondary py-4">{{ __('Nenhum redirecionamento cadastrado.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-client-layout>

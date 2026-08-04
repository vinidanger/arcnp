<x-client-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Proteção de pasta com senha') }} — {{ $account->primary_domain }}</h1>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6">{{ __('Nova proteção') }}</h2>
            <p class="small text-secondary mb-2">
                {{ __('Exige usuário e senha pra acessar um caminho do site. O caminho é relativo à raiz do domínio (ex: /admin).') }}
            </p>
            <form method="POST" action="{{ route('client.hosting-accounts.protected-folders.store', $account) }}" class="row g-2 align-items-end">
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
                    <input type="text" id="path" name="path" value="{{ old('path') }}" class="form-control form-control-sm" placeholder="/admin" required>
                </div>
                <div class="col-auto">
                    <x-input-label for="username" value="{{ __('Usuário') }}" class="small mb-1" />
                    <input type="text" id="username" name="username" value="{{ old('username') }}" class="form-control form-control-sm" required>
                </div>
                <div class="col-auto">
                    <x-input-label for="password" value="{{ __('Senha') }}" class="small mb-1" />
                    <input type="password" id="password" name="password" class="form-control form-control-sm" minlength="8" required>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">{{ __('Proteger') }}</button>
                </div>
            </form>
            <x-input-error :messages="$errors->get('domain')" class="mt-2" />
            <x-input-error :messages="$errors->get('path')" class="mt-2" />
            <x-input-error :messages="$errors->get('username')" class="mt-2" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Domínio') }}</th>
                        <th>{{ __('Caminho') }}</th>
                        <th>{{ __('Usuário') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($account->folderProtections as $protection)
                        <tr>
                            <td>{{ $protection->domain }}</td>
                            <td><code>{{ $protection->path }}</code></td>
                            <td>{{ $protection->username }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('client.hosting-accounts.protected-folders.destroy', [$account, $protection]) }}"
                                      onsubmit="return confirm('{{ __('Remove essa proteção. Continuar?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Remover') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-secondary py-4">{{ __('Nenhuma pasta protegida.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-client-layout>

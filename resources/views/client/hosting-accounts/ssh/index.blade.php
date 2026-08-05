<x-client-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Acesso SSH') }} — {{ $account->primary_domain }}</h1>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @php $sshHost = $account->server->public_ip_address ?: $account->server->ip_address; @endphp

    @if (session('plain_ssh_password'))
        <div class="alert alert-warning">
            <strong>{{ __('Senha SSH — copie agora, não aparece de novo.') }}</strong>
            <pre class="mb-0 mt-2 bg-body-secondary p-2 rounded border small">{{ session('plain_ssh_password') }}</pre>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6">{{ __('Como conectar') }}</h2>
            <dl class="row mb-2 small">
                <dt class="col-2">{{ __('Host') }}</dt>
                <dd class="col-10"><code>{{ $sshHost }}</code></dd>
                <dt class="col-2">{{ __('Porta') }}</dt>
                <dd class="col-10"><code>22</code></dd>
                <dt class="col-2">{{ __('Usuário') }}</dt>
                <dd class="col-10"><code>{{ $account->linux_username }}</code></dd>
            </dl>
            <div class="d-flex gap-2 align-items-start">
                <pre id="ssh-command" class="mb-0 bg-body-secondary p-2 rounded border small flex-grow-1">ssh {{ $account->linux_username.'@'.$sshHost }} -p 22</pre>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="navigator.clipboard.writeText(document.getElementById('ssh-command').textContent.trim()); this.textContent='{{ __('Copiado!') }}'; setTimeout(() => this.textContent='{{ __('Copiar') }}', 1500);">{{ __('Copiar') }}</button>
            </div>
            @unless ($account->ssh_enabled)
                <p class="small text-secondary mt-2 mb-0">{{ __('Libere o acesso abaixo pra esses dados funcionarem — a senha já é a mesma que você usa pra entrar no painel.') }}</p>
            @endunless
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="h6 mb-1">{{ __('Shell de login') }}</h2>
                    <p class="small text-secondary mb-0">
                        {{ __('Login por senha (a mesma do seu login no painel — troque em Perfil) e por chave pública, ambos liberados junto.') }}
                    </p>
                </div>
                <div class="d-flex gap-2">
                    @if ($account->ssh_enabled)
                        <form method="POST" action="{{ route('client.hosting-accounts.ssh.password.regenerate', $account) }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('Gerar nova senha') }}</button>
                        </form>
                    @endif
                    <form method="POST" action="{{ route('client.hosting-accounts.ssh.toggle', $account) }}">
                        @csrf
                        <input type="hidden" name="enabled" value="{{ $account->ssh_enabled ? '0' : '1' }}">
                        <button type="submit" class="btn btn-sm {{ $account->ssh_enabled ? 'btn-outline-danger' : 'btn-outline-success' }}">
                            {{ $account->ssh_enabled ? __('Revogar acesso SSH') : __('Liberar acesso SSH') }}
                        </button>
                    </form>
                </div>
            </div>
            <div class="mt-2">
                <span class="badge text-bg-{{ $account->ssh_enabled ? 'success' : 'secondary' }}">
                    {{ $account->ssh_enabled ? __('Liberado') : __('Desativado') }}
                </span>
            </div>

            @if ($account->ssh_enabled)
                <hr>
                <h3 class="h6">{{ __('Definir minha senha') }}</h3>
                <p class="small text-secondary">{{ __('Mesma senha do login no painel — trocar aqui ou em Perfil dá no mesmo.') }}</p>
                <form method="POST" action="{{ route('client.hosting-accounts.ssh.password.update', $account) }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-auto">
                        <x-input-label for="password" value="{{ __('Nova senha') }}" class="small mb-1" />
                        <input type="password" id="password" name="password" class="form-control form-control-sm" minlength="8" required>
                    </div>
                    <div class="col-auto">
                        <x-input-label for="password_confirmation" value="{{ __('Confirmar senha') }}" class="small mb-1" />
                        <input type="password" id="password_confirmation" name="password_confirmation" class="form-control form-control-sm" minlength="8" required>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-primary">{{ __('Salvar senha') }}</button>
                    </div>
                </form>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            @endif
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6">{{ __('Nova chave pública') }}</h2>
            <form method="POST" action="{{ route('client.hosting-accounts.ssh.keys.store', $account) }}">
                @csrf
                <div class="row g-2">
                    <div class="col-md-4">
                        <x-input-label for="name" value="{{ __('Nome') }}" class="small mb-1" />
                        <input type="text" id="name" name="name" class="form-control form-control-sm" placeholder="meu notebook" required>
                    </div>
                    <div class="col-md-8">
                        <x-input-label for="public_key" value="{{ __('Chave pública') }}" class="small mb-1" />
                        <input type="text" id="public_key" name="public_key" class="form-control form-control-sm" style="font-family: monospace;" placeholder="ssh-ed25519 AAAA... user@host" required>
                    </div>
                </div>
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
                <x-input-error :messages="$errors->get('public_key')" class="mt-2" />
                <button type="submit" class="btn btn-sm btn-primary mt-2">{{ __('Adicionar chave') }}</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Nome') }}</th>
                        <th>{{ __('Chave') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($account->sshKeys as $key)
                        <tr>
                            <td>{{ $key->name }}</td>
                            <td><code class="small">{{ \Illuminate\Support\Str::limit($key->public_key, 60) }}</code></td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('client.hosting-accounts.ssh.keys.destroy', [$account, $key]) }}"
                                      onsubmit="return confirm('{{ __('Remove essa chave. Continuar?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Remover') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-secondary py-4">{{ __('Nenhuma chave cadastrada.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-client-layout>

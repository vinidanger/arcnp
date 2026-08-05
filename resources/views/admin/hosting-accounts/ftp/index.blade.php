<x-admin-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Contas de FTP') }} — {{ $account->primary_domain }}</h1>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if (session('plain_ftp_password'))
        <div class="alert alert-warning">
            <strong>{{ __('Senha da conta de FTP — copie agora, não aparece de novo.') }}</strong>
            <pre class="mb-0 mt-2 bg-body-secondary p-2 rounded border small">{{ session('plain_ftp_password') }}</pre>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <p class="small text-secondary mb-0">
                {{ __('Conecte no servidor') }}
                <code>{{ $account->server->ftp_hostname ?: ($account->server->public_ip_address ?: $account->server->ip_address) }}</code>
                {{ __('com FTPS (explícito), usando o usuário e senha de cada conta abaixo. O certificado é autoassinado — o cliente de FTP vai avisar na primeira conexão, é esperado; aceite/fixe o certificado e não pergunta de novo. Pra usar um hostname com certificado confiável em vez do IP, preencha "Hostname de FTP" ao editar o servidor.') }}
            </p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6">{{ __('Nova conta de FTP') }}</h2>
            <form method="POST" action="{{ route('admin.hosting-accounts.ftp.store', $account) }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-auto">
                    <x-input-label for="username" value="{{ __('Usuário') }}" class="small mb-1" />
                    <input type="text" id="username" name="username" value="{{ old('username') }}" class="form-control form-control-sm" minlength="3" maxlength="32" required>
                </div>
                <div class="col-auto">
                    <x-input-label for="path" value="{{ __('Pasta (opcional)') }}" class="small mb-1" />
                    <input type="text" id="path" name="path" value="{{ old('path') }}" class="form-control form-control-sm" placeholder="public_html/blog">
                </div>
                <div class="col-auto">
                    <x-input-label for="password" value="{{ __('Senha (opcional, gera uma se vazio)') }}" class="small mb-1" />
                    <input type="password" id="password" name="password" class="form-control form-control-sm" minlength="8">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">{{ __('Criar') }}</button>
                </div>
            </form>
            <div class="form-text mt-2">{{ __('Pasta relativa à conta (ex: public_html ou public_html/blog) — a pasta precisa já existir. Deixe vazio pra acesso à conta inteira.') }}</div>
            <x-input-error :messages="$errors->get('username')" class="mt-2" />
            <x-input-error :messages="$errors->get('path')" class="mt-2" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Usuário') }}</th>
                        <th>{{ __('Pasta') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($account->ftpAccounts as $ftpAccount)
                        <tr>
                            <td>{{ $ftpAccount->username }}</td>
                            <td><code class="small">{{ $ftpAccount->path ?: __('(conta inteira)') }}</code></td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#ftp-password-modal-{{ $ftpAccount->id }}">{{ __('Trocar senha') }}</button>
                                <form method="POST" action="{{ route('admin.hosting-accounts.ftp.destroy', [$account, $ftpAccount]) }}"
                                      class="d-inline-block" onsubmit="return confirm('{{ __('Remove essa conta de FTP. Continuar?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Remover') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-secondary py-4">{{ __('Nenhuma conta de FTP cadastrada.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @foreach ($account->ftpAccounts as $ftpAccount)
        <x-modal name="ftp-password-modal-{{ $ftpAccount->id }}" maxWidth="sm">
            <form method="POST" action="{{ route('admin.hosting-accounts.ftp.password.update', [$account, $ftpAccount]) }}">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Trocar senha') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-secondary text-truncate mb-3">{{ $ftpAccount->username }}</p>
                    <div class="mb-3">
                        <x-input-label for="ftp-password-{{ $ftpAccount->id }}" value="{{ __('Nova senha') }}" class="small mb-1" />
                        <input type="password" id="ftp-password-{{ $ftpAccount->id }}" name="password" class="form-control form-control-sm" minlength="8" required>
                    </div>
                    <div class="mb-0">
                        <x-input-label for="ftp-password-confirmation-{{ $ftpAccount->id }}" value="{{ __('Confirmar') }}" class="small mb-1" />
                        <input type="password" id="ftp-password-confirmation-{{ $ftpAccount->id }}" name="password_confirmation" class="form-control form-control-sm" minlength="8" required>
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

<x-admin-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">{{ __('Extensões PHP') }} — {{ $server->name }}</h1>
            <a href="{{ route('admin.servers.show', $server) }}" class="btn btn-sm btn-outline-secondary">{{ __('Voltar') }}</a>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="alert alert-info small">
        {{ __('Ativar/desativar aqui afeta TODAS as contas de hospedagem que usam essa versão de PHP nesse servidor — não é por conta. Só alterna extensões já instaladas; instalar uma extensão nova ainda exige acesso SSH ao servidor (ver deploy/README.md do Agent).') }}
    </div>

    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-auto">
                    <x-input-label for="version" value="{{ __('Versão de PHP') }}" class="small mb-1" />
                    <select id="version" name="version" class="form-select form-select-sm" onchange="this.form.submit()">
                        @foreach (config('hosting.php_versions') as $v)
                            <option value="{{ $v }}" @selected($version === $v)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Extensão') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @if ($fetchError)
                        <tr>
                            <td colspan="3" class="text-center text-danger py-4">{{ $fetchError }}</td>
                        </tr>
                    @elseif (empty($extensions))
                        <tr>
                            <td colspan="3" class="text-center text-secondary py-4">{{ __('Nenhuma extensão encontrada pra essa versão.') }}</td>
                        </tr>
                    @else
                        @foreach ($extensions as $extension)
                            <tr>
                                <td><code>{{ $extension['name'] }}</code></td>
                                <td>
                                    <span class="badge text-bg-{{ $extension['enabled'] ? 'success' : 'secondary' }}">
                                        {{ $extension['enabled'] ? __('ativada') : __('desativada') }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('admin.servers.php-extensions.toggle', $server) }}"
                                          onsubmit="return confirm('{{ __('Isso afeta todas as contas nessa versão de PHP. Continuar?') }}')">
                                        @csrf
                                        <input type="hidden" name="version" value="{{ $version }}">
                                        <input type="hidden" name="filename" value="{{ $extension['filename'] }}">
                                        <input type="hidden" name="enable" value="{{ $extension['enabled'] ? '0' : '1' }}">
                                        <button type="submit" class="btn btn-sm btn-outline-{{ $extension['enabled'] ? 'danger' : 'success' }}">
                                            {{ $extension['enabled'] ? __('Desativar') : __('Ativar') }}
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>

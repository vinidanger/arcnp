<x-admin-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Apps (Node.js/Python)') }} — {{ $account->primary_domain }}</h1>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6">{{ __('Configurar app') }}</h2>
            <form method="POST" action="{{ route('admin.hosting-accounts.apps.store', $account) }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-auto">
                    <x-input-label for="domain" value="{{ __('Domínio') }}" class="small mb-1" />
                    <select id="domain" name="domain" class="form-select form-select-sm" required>
                        @foreach ($domains as $domain)
                            <option value="{{ $domain }}" @selected(old('domain') === $domain)>{{ $domain }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <x-input-label for="runtime" value="{{ __('Runtime') }}" class="small mb-1" />
                    <select id="runtime" name="runtime" class="form-select form-select-sm" required>
                        <option value="node" @selected(old('runtime') === 'node')>Node.js</option>
                        <option value="python" @selected(old('runtime') === 'python')>Python</option>
                    </select>
                </div>
                <div class="col-auto">
                    <x-input-label for="entry_file" value="{{ __('Arquivo de entrada') }}" class="small mb-1" />
                    <input type="text" id="entry_file" name="entry_file" value="{{ old('entry_file') }}" class="form-control form-control-sm" placeholder="index.js" required>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">{{ __('Configurar') }}</button>
                </div>
            </form>
            <div class="form-text mt-2">{{ __('O arquivo de entrada precisa já existir dentro de public_html do domínio escolhido — envie os arquivos do app antes pelo gerenciador de arquivos.') }}</div>
            <div class="form-text">{{ __('Atenção: configurar um app troca o domínio pro modo proxy — ele deixa de servir PHP/arquivo estático diretamente.') }}</div>
            <x-input-error :messages="$errors->get('domain')" class="mt-2" />
            <x-input-error :messages="$errors->get('runtime')" class="mt-2" />
            <x-input-error :messages="$errors->get('entry_file')" class="mt-2" />
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Domínio') }}</th>
                        <th>{{ __('Runtime') }}</th>
                        <th>{{ __('Arquivo') }}</th>
                        <th>{{ __('Porta interna') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($account->hostedApps as $app)
                        <tr>
                            <td>{{ $app->domain }}</td>
                            <td>{{ $app->runtime === 'node' ? 'Node.js' : 'Python' }}</td>
                            <td><code class="small">{{ $app->entry_file }}</code></td>
                            <td><code class="small">{{ $app->port }}</code></td>
                            <td>
                                @php $status = $statuses[$app->id] ?? 'unknown'; @endphp
                                <span class="badge {{ $status === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ $status }}</span>
                            </td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('admin.hosting-accounts.apps.restart', [$account, $app]) }}" class="d-inline-block">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('Reiniciar') }}</button>
                                </form>
                                <form method="POST" action="{{ route('admin.hosting-accounts.apps.destroy', [$account, $app]) }}"
                                      class="d-inline-block" onsubmit="return confirm('{{ __('Remove esse app e devolve o domínio pro modo PHP normal. Continuar?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Remover') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-4">{{ __('Nenhum app configurado.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>

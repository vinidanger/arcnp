<x-client-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">{{ __('Zona DNS') }} — {{ $zone->domain }}</h1>
            <a href="{{ route('client.hosting-accounts.dns.index', $account) }}" class="btn btn-sm btn-outline-secondary">{{ __('Voltar') }}</a>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6">{{ __('Novo registro') }}</h2>
            <form method="POST" action="{{ route('client.hosting-accounts.dns.records.store', [$account, $zone]) }}" class="row g-2 align-items-end dns-record-form">
                @csrf
                <div class="col-auto">
                    <x-input-label for="type" value="{{ __('Tipo') }}" class="small mb-1" />
                    <select id="type" name="type" class="form-select form-select-sm dns-record-type">
                        <option value="A">A</option>
                        <option value="AAAA">AAAA</option>
                        <option value="CNAME">CNAME</option>
                        <option value="MX">MX</option>
                        <option value="TXT">TXT</option>
                        <option value="NS">NS</option>
                    </select>
                </div>
                <div class="col-auto">
                    <x-input-label for="name" value="{{ __('Nome') }}" class="small mb-1" />
                    <input type="text" id="name" name="name" value="{{ old('name', '@') }}" class="form-control form-control-sm" style="width: 8rem;" required>
                </div>
                <div class="col-auto">
                    <x-input-label for="content" value="{{ __('Conteúdo') }}" class="small mb-1" />
                    <input type="text" id="content" name="content" value="{{ old('content') }}" class="form-control form-control-sm" placeholder="ex: 203.0.113.10" required>
                </div>
                <div class="col-auto dns-record-priority" style="display: none;">
                    <x-input-label for="priority" value="{{ __('Prioridade') }}" class="small mb-1" />
                    <input type="number" id="priority" name="priority" value="{{ old('priority', 10) }}" class="form-control form-control-sm" style="width: 6rem;">
                </div>
                <div class="col-auto">
                    <x-input-label for="ttl" value="{{ __('TTL') }}" class="small mb-1" />
                    <input type="number" id="ttl" name="ttl" value="{{ old('ttl', 3600) }}" class="form-control form-control-sm" style="width: 6rem;" required>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">{{ __('Adicionar') }}</button>
                </div>
            </form>
            <x-input-error :messages="$errors->get('type')" class="mt-2" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
            <x-input-error :messages="$errors->get('content')" class="mt-2" />
            <x-input-error :messages="$errors->get('priority')" class="mt-2" />
            <x-input-error :messages="$errors->get('ttl')" class="mt-2" />
            <div class="form-text mt-2">
                {{ __('Nome "@" representa a raiz do domínio. Registros CNAME/MX/NS apontando pra fora precisam terminar com ponto, ex: "exemplo.com." — sem ponto, o Agent completa automaticamente com o domínio da zona.') }}
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Tipo') }}</th>
                        <th>{{ __('Nome') }}</th>
                        <th>{{ __('Conteúdo') }}</th>
                        <th>{{ __('Prioridade') }}</th>
                        <th>{{ __('TTL') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($zone->records as $record)
                        <tr>
                            <td><code>{{ $record->type }}</code></td>
                            <td>{{ $record->name }}</td>
                            <td>{{ $record->content }}</td>
                            <td>{{ $record->priority ?? '—' }}</td>
                            <td>{{ $record->ttl }}</td>
                            <td class="text-end">
                                <details class="d-inline-block">
                                    <summary class="btn btn-sm btn-outline-secondary">{{ __('Editar') }}</summary>
                                    <form method="POST" action="{{ route('client.hosting-accounts.dns.records.update', [$account, $zone, $record]) }}" class="row g-2 align-items-end mt-2 dns-record-form">
                                        @csrf
                                        @method('PUT')
                                        <div class="col-auto">
                                            <select name="type" class="form-select form-select-sm dns-record-type">
                                                @foreach (['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS'] as $type)
                                                    <option value="{{ $type }}" @selected($record->type === $type)>{{ $type }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-auto">
                                            <input type="text" name="name" value="{{ $record->name }}" class="form-control form-control-sm" style="width: 8rem;" required>
                                        </div>
                                        <div class="col-auto">
                                            <input type="text" name="content" value="{{ $record->content }}" class="form-control form-control-sm" required>
                                        </div>
                                        <div class="col-auto dns-record-priority" style="{{ $record->type === 'MX' ? '' : 'display: none;' }}">
                                            <input type="number" name="priority" value="{{ $record->priority ?? 10 }}" class="form-control form-control-sm" style="width: 6rem;">
                                        </div>
                                        <div class="col-auto">
                                            <input type="number" name="ttl" value="{{ $record->ttl }}" class="form-control form-control-sm" style="width: 6rem;" required>
                                        </div>
                                        <div class="col-auto">
                                            <button type="submit" class="btn btn-sm btn-primary">{{ __('Salvar') }}</button>
                                        </div>
                                    </form>
                                </details>
                                <form method="POST" action="{{ route('client.hosting-accounts.dns.records.destroy', [$account, $zone, $record]) }}"
                                      class="d-inline-block" onsubmit="return confirm('{{ __('Remove esse registro. Continuar?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Remover') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-secondary py-4">{{ __('Nenhum registro cadastrado.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card border-danger">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h6 mb-1">{{ __('Apagar zona') }}</h2>
                <p class="small text-secondary mb-0">{{ __('Remove a zona e todos os registros dela do servidor DNS.') }}</p>
            </div>
            <form method="POST" action="{{ route('client.hosting-accounts.dns.destroy', [$account, $zone]) }}"
                  onsubmit="return confirm('{{ __('Apaga a zona inteira, incluindo todos os registros. Continuar?') }}')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Apagar zona') }}</button>
            </form>
        </div>
    </div>

    <script>
        document.querySelectorAll('.dns-record-form').forEach(function (form) {
            const typeSelect = form.querySelector('.dns-record-type');
            const priorityCol = form.querySelector('.dns-record-priority');

            const toggle = () => {
                priorityCol.style.display = typeSelect.value === 'MX' ? '' : 'none';
            };

            typeSelect.addEventListener('change', toggle);
            toggle();
        });
    </script>
</x-client-layout>

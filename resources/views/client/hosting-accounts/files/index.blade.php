<x-client-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Arquivos') }} — {{ $account->primary_domain }}</h1>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('client.hosting-accounts.files.index', $account) }}">public_html</a>
            </li>
            @php $accumulated = ''; @endphp
            @foreach (array_filter(explode('/', $path)) as $segment)
                @php $accumulated = trim($accumulated.'/'.$segment, '/'); @endphp
                <li class="breadcrumb-item">
                    <a href="{{ route('client.hosting-accounts.files.index', [$account, 'path' => $accumulated]) }}">{{ $segment }}</a>
                </li>
            @endforeach
        </ol>
    </nav>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-2">
                <div class="col-auto">
                    <form method="POST" action="{{ route('client.hosting-accounts.files.directories.store', $account) }}" class="d-flex gap-1">
                        @csrf
                        <input type="hidden" name="current_path" value="{{ $path }}">
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="{{ __('Nova pasta') }}" required>
                        <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('Criar') }}</button>
                    </form>
                </div>
                <div class="col-auto">
                    <form method="POST" action="{{ route('client.hosting-accounts.files.store', $account) }}" class="d-flex gap-1">
                        @csrf
                        <input type="hidden" name="current_path" value="{{ $path }}">
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="{{ __('Novo arquivo') }}" required>
                        <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('Criar') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Nome') }}</th>
                        <th>{{ __('Tamanho') }}</th>
                        <th>{{ __('Modificado') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($entries as $entry)
                        @php $entryPath = trim($path.'/'.$entry['name'], '/'); @endphp
                        <tr>
                            <td>
                                @if ($entry['type'] === 'directory')
                                    <a href="{{ route('client.hosting-accounts.files.index', [$account, 'path' => $entryPath]) }}">📁 {{ $entry['name'] }}</a>
                                @else
                                    <a href="{{ route('client.hosting-accounts.files.edit', [$account, 'path' => $entryPath]) }}">📄 {{ $entry['name'] }}</a>
                                @endif
                            </td>
                            <td class="small text-secondary">{{ $entry['size'] !== null ? number_format($entry['size'] / 1024, 1).' KB' : '—' }}</td>
                            <td class="small text-secondary">{{ \Illuminate\Support\Carbon::parse($entry['modified_at'])->format('d/m/Y H:i') }}</td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <form method="POST" action="{{ route('client.hosting-accounts.files.rename', $account) }}" class="d-flex gap-1">
                                        @csrf
                                        <input type="hidden" name="from" value="{{ $entryPath }}">
                                        <input type="text" name="name" value="{{ $entry['name'] }}" class="form-control form-control-sm" style="width: 10rem;">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('Renomear') }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('client.hosting-accounts.files.destroy', $account) }}"
                                          onsubmit="return confirm('{{ __('Remove definitivamente. Continuar?') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="path" value="{{ $entryPath }}">
                                        <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Remover') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-secondary py-4">{{ __('Pasta vazia.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-client-layout>

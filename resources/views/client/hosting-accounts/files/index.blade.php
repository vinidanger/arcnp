@php
    $entryPathOf = fn ($entry) => trim($path.'/'.$entry['name'], '/');
@endphp

<x-client-layout>
    @push('scripts')
        @vite('resources/js/file-manager.js')
    @endpush

    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Arquivos') }} — {{ $account->primary_domain }}</h1>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($outsideDomains->isNotEmpty())
        <div class="mb-3">
            <div class="small text-secondary mb-1">{{ __('Gerenciando arquivos de:') }}</div>
            <div class="btn-group btn-group-sm" role="group">
                <a href="{{ route('client.hosting-accounts.files.index', $account) }}"
                   class="btn {{ $root === null ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $account->primary_domain }} (public_html)</a>
                @foreach ($outsideDomains as $outsideDomain)
                    <a href="{{ route('client.hosting-accounts.files.index', [$account, 'root' => $outsideDomain->domain]) }}"
                       class="btn {{ $root === $outsideDomain->domain ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $outsideDomain->domain }}</a>
                @endforeach
            </div>
        </div>
    @endif

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('client.hosting-accounts.files.index', [$account, 'root' => $root]) }}">{{ $root ?? 'public_html' }}</a>
            </li>
            @php $accumulated = ''; @endphp
            @foreach (array_filter(explode('/', $path)) as $segment)
                @php $accumulated = trim($accumulated.'/'.$segment, '/'); @endphp
                <li class="breadcrumb-item">
                    <a href="{{ route('client.hosting-accounts.files.index', [$account, 'path' => $accumulated, 'root' => $root]) }}">{{ $segment }}</a>
                </li>
            @endforeach
        </ol>
    </nav>

    <div id="file-manager"
         data-upload-url="{{ route('client.hosting-accounts.files.upload', $account) }}"
         data-rename-url="{{ route('client.hosting-accounts.files.rename', $account) }}"
         data-destroy-url="{{ route('client.hosting-accounts.files.destroy', $account) }}"
         data-compress-url="{{ route('client.hosting-accounts.files.compress', $account) }}"
         data-extract-url="{{ route('client.hosting-accounts.files.extract', $account) }}"
         data-current-path="{{ $path }}"
         data-root="{{ $root }}"
         data-csrf="{{ csrf_token() }}">

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-auto">
                        <form method="POST" action="{{ route('client.hosting-accounts.files.directories.store', $account) }}" class="d-flex gap-1">
                            @csrf
                            <input type="hidden" name="current_path" value="{{ $path }}">
                            <input type="hidden" name="root" value="{{ $root }}">
                            <input type="text" name="name" class="form-control form-control-sm" placeholder="{{ __('Nova pasta') }}" required>
                            <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('Criar') }}</button>
                        </form>
                    </div>
                    <div class="col-auto">
                        <form method="POST" action="{{ route('client.hosting-accounts.files.store', $account) }}" class="d-flex gap-1">
                            @csrf
                            <input type="hidden" name="current_path" value="{{ $path }}">
                            <input type="hidden" name="root" value="{{ $root }}">
                            <input type="text" name="name" class="form-control form-control-sm" placeholder="{{ __('Novo arquivo') }}" required>
                            <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('Criar') }}</button>
                        </form>
                    </div>
                    <div class="col-auto ms-auto">
                        <button type="button" id="btn-compress-selected" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-file-earmark-zip"></i> {{ __('Compactar selecionados') }}
                        </button>
                    </div>
                </div>

                <div id="file-dropzone" class="file-dropzone">
                    <input type="file" id="file-upload-input" class="d-none" multiple>
                    <i class="bi bi-cloud-arrow-up"></i>
                    {{ __('Arraste arquivos aqui pra enviar, ou') }}
                    <button type="button" id="file-upload-browse" class="btn btn-sm btn-link p-0 align-baseline">{{ __('escolha do computador') }}</button>
                    <div id="upload-status" class="small mt-1 d-none"></div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table id="file-table" class="table table-sm mb-0 align-middle">
                    <thead>
                        <tr>
                            <th style="width: 2rem;"></th>
                            <th>{{ __('Nome') }}</th>
                            <th>{{ __('Tamanho') }}</th>
                            <th>{{ __('Modificado') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($entries as $entry)
                            @php
                                $entryPath = $entryPathOf($entry);
                                $isZip = $entry['type'] === 'file' && \Illuminate\Support\Str::endsWith(strtolower($entry['name']), '.zip');
                                $openUrl = $entry['type'] === 'directory'
                                    ? route('client.hosting-accounts.files.index', [$account, 'path' => $entryPath, 'root' => $root])
                                    : route('client.hosting-accounts.files.edit', [$account, 'path' => $entryPath, 'root' => $root]);
                            @endphp
                            <tr class="file-row" data-path="{{ $entryPath }}" data-name="{{ $entry['name'] }}" data-type="{{ $entry['type'] }}" data-zip="{{ $isZip ? '1' : '0' }}" data-href="{{ $openUrl }}">
                                <td>
                                    <input type="checkbox" class="form-check-input file-select" value="{{ $entryPath }}">
                                </td>
                                <td>
                                    <a href="{{ $openUrl }}">
                                        <i class="bi {{ $entry['type'] === 'directory' ? 'bi-folder-fill text-warning' : ($isZip ? 'bi-file-earmark-zip' : 'bi-file-earmark') }}"></i>
                                        {{ $entry['name'] }}
                                    </a>
                                </td>
                                <td class="small text-secondary">{{ $entry['size'] !== null ? number_format($entry['size'] / 1024, 1).' KB' : '—' }}</td>
                                <td class="small text-secondary">{{ \Illuminate\Support\Carbon::parse($entry['modified_at'])->format('d/m/Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-secondary py-4">{{ __('Pasta vazia — arraste arquivos aqui pra enviar.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Menu de contexto (botão direito) --}}
    <ul id="file-context-menu" class="dropdown-menu file-context-menu shadow">
        <li><a class="dropdown-item" href="#" data-ctx="open"><i class="bi bi-box-arrow-up-right me-2"></i>{{ __('Abrir') }}</a></li>
        <li><a class="dropdown-item" href="#" data-ctx="extract"><i class="bi bi-file-earmark-zip me-2"></i>{{ __('Extrair aqui') }}</a></li>
        <li><a class="dropdown-item" href="#" data-ctx="compress"><i class="bi bi-file-earmark-zip-fill me-2"></i>{{ __('Compactar') }}</a></li>
        <li><a class="dropdown-item" href="#" data-ctx="rename"><i class="bi bi-pencil me-2"></i>{{ __('Renomear') }}</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger" href="#" data-ctx="delete"><i class="bi bi-trash me-2"></i>{{ __('Remover') }}</a></li>
    </ul>

    {{-- Modal: renomear --}}
    <x-modal name="rename-modal" maxWidth="sm">
        <form method="POST" action="{{ route('client.hosting-accounts.files.rename', $account) }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Renomear') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="from" id="rename-from">
                <input type="hidden" name="root" value="{{ $root }}">
                <x-input-label for="rename-name" value="{{ __('Novo nome') }}" class="small mb-1" />
                <input type="text" id="rename-name" name="name" class="form-control" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancelar') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('Salvar') }}</button>
            </div>
        </form>
    </x-modal>

    {{-- Modal: compactar --}}
    <x-modal name="compress-modal" maxWidth="sm">
        <form method="POST" action="{{ route('client.hosting-accounts.files.compress', $account) }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Compactar') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="compress-paths"></div>
                <input type="hidden" name="current_path" id="compress-current-path">
                <input type="hidden" name="root" id="compress-root">
                <x-input-label for="compress-output" value="{{ __('Nome do arquivo .zip') }}" class="small mb-1" />
                <input type="text" id="compress-output" name="output" class="form-control" placeholder="arquivos.zip" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancelar') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('Compactar') }}</button>
            </div>
        </form>
    </x-modal>
</x-client-layout>

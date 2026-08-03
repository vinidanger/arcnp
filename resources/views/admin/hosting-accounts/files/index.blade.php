@php
    $entryPathOf = fn ($entry) => trim($path.'/'.$entry['name'], '/');
@endphp

<x-admin-layout>
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

    <div class="file-toolbar-path d-flex align-items-center gap-2 mb-3 flex-wrap">
        @if ($outsideDomains->isNotEmpty())
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-1" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-globe2"></i> {{ $root ?? $account->primary_domain }}
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <a class="dropdown-item {{ $root === null ? 'active' : '' }}" href="{{ route('admin.hosting-accounts.files.index', $account) }}">
                            {{ $account->primary_domain }} <span class="text-secondary small">(public_html)</span>
                        </a>
                    </li>
                    @foreach ($outsideDomains as $outsideDomain)
                        <li>
                            <a class="dropdown-item {{ $root === $outsideDomain->domain ? 'active' : '' }}"
                               href="{{ route('admin.hosting-accounts.files.index', [$account, 'root' => $outsideDomain->domain]) }}">
                                {{ $outsideDomain->domain }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="vr"></div>
        @endif

        <nav aria-label="breadcrumb" class="flex-grow-1">
            <ol class="breadcrumb file-breadcrumb">
                <li class="breadcrumb-item {{ $path === '' ? 'active' : '' }}">
                    @if ($path === '')
                        <i class="bi bi-house-door-fill"></i>
                    @else
                        <a href="{{ route('admin.hosting-accounts.files.index', [$account, 'root' => $root]) }}"><i class="bi bi-house-door"></i></a>
                    @endif
                </li>
                @php $accumulated = ''; @endphp
                @foreach (array_filter(explode('/', $path)) as $segment)
                    @php $accumulated = trim($accumulated.'/'.$segment, '/'); @endphp
                    <li class="breadcrumb-item {{ $accumulated === $path ? 'active' : '' }}">
                        @if ($accumulated === $path)
                            {{ $segment }}
                        @else
                            <a href="{{ route('admin.hosting-accounts.files.index', [$account, 'path' => $accumulated, 'root' => $root]) }}">{{ $segment }}</a>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    </div>

    <div id="file-manager"
         data-upload-url="{{ route('admin.hosting-accounts.files.upload', $account) }}"
         data-destroy-url="{{ route('admin.hosting-accounts.files.destroy', $account) }}"
         data-compress-url="{{ route('admin.hosting-accounts.files.compress', $account) }}"
         data-extract-url="{{ route('admin.hosting-accounts.files.extract', $account) }}"
         data-current-path="{{ $path }}"
         data-root="{{ $root }}"
         data-csrf="{{ csrf_token() }}">

        <div class="d-flex gap-2 mb-3">
            <div class="dropdown">
                <button class="btn btn-sm btn-primary dropdown-toggle d-flex align-items-center gap-1" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-plus-lg"></i> {{ __('Criar novo') }}
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#new-folder-modal">
                            <i class="bi bi-folder-plus me-2"></i>{{ __('Pasta') }}
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#new-file-modal">
                            <i class="bi bi-file-earmark-plus me-2"></i>{{ __('Arquivo') }}
                        </a>
                    </li>
                </ul>
            </div>

            <button type="button" id="btn-import" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
                <i class="bi bi-upload"></i> {{ __('Importar') }}
            </button>
            <input type="file" id="file-upload-input" class="d-none" multiple>

            <button type="button" id="btn-compress-selected" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1">
                <i class="bi bi-file-earmark-zip"></i> {{ __('Compactar selecionados') }}
            </button>

            <div id="upload-status" class="small align-self-center d-none"></div>
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
                                    ? route('admin.hosting-accounts.files.index', [$account, 'path' => $entryPath, 'root' => $root])
                                    : route('admin.hosting-accounts.files.edit', [$account, 'path' => $entryPath, 'root' => $root]);
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

    {{-- Overlay de "solte pra enviar" — só some do d-none durante um arrasto real (ver file-manager.js) --}}
    <div id="file-dropzone-overlay" class="file-dropzone-overlay d-none">
        <div class="file-dropzone-overlay-box">
            <i class="bi bi-cloud-arrow-up d-block mb-2" style="font-size: 2.5rem;"></i>
            {{ __('Solte aqui pra enviar') }}
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

    {{-- Modal: nova pasta --}}
    <x-modal name="new-folder-modal" maxWidth="sm">
        <form method="POST" action="{{ route('admin.hosting-accounts.files.directories.store', $account) }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Nova pasta') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="current_path" value="{{ $path }}">
                <input type="hidden" name="root" value="{{ $root }}">
                <x-input-label for="new-folder-name" value="{{ __('Nome da pasta') }}" class="small mb-1" />
                <input type="text" id="new-folder-name" name="name" class="form-control" required autofocus>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancelar') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('Criar') }}</button>
            </div>
        </form>
    </x-modal>

    {{-- Modal: novo arquivo --}}
    <x-modal name="new-file-modal" maxWidth="sm">
        <form method="POST" action="{{ route('admin.hosting-accounts.files.store', $account) }}">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">{{ __('Novo arquivo') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="current_path" value="{{ $path }}">
                <input type="hidden" name="root" value="{{ $root }}">
                <x-input-label for="new-file-name" value="{{ __('Nome do arquivo') }}" class="small mb-1" />
                <input type="text" id="new-file-name" name="name" class="form-control" placeholder="index.php" required autofocus>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancelar') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('Criar') }}</button>
            </div>
        </form>
    </x-modal>

    {{-- Modal: renomear --}}
    <x-modal name="rename-modal" maxWidth="sm">
        <form method="POST" action="{{ route('admin.hosting-accounts.files.rename', $account) }}">
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
        <form method="POST" action="{{ route('admin.hosting-accounts.files.compress', $account) }}">
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
                <input type="text" id="compress-output" name="output" class="form-control" placeholder="arquivos.zip" required autofocus>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancelar') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('Compactar') }}</button>
            </div>
        </form>
    </x-modal>
</x-admin-layout>

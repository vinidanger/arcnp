@php
    $entryPathOf = fn ($entry) => trim($path.'/'.$entry['name'], '/');
    $parentPath = null;
    if ($path !== '') {
        $computed = dirname($path);
        $parentPath = $computed === '.' ? '' : $computed;
    }
    $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico'];
@endphp

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

    <div class="file-toolbar-path d-flex align-items-center gap-2 mb-3 flex-wrap">
        @if ($outsideDomains->isNotEmpty())
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle d-flex align-items-center gap-1" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-globe2"></i> {{ $root ?? $account->primary_domain }}
                </button>
                <ul class="dropdown-menu">
                    <li>
                        <a class="dropdown-item {{ $root === null ? 'active' : '' }}" href="{{ route('client.hosting-accounts.files.index', $account) }}">
                            {{ $account->primary_domain }} <span class="text-secondary small">(public_html)</span>
                        </a>
                    </li>
                    @foreach ($outsideDomains as $outsideDomain)
                        <li>
                            <a class="dropdown-item {{ $root === $outsideDomain->domain ? 'active' : '' }}"
                               href="{{ route('client.hosting-accounts.files.index', [$account, 'root' => $outsideDomain->domain]) }}">
                                {{ $outsideDomain->domain }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="vr"></div>
        @endif

        @if ($parentPath !== null)
            <a href="{{ route('client.hosting-accounts.files.index', [$account, 'path' => $parentPath, 'root' => $root]) }}"
               class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1 flex-shrink-0">
                <i class="bi bi-arrow-left"></i> {{ __('Voltar') }}
            </a>
        @endif

        <nav aria-label="breadcrumb" class="flex-grow-1">
            <ol class="breadcrumb file-breadcrumb">
                <li class="breadcrumb-item {{ $path === '' ? 'active' : '' }}">
                    @if ($path === '')
                        <i class="bi bi-house-door-fill"></i>
                    @else
                        <a href="{{ route('client.hosting-accounts.files.index', [$account, 'root' => $root]) }}"><i class="bi bi-house-door"></i></a>
                    @endif
                </li>
                @php $accumulated = ''; @endphp
                @foreach (array_filter(explode('/', $path)) as $segment)
                    @php $accumulated = trim($accumulated.'/'.$segment, '/'); @endphp
                    <li class="breadcrumb-item {{ $accumulated === $path ? 'active' : '' }}">
                        @if ($accumulated === $path)
                            {{ $segment }}
                        @else
                            <a href="{{ route('client.hosting-accounts.files.index', [$account, 'path' => $accumulated, 'root' => $root]) }}">{{ $segment }}</a>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    </div>

    <div id="file-manager"
         data-upload-url="{{ route('client.hosting-accounts.files.upload', $account) }}"
         data-destroy-url="{{ route('client.hosting-accounts.files.destroy', $account) }}"
         data-compress-url="{{ route('client.hosting-accounts.files.compress', $account) }}"
         data-extract-url="{{ route('client.hosting-accounts.files.extract', $account) }}"
         data-current-path="{{ $path }}"
         data-root="{{ $root }}"
         data-csrf="{{ csrf_token() }}">

        <div class="mb-3">
            <div class="d-flex gap-2 flex-wrap">
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
                    <i class="bi bi-file-earmark-zip"></i> {{ __('Compactar') }}
                </button>

                <button type="button" id="btn-delete-selected" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1">
                    <i class="bi bi-trash"></i> {{ __('Excluir selecionados') }}
                </button>
            </div>

            <div id="upload-progress" class="mt-2 d-none">
                <div class="d-flex justify-content-between small mb-1">
                    <span id="upload-progress-label"></span>
                    <span id="upload-progress-stats" class="text-secondary"></span>
                </div>
                <div class="progress" style="height: 6px;">
                    <div id="upload-progress-bar" class="progress-bar" role="progressbar" style="width: 0%"></div>
                </div>
            </div>

            <div id="upload-status" class="small mt-2 d-none"></div>
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
                                $ext = strtolower(pathinfo($entry['name'], PATHINFO_EXTENSION));
                                $isImage = $entry['type'] === 'file' && in_array($ext, $imageExtensions, true);
                                $isZip = $entry['type'] === 'file' && $ext === 'zip';
                                $kind = match (true) {
                                    $entry['type'] === 'directory' => 'directory',
                                    $isImage => 'image',
                                    $isZip => 'other',
                                    default => 'text',
                                };
                                $icon = match ($kind) {
                                    'directory' => 'bi-folder-fill text-warning',
                                    'image' => 'bi-file-earmark-image',
                                    'other' => 'bi-file-earmark-zip',
                                    default => 'bi-file-earmark-text',
                                };
                                $navigateUrl = match ($kind) {
                                    'directory' => route('client.hosting-accounts.files.index', [$account, 'path' => $entryPath, 'root' => $root]),
                                    'text' => route('client.hosting-accounts.files.edit', [$account, 'path' => $entryPath, 'root' => $root]),
                                    default => null,
                                };
                                $downloadUrl = $entry['type'] === 'file'
                                    ? route('client.hosting-accounts.files.download', [$account, 'path' => $entryPath, 'root' => $root])
                                    : null;
                            @endphp
                            <tr class="file-row" data-path="{{ $entryPath }}" data-name="{{ $entry['name'] }}" data-type="{{ $entry['type'] }}"
                                data-kind="{{ $kind }}" data-zip="{{ $isZip ? '1' : '0' }}" data-href="{{ $navigateUrl }}" data-download-url="{{ $downloadUrl }}">
                                <td>
                                    <input type="checkbox" class="form-check-input file-select" value="{{ $entryPath }}">
                                </td>
                                <td>
                                    @if ($kind === 'other')
                                        <span class="text-body"><i class="bi {{ $icon }}"></i> {{ $entry['name'] }}</span>
                                    @elseif ($kind === 'image')
                                        <a href="#" class="file-preview-image"><i class="bi {{ $icon }}"></i> {{ $entry['name'] }}</a>
                                    @else
                                        <a href="{{ $navigateUrl }}"><i class="bi {{ $icon }}"></i> {{ $entry['name'] }}</a>
                                    @endif
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

    {{-- Overlay de operação em andamento (extrair/compactar) — sem progresso
         real (o servidor só responde quando termina), mas evita a tela
         parecer travada durante operações mais demoradas. Some sozinho
         quando a página recarrega (sucesso ou erro). --}}
    <div id="file-operation-overlay" class="file-dropzone-overlay d-none">
        <div class="file-dropzone-overlay-box">
            <div class="spinner-border mb-2" role="status"></div>
            <div id="file-operation-label">{{ __('Processando...') }}</div>
        </div>
    </div>

    {{-- Menu de contexto (botão direito) --}}
    <ul id="file-context-menu" class="dropdown-menu file-context-menu shadow">
        <li><a class="dropdown-item" href="#" data-ctx="open"><i class="bi bi-box-arrow-up-right me-2"></i>{{ __('Abrir') }}</a></li>
        <li><a class="dropdown-item" href="#" data-ctx="download"><i class="bi bi-download me-2"></i>{{ __('Baixar') }}</a></li>
        <li><a class="dropdown-item" href="#" data-ctx="extract"><i class="bi bi-file-earmark-zip me-2"></i>{{ __('Extrair aqui') }}</a></li>
        <li><a class="dropdown-item" href="#" data-ctx="compress"><i class="bi bi-file-earmark-zip-fill me-2"></i>{{ __('Compactar') }}</a></li>
        <li><a class="dropdown-item" href="#" data-ctx="rename"><i class="bi bi-pencil me-2"></i>{{ __('Renomear') }}</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger" href="#" data-ctx="delete"><i class="bi bi-trash me-2"></i>{{ __('Remover') }}</a></li>
    </ul>

    {{-- Modal: preview de imagem --}}
    <x-modal name="image-preview-modal" maxWidth="lg">
        <div class="modal-header">
            <h5 class="modal-title" id="image-preview-title"></h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body text-center">
            <img id="image-preview-img" src="" alt="" class="img-fluid" style="max-height: 70vh;">
        </div>
    </x-modal>

    {{-- Modal: nova pasta --}}
    <x-modal name="new-folder-modal" maxWidth="sm">
        <form method="POST" action="{{ route('client.hosting-accounts.files.directories.store', $account) }}">
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
        <form method="POST" action="{{ route('client.hosting-accounts.files.store', $account) }}">
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
        <form id="compress-form" method="POST" action="{{ route('client.hosting-accounts.files.compress', $account) }}">
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
</x-client-layout>

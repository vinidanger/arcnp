<x-admin-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Configurações') }}</h1>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card" style="max-width: 32rem;">
        <div class="card-body">
            <h2 class="h6">{{ __('Gerenciador de arquivos') }}</h2>

            <form method="POST" action="{{ route('admin.settings.update') }}" class="mt-3">
                @csrf
                @method('PUT')

                <div class="mb-2">
                    <x-input-label for="max_upload_mb" value="{{ __('Tamanho máximo de upload (MB)') }}" />
                    <x-text-input id="max_upload_mb" name="max_upload_mb" type="number" min="1" max="2048"
                                  :value="old('max_upload_mb', $maxUploadMb)" required />
                    <x-input-error :messages="$errors->get('max_upload_mb')" class="mt-2" />
                </div>

                <p class="small text-secondary">
                    {{ __('O PHP-FPM deste servidor (Painel) permite até') }} <strong>{{ $phpUploadLimitMb }} MB</strong>
                    ({{ __('upload_max_filesize/post_max_size') }}) — {{ __('um valor maior aqui não terá efeito sem ajustar isso também. O Agent de cada servidor de hospedagem também tem seu próprio limite (nginx + PHP-FPM, 300 MB por padrão no deploy), independente deste.') }}
                </p>

                <x-primary-button>{{ __('Salvar') }}</x-primary-button>
            </form>
        </div>
    </div>
</x-admin-layout>

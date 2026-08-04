<x-admin-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Configurações') }}</h1>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card mb-3" style="max-width: 32rem;">
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

                <div class="mb-2 mt-4">
                    <h2 class="h6">{{ __('Google reCAPTCHA v3 (login)') }}</h2>
                    <p class="small text-secondary">
                        {{ __('Protege a tela de login contra bots. Deixe desativado (ou as chaves em branco) pra não pedir captcha nenhum. Gere as chaves em') }}
                        <a href="https://www.google.com/recaptcha/admin" target="_blank" rel="noopener">google.com/recaptcha/admin</a>.
                    </p>
                </div>

                <div class="form-check mb-2">
                    <input type="checkbox" id="recaptcha_enabled" name="recaptcha_enabled" value="1" class="form-check-input" @checked(old('recaptcha_enabled', $recaptchaEnabled))>
                    <label for="recaptcha_enabled" class="form-check-label">{{ __('Ativar reCAPTCHA no login') }}</label>
                </div>

                <div class="mb-2">
                    <x-input-label for="recaptcha_site_key" value="{{ __('Site key') }}" />
                    <x-text-input id="recaptcha_site_key" name="recaptcha_site_key" type="text"
                                  :value="old('recaptcha_site_key', $recaptchaSiteKey)" />
                    <x-input-error :messages="$errors->get('recaptcha_site_key')" class="mt-2" />
                </div>

                <div class="mb-2">
                    <x-input-label for="recaptcha_secret_key" value="{{ __('Secret key') }}" />
                    <x-text-input id="recaptcha_secret_key" name="recaptcha_secret_key" type="text"
                                  :value="old('recaptcha_secret_key', $recaptchaSecretKey)" />
                    <x-input-error :messages="$errors->get('recaptcha_secret_key')" class="mt-2" />
                </div>

                <x-primary-button>{{ __('Salvar') }}</x-primary-button>
            </form>
        </div>
    </div>
</x-admin-layout>

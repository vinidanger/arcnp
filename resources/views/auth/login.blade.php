<x-guest-layout>
    <div class="mb-6">
        <p class="eyebrow mb-1">Acesso restrito</p>
        <h1 class="text-2xl text-text mb-1">Entrar no painel</h1>
        <p class="text-sm text-text-dim">Use as credenciais da sua conta para continuar.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" id="login-form" class="flex flex-col gap-4">
        @csrf

        @if ($recaptchaEnabled)
            <input type="hidden" name="recaptcha_token" id="recaptcha_token">
        @endif

        <div>
            <x-input-label for="login" :value="__('Usuário')" />
            <x-text-input id="login" type="text" name="login" :value="old('login')" required autofocus autocomplete="username" class="w-full" />
            <x-input-error :messages="$errors->get('login')" class="mt-1.5" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" class="w-full" />
            <x-input-error :messages="$errors->get('password')" class="mt-1.5" />
        </div>

        <label for="remember_me" class="flex items-center gap-2 text-sm text-text-dim select-none">
            <input id="remember_me" type="checkbox" name="remember"
                class="w-4 h-4 rounded-sm border border-line-strong bg-panel text-accent accent-[var(--color-accent)]">
            {{ __('Lembrar de mim') }}
        </label>

        <div class="flex items-center justify-end pt-1">
            <x-primary-button>
                {{ __('Entrar') }}
            </x-primary-button>
        </div>
    </form>

    @if ($recaptchaEnabled)
        <script src="https://www.google.com/recaptcha/api.js?render={{ $recaptchaSiteKey }}"></script>
        <script>
            document.getElementById('login-form').addEventListener('submit', function (event) {
                if (document.getElementById('recaptcha_token').value) {
                    return;
                }

                event.preventDefault();

                grecaptcha.ready(function () {
                    grecaptcha.execute('{{ $recaptchaSiteKey }}', { action: 'login' }).then(function (token) {
                        document.getElementById('recaptcha_token').value = token;
                        document.getElementById('login-form').submit();
                    });
                });
            });
        </script>
    @endif
</x-guest-layout>

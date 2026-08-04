<x-guest-layout>
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" id="login-form">
        @csrf

        @if ($recaptchaEnabled)
            <input type="hidden" name="recaptcha_token" id="recaptcha_token">
        @endif

        <div class="mb-3">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mb-3">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="form-check mb-3">
            <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
            <label for="remember_me" class="form-check-label">{{ __('Remember me') }}</label>
        </div>

        <div class="d-flex align-items-center justify-content-between">
            @if (Route::has('password.request'))
                <a class="small text-decoration-none" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @else
                <span></span>
            @endif

            <x-primary-button>
                {{ __('Log in') }}
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

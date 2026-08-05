<section>
    <header>
        <h2 class="h5">{{ __('Autenticação de dois fatores') }}</h2>
        <p class="small text-secondary">
            {{ __('Adiciona uma camada extra de segurança no login, pedindo um código do seu app autenticador (Google Authenticator, Authy, etc) além da senha.') }}
        </p>
    </header>

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if (session('two_factor_recovery_codes'))
        <div class="alert alert-warning">
            <strong>{{ __('Códigos de recuperação — anote agora, não aparecem de novo.') }}</strong>
            <p class="small mb-2">{{ __('Use um desses códigos pra entrar se perder acesso ao app autenticador. Cada um só funciona uma vez.') }}</p>
            <pre class="mb-0 bg-body-secondary p-2 rounded border small">{{ implode("\n", session('two_factor_recovery_codes')) }}</pre>
        </div>
    @endif

    @if ($user->hasTwoFactorEnabled())
        <p class="small text-success mb-3"><i class="bi bi-shield-check"></i> {{ __('Ativado.') }}</p>

        <form method="POST" action="{{ route('two-factor.recovery-codes') }}" class="mb-3">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('Gerar novos códigos de recuperação') }}</button>
        </form>

        <form method="POST" action="{{ route('two-factor.disable') }}" style="max-width: 24rem;">
            @csrf
            @method('DELETE')
            <x-input-label for="two_factor_disable_password" :value="__('Senha atual (pra confirmar)')" />
            <x-text-input id="two_factor_disable_password" name="password" type="password" autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
            <button type="submit" class="btn btn-sm btn-outline-danger mt-2">{{ __('Desativar') }}</button>
        </form>
    @elseif ($user->two_factor_secret)
        @php
            $otpauthUri = app(App\Services\TwoFactorAuthenticationService::class)->generateQrCodeUri($user->email, $user->two_factor_secret);
        @endphp
        <div class="alert alert-info">
            <p class="small mb-2">{{ __('Escaneie o QR code com seu app autenticador (Google Authenticator, Authy, etc):') }}</p>
            <canvas id="two-factor-qr-canvas" data-otpauth-uri="{{ $otpauthUri }}" class="mb-3 d-block"></canvas>

            <p class="small mb-2">{{ __('Não consegue escanear? Digite essa chave manualmente:') }}</p>
            <code class="d-block mb-0" style="letter-spacing: 0.1em;">{{ $user->two_factor_secret }}</code>
        </div>
        @vite('resources/js/two-factor-qr.js')

        <form method="POST" action="{{ route('two-factor.confirm') }}" style="max-width: 20rem;">
            @csrf
            <x-input-label for="two_factor_code" :value="__('Código do app')" />
            <x-text-input id="two_factor_code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" autofocus />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
            <x-primary-button class="mt-2">{{ __('Confirmar e ativar') }}</x-primary-button>
        </form>

        <form method="POST" action="{{ route('two-factor.enable') }}" class="mt-2">
            @csrf
            <button type="submit" class="btn btn-sm btn-link px-0">{{ __('Perdeu a chave? Gerar uma nova') }}</button>
        </form>
    @else
        <form method="POST" action="{{ route('two-factor.enable') }}">
            @csrf
            <x-primary-button>{{ __('Ativar') }}</x-primary-button>
        </form>
    @endif
</section>

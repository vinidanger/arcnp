<section>
    <header>
        <h2 class="h5">{{ __('Informações do perfil') }}</h2>
        <p class="small text-secondary">
            {{ __('Atualize as informações do seu perfil e endereço de e-mail.') }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-3">
        @csrf
        @method('patch')

        <div class="mb-3">
            <x-input-label for="name" :value="__('Nome')" />
            <x-text-input id="name" name="name" type="text" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        @if ($user->isAdmin())
            <div class="mb-3">
                <x-input-label for="username" :value="__('Usuário (login)')" />
                <x-text-input id="username" name="username" type="text" :value="old('username', $user->username)" required autocomplete="username" />
                <x-input-error class="mt-2" :messages="$errors->get('username')" />
                <p class="small text-secondary mt-1 mb-0">{{ __('É isso que você digita pra entrar no painel.') }}</p>
            </div>
        @endif

        <div class="mb-3">
            <x-input-label for="email" :value="__('E-mail') . ($user->isClient() ? ' (' . __('opcional, só contato') . ')' : '')" />
            <x-text-input id="email" name="email" type="email" :value="old('email', $user->email)" autocomplete="email" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="small mt-2">
                        {{ __('Seu endereço de e-mail não foi verificado.') }}

                        <button form="send-verification" class="btn btn-link btn-sm p-0 align-baseline">
                            {{ __('Clique aqui para reenviar o e-mail de verificação.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="small text-success mt-2">
                            {{ __('Um novo link de verificação foi enviado para o seu e-mail.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="d-flex align-items-center gap-3">
            <x-primary-button>{{ __('Salvar') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p class="small text-success mb-0">{{ __('Salvo.') }}</p>
            @endif
        </div>
    </form>
</section>

<section>
    <header>
        <h2 class="h5">{{ __('Alterar senha') }}</h2>
        @if (auth()->user()->isClient())
            <p class="small text-secondary">
                {{ __('Essa é a mesma senha usada pra entrar no painel e pro acesso SSH — altere aqui e ela vale para os dois.') }}
            </p>
        @else
            <p class="small text-secondary">
                {{ __('Garanta que sua conta esteja usando uma senha longa e aleatória para se manter segura.') }}
            </p>
        @endif
    </header>

    @if (auth()->user()->isClient() && ! auth()->user()->hostingAccount)
        <p class="small text-secondary mb-0">{{ __('Disponível assim que sua hospedagem for provisionada.') }}</p>
    @else
    <form method="post" action="{{ route('password.update') }}" class="mt-3">
        @csrf
        @method('put')

        <div class="mb-3">
            <x-input-label for="update_password_current_password" :value="__('Senha atual')" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div class="mb-3">
            <x-input-label for="update_password_password" :value="__('Nova senha')" />
            <x-text-input id="update_password_password" name="password" type="password" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div class="mb-3">
            <x-input-label for="update_password_password_confirmation" :value="__('Confirmar senha')" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="d-flex align-items-center gap-3">
            <x-primary-button>{{ __('Salvar') }}</x-primary-button>

            @if (session('status') === 'password-updated')
                <p class="small text-success mb-0">{{ __('Salvo.') }}</p>
            @endif
        </div>
    </form>
    @endif
</section>

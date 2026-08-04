<x-guest-layout>
    <p class="small text-secondary mb-3">
        {{ __('Digite o código do seu app autenticador, ou um código de recuperação se não tiver acesso a ele.') }}
    </p>

    <form method="POST" action="{{ route('two-factor.challenge') }}">
        @csrf

        <div class="mb-3">
            <x-input-label for="code" :value="__('Código')" />
            <x-text-input id="code" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" autofocus />
        </div>

        <div class="mb-3">
            <x-input-label for="recovery_code" :value="__('Ou código de recuperação')" />
            <x-text-input id="recovery_code" type="text" name="recovery_code" autocomplete="off" />
        </div>

        <x-input-error :messages="$errors->get('code')" class="mb-3" />

        <div class="d-flex align-items-center justify-content-between">
            <a class="small text-decoration-none" href="{{ route('login') }}">{{ __('Voltar') }}</a>

            <x-primary-button>
                {{ __('Confirmar') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>

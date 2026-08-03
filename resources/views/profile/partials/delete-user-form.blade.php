<section>
    <header>
        <h2 class="h5">{{ __('Excluir conta') }}</h2>
        <p class="small text-secondary">
            {{ __('Depois que sua conta for excluída, todos os seus recursos e dados serão apagados permanentemente. Antes de excluir sua conta, baixe qualquer dado ou informação que queira manter.') }}
        </p>
    </header>

    <x-danger-button data-bs-toggle="modal" data-bs-target="#confirm-user-deletion">
        {{ __('Excluir conta') }}
    </x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-3">
            @csrf
            @method('delete')

            <h2 class="h5">
                {{ __('Tem certeza que deseja excluir sua conta?') }}
            </h2>

            <p class="small text-secondary">
                {{ __('Depois que sua conta for excluída, todos os seus recursos e dados serão apagados permanentemente. Digite sua senha para confirmar que deseja excluir sua conta permanentemente.') }}
            </p>

            <div class="mt-3">
                <x-input-label for="password" value="{{ __('Senha') }}" class="visually-hidden" />
                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    placeholder="{{ __('Senha') }}"
                />
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-3 d-flex justify-content-end gap-2">
                <x-secondary-button data-bs-dismiss="modal">
                    {{ __('Cancelar') }}
                </x-secondary-button>

                <x-danger-button>
                    {{ __('Excluir conta') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>

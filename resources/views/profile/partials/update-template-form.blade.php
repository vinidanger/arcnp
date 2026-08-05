<section>
    <header>
        <h2 class="h5">{{ __('Aparência do painel') }}</h2>
        <p class="small text-secondary">
            {{ __('Escolha como as telas da sua hospedagem são exibidas.') }}
        </p>
    </header>

    @if ($user->ui_template_locked)
        <p class="small text-secondary mt-3 mb-0">
            <i class="bi bi-lock"></i>
            {{ __('Seu administrador definiu o template do painel para sua conta — você não pode trocar sozinho.') }}
        </p>
    @else
        <form method="post" action="{{ route('profile.template.update') }}" class="mt-3">
            @csrf
            @method('patch')

            <div class="d-flex flex-wrap gap-2">
                @foreach (['default' => 'Padrão', 'cpanel' => 'cPanel'] as $value => $label)
                    <label class="form-check-label" style="cursor: pointer;">
                        <input type="radio" class="form-check-input" name="ui_template" value="{{ $value }}"
                               @checked(old('ui_template', $user->resolvedUiTemplate()) === $value)
                               onchange="this.form.requestSubmit()">
                        {{ __($label) }}
                    </label>
                @endforeach
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('ui_template')" />

            @if (session('status') === 'template-updated')
                <p class="small text-success mt-2 mb-0">{{ __('Salvo.') }}</p>
            @endif
        </form>
    @endif
</section>

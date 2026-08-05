<x-admin-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Editar cliente') }}</h1>
    </x-slot>

    <div class="card" style="max-width: 32rem;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.clients.update', $client) }}">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <x-input-label for="name" value="{{ __('Nome') }}" />
                    <x-text-input id="name" name="name" type="text" :value="old('name', $client->name)" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div class="mb-3">
                    <x-input-label for="email" value="{{ __('E-mail (opcional, só contato)') }}" />
                    <x-text-input id="email" name="email" type="email" :value="old('email', $client->email)" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="mb-3">
                    <x-input-label for="status" value="{{ __('Status') }}" />
                    <select id="status" name="status" class="form-select" required>
                        <option value="active" @selected(old('status', $client->status) === 'active')>{{ __('Ativo') }}</option>
                        <option value="suspended" @selected(old('status', $client->status) === 'suspended')>{{ __('Suspenso') }}</option>
                    </select>
                    <x-input-error :messages="$errors->get('status')" class="mt-2" />
                </div>

                <div class="mb-3">
                    <x-input-label for="ui_template" value="{{ __('Template do painel do cliente') }}" />
                    <select id="ui_template" name="ui_template" class="form-select" required>
                        <option value="default" @selected(old('ui_template', $client->resolvedUiTemplate()) === 'default')>{{ __('Padrão') }}</option>
                        <option value="cpanel" @selected(old('ui_template', $client->resolvedUiTemplate()) === 'cpanel')>{{ __('cPanel') }}</option>
                    </select>
                    <x-input-error :messages="$errors->get('ui_template')" class="mt-2" />
                </div>

                <div class="mb-3">
                    <div class="form-check">
                        <input type="hidden" name="ui_template_locked" value="0">
                        <input type="checkbox" class="form-check-input" id="ui_template_locked" name="ui_template_locked" value="1" @checked(old('ui_template_locked', $client->ui_template_locked))>
                        <label class="form-check-label" for="ui_template_locked">{{ __('Travar (impede o cliente de trocar o template sozinho)') }}</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">{{ __('Salvar') }}</button>
                <a href="{{ route('admin.clients.index') }}" class="btn btn-outline-secondary">{{ __('Cancelar') }}</a>
            </form>
        </div>
    </div>
</x-admin-layout>

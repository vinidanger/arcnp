{{-- Modal: criar banco de dados --}}
<x-modal name="add-database-modal" maxWidth="sm" :show="$errors->has('name') || $errors->has('username') || $errors->has('password')">
    <form method="POST" action="{{ route('client.hosting-accounts.databases.store', $account) }}">
        @csrf
        <div class="modal-header">
            <h5 class="modal-title">{{ __('Criar banco de dados') }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="mb-3">
                <x-input-label for="db_name" value="{{ __('Nome do banco') }}" class="small mb-1" />
                <div class="input-group">
                    <span class="input-group-text">{{ $account->linux_username }}_</span>
                    <input id="db_name" name="name" type="text" class="form-control" placeholder="loja" value="{{ old('name') }}" required autofocus>
                </div>
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div class="mb-3">
                <x-input-label for="db_username" value="{{ __('Usuário (opcional)') }}" class="small mb-1" />
                <div class="input-group">
                    <span class="input-group-text">{{ $account->linux_username }}_</span>
                    <input id="db_username" name="username" type="text" class="form-control" placeholder="{{ __('igual ao nome do banco') }}" value="{{ old('username') }}">
                </div>
                <x-input-error :messages="$errors->get('username')" class="mt-2" />
            </div>
            <div class="mb-0">
                <x-input-label for="db_password" value="{{ __('Senha (opcional)') }}" class="small mb-1" />
                <input id="db_password" name="password" type="text" class="form-control" placeholder="{{ __('gerar automaticamente') }}">
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancelar') }}</button>
            <button type="submit" class="btn btn-primary">{{ __('Criar banco') }}</button>
        </div>
    </form>
</x-modal>

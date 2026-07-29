<x-admin-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Nova conta de hospedagem') }}</h1>
    </x-slot>

    @if ($clients->isEmpty() || $servers->isEmpty() || $plans->isEmpty())
        <div class="alert alert-warning">
            {{ __('Antes de criar uma conta, é preciso ter pelo menos: um cliente (usuário tipo client), um servidor pareado e um plano ativo.') }}
        </div>
    @endif

    <div class="card" style="max-width: 36rem;">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.hosting-accounts.store') }}">
                @csrf

                <div class="mb-3">
                    <x-input-label for="user_id" value="{{ __('Cliente') }}" />
                    <select id="user_id" name="user_id" class="form-select" required>
                        <option value="">{{ __('Selecione...') }}</option>
                        @foreach ($clients as $client)
                            <option value="{{ $client->id }}" @selected(old('user_id') == $client->id)>
                                {{ $client->name }} ({{ $client->email }})
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('user_id')" class="mt-2" />
                </div>

                <div class="mb-3">
                    <x-input-label for="server_id" value="{{ __('Servidor') }}" />
                    <select id="server_id" name="server_id" class="form-select" required>
                        <option value="">{{ __('Selecione...') }}</option>
                        @foreach ($servers as $server)
                            <option value="{{ $server->id }}" @selected(old('server_id') == $server->id)>
                                {{ $server->name }} ({{ $server->ip_address }}) — {{ $server->agent_status }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('server_id')" class="mt-2" />
                </div>

                <div class="mb-3">
                    <x-input-label for="plan_id" value="{{ __('Plano') }}" />
                    <select id="plan_id" name="plan_id" class="form-select" required>
                        <option value="">{{ __('Selecione...') }}</option>
                        @foreach ($plans as $plan)
                            <option value="{{ $plan->id }}" @selected(old('plan_id') == $plan->id)>
                                {{ $plan->name }} ({{ $plan->disk_quota_mb }} MB)
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('plan_id')" class="mt-2" />
                </div>

                <div class="mb-3">
                    <x-input-label for="primary_domain" value="{{ __('Domínio principal') }}" />
                    <x-text-input id="primary_domain" name="primary_domain" type="text" :value="old('primary_domain')" placeholder="sitedocliente.com" required />
                    <x-input-error :messages="$errors->get('primary_domain')" class="mt-2" />
                </div>

                <div class="mb-3">
                    <x-input-label for="php_version" value="{{ __('Versão do PHP') }}" />
                    <select id="php_version" name="php_version" class="form-select" required>
                        @foreach (config('hosting.php_versions') as $version)
                            <option value="{{ $version }}" @selected(old('php_version', config('hosting.default_php_version')) === $version)>
                                {{ $version }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('php_version')" class="mt-2" />
                </div>

                <div class="form-check mb-3">
                    <input type="hidden" name="create_database" value="0">
                    <input type="checkbox" class="form-check-input" id="create_database" name="create_database" value="1" @checked(old('create_database'))>
                    <label class="form-check-label" for="create_database">{{ __('Criar banco de dados MySQL junto') }}</label>
                </div>

                <button type="submit" class="btn btn-primary">{{ __('Criar e provisionar') }}</button>
            </form>
        </div>
    </div>
</x-admin-layout>

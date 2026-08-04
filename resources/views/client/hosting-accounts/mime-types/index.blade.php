<x-client-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('MIME Types') }} — {{ $account->primary_domain }}</h1>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6">{{ __('Nova regra') }}</h2>
            <form method="POST" action="{{ route('client.hosting-accounts.mime-types.store', $account) }}" class="row g-2 align-items-end">
                @csrf
                <div class="col-auto">
                    <x-input-label for="domain" value="{{ __('Domínio') }}" class="small mb-1" />
                    <select id="domain" name="domain" class="form-select form-select-sm" required>
                        @foreach ($domains as $domain)
                            <option value="{{ $domain }}">{{ $domain }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <x-input-label for="extension" value="{{ __('Extensão') }}" class="small mb-1" />
                    <input type="text" id="extension" name="extension" value="{{ old('extension') }}" class="form-control form-control-sm" placeholder="webp" required>
                </div>
                <div class="col-auto">
                    <x-input-label for="mime_type" value="{{ __('Tipo MIME') }}" class="small mb-1" />
                    <input type="text" id="mime_type" name="mime_type" value="{{ old('mime_type') }}" class="form-control form-control-sm" placeholder="image/webp" required>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">{{ __('Criar') }}</button>
                </div>
            </form>
            <x-input-error :messages="$errors->get('domain')" class="mt-2" />
            <x-input-error :messages="$errors->get('extension')" class="mt-2" />
            <x-input-error :messages="$errors->get('mime_type')" class="mt-2" />
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Domínio') }}</th>
                        <th>{{ __('Extensão') }}</th>
                        <th>{{ __('Tipo MIME') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($account->mimeTypeRules as $rule)
                        <tr>
                            <td>{{ $rule->domain }}</td>
                            <td><code>.{{ ltrim($rule->extension, '.') }}</code></td>
                            <td><code>{{ $rule->mime_type }}</code></td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('client.hosting-accounts.mime-types.destroy', [$account, $rule]) }}"
                                      class="d-inline-block" onsubmit="return confirm('{{ __('Remove essa regra de MIME type. Continuar?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Remover') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-secondary py-4">{{ __('Nenhuma regra de MIME type cadastrada.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-client-layout>

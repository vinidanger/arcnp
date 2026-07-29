<x-admin-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Zonas DNS') }} — {{ $account->primary_domain }}</h1>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if ($availableDomains !== [])
        <div class="card mb-3">
            <div class="card-body">
                <h2 class="h6">{{ __('Criar zona DNS') }}</h2>
                <p class="small text-secondary">
                    {{ __('Só funciona se o domínio já estiver apontando os nameservers (NS) pro nosso servidor no registrador.') }}
                </p>
                <form method="POST" action="{{ route('admin.hosting-accounts.dns.store', $account) }}" class="row g-2 align-items-end">
                    @csrf
                    <div class="col-auto">
                        <x-input-label for="domain" value="{{ __('Domínio') }}" class="small mb-1" />
                        <select id="domain" name="domain" class="form-select form-select-sm" required>
                            @foreach ($availableDomains as $domain)
                                <option value="{{ $domain }}" @selected(old('domain') === $domain)>{{ $domain }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <x-input-label for="admin_email" value="{{ __('E-mail do administrador') }}" class="small mb-1" />
                        <input type="email" id="admin_email" name="admin_email" value="{{ old('admin_email', $account->client->email) }}" class="form-control form-control-sm" required>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-sm btn-primary">{{ __('Criar zona') }}</button>
                    </div>
                </form>
                <x-input-error :messages="$errors->get('domain')" class="mt-2" />
                <x-input-error :messages="$errors->get('admin_email')" class="mt-2" />
            </div>
        </div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Domínio') }}</th>
                        <th>{{ __('E-mail admin') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($account->dnsZones as $zone)
                        <tr>
                            <td>{{ $zone->domain }}</td>
                            <td>{{ $zone->admin_email }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.hosting-accounts.dns.show', [$account, $zone]) }}" class="btn btn-sm btn-outline-secondary">{{ __('Gerenciar registros') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-secondary py-4">{{ __('Nenhuma zona DNS cadastrada.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>

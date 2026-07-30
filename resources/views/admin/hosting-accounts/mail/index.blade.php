<x-admin-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('E-mail') }} — {{ $account->primary_domain }}</h1>
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
                <h2 class="h6">{{ __('Ativar e-mail num domínio') }}</h2>
                <p class="small text-secondary">
                    {{ __('Cria a estrutura de caixas de e-mail e a assinatura DKIM pra esse domínio. Só funciona bem se os e-mails do domínio estiverem chegando de fato nesse servidor (registro MX apontando pra cá).') }}
                </p>
                <form method="POST" action="{{ route('admin.hosting-accounts.mail.store', $account) }}" class="row g-2 align-items-end">
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
                        <button type="submit" class="btn btn-sm btn-primary">{{ __('Ativar') }}</button>
                    </div>
                </form>
                <x-input-error :messages="$errors->get('domain')" class="mt-2" />
            </div>
        </div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Domínio') }}</th>
                        <th>{{ __('Caixas') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($account->mailDomains as $mailDomain)
                        <tr>
                            <td>{{ $mailDomain->domain }}</td>
                            <td>{{ $mailDomain->mailboxes->count() }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.hosting-accounts.mail.show', [$account, $mailDomain]) }}" class="btn btn-sm btn-outline-secondary">{{ __('Gerenciar') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-secondary py-4">{{ __('Nenhum domínio com e-mail ativo.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>

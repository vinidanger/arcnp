<x-client-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Bancos de Dados') }} — {{ $account->primary_domain }}</h1>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @if (session('plain_db_password'))
        <div class="alert alert-warning">
            <strong>{{ __('Credenciais do banco de dados — copie agora, não aparecem de novo.') }}</strong>
            <pre class="mb-0 mt-2 bg-body-secondary p-2 rounded border small">DB_DATABASE={{ session('plain_db_name') }}
DB_USERNAME={{ session('plain_db_username') }}
DB_PASSWORD={{ session('plain_db_password') }}</pre>
        </div>
    @endif

    @include('client.hosting-accounts.partials._databases-table')
    @include('client.hosting-accounts.partials._add-database-modal')
</x-client-layout>

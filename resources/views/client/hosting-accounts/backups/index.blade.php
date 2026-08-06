<x-client-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Backups') }} — {{ $account->primary_domain }}</h1>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @include('client.hosting-accounts.partials._backups-table')
</x-client-layout>

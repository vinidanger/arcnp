<x-admin-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0 text-break">{{ __('Editando') }} — {{ $path }}</h1>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <a href="{{ route('admin.hosting-accounts.files.index', [$account, 'path' => dirname($path) === '.' ? '' : dirname($path)]) }}" class="d-inline-block mb-2">
        {{ __('← Voltar pra pasta') }}
    </a>

    <form method="POST" action="{{ route('admin.hosting-accounts.files.update', $account) }}">
        @csrf
        <input type="hidden" name="path" value="{{ $path }}">
        <textarea name="content" class="form-control" rows="28" style="font-family: monospace; font-size: 0.875rem;" spellcheck="false">{{ $content }}</textarea>
        <x-input-error :messages="$errors->get('content')" class="mt-2" />
        <button type="submit" class="btn btn-primary mt-2">{{ __('Salvar') }}</button>
    </form>
</x-admin-layout>

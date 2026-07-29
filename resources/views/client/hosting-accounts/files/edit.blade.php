<x-client-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0 text-break">{{ __('Editando') }} — {{ $path }}</h1>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <a href="{{ route('client.hosting-accounts.files.index', [$account, 'path' => dirname($path) === '.' ? '' : dirname($path), 'root' => $root]) }}" class="d-inline-block mb-2">
        {{ __('← Voltar pra pasta') }}
    </a>

    <form method="POST" action="{{ route('client.hosting-accounts.files.update', $account) }}">
        @csrf
        <input type="hidden" name="path" value="{{ $path }}">
        <input type="hidden" name="root" value="{{ $root }}">
        <textarea name="content" class="form-control" data-code-editor data-filename="{{ basename($path) }}" spellcheck="false">{{ $content }}</textarea>
        <x-input-error :messages="$errors->get('content')" class="mt-2" />
        <button type="submit" class="btn btn-primary mt-2">{{ __('Salvar') }}</button>
    </form>

    @vite(['resources/js/code-editor.js'])
</x-client-layout>

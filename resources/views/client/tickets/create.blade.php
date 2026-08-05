<x-client-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Novo chamado') }}</h1>
    </x-slot>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('client.tickets.store') }}">
                @csrf
                <div class="mb-3">
                    <x-input-label for="subject" value="{{ __('Assunto') }}" />
                    <input type="text" id="subject" name="subject" value="{{ old('subject') }}" class="form-control" required>
                    <x-input-error :messages="$errors->get('subject')" class="mt-2" />
                </div>
                <div class="mb-3">
                    <x-input-label for="priority" value="{{ __('Prioridade') }}" />
                    <select id="priority" name="priority" class="form-select">
                        <option value="low" @selected(old('priority') === 'low')>{{ __('Baixa') }}</option>
                        <option value="normal" @selected(old('priority', 'normal') === 'normal')>{{ __('Normal') }}</option>
                        <option value="high" @selected(old('priority') === 'high')>{{ __('Alta') }}</option>
                    </select>
                    <x-input-error :messages="$errors->get('priority')" class="mt-2" />
                </div>
                <div class="mb-3">
                    <x-input-label for="body" value="{{ __('Mensagem') }}" />
                    <textarea id="body" name="body" rows="6" class="form-control" required>{{ old('body') }}</textarea>
                    <x-input-error :messages="$errors->get('body')" class="mt-2" />
                </div>
                <button type="submit" class="btn btn-primary">{{ __('Abrir chamado') }}</button>
            </form>
        </div>
    </div>
</x-client-layout>

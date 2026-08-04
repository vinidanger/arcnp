@php $announcement ??= null; @endphp

<div class="mb-3">
    <x-input-label for="title" value="{{ __('Título') }}" />
    <x-text-input id="title" name="title" type="text" :value="old('title', $announcement?->title)" required autofocus />
    <x-input-error :messages="$errors->get('title')" class="mt-2" />
</div>

<div class="mb-3">
    <x-input-label for="body" value="{{ __('Mensagem') }}" />
    <textarea id="body" name="body" class="form-control" rows="4" required>{{ old('body', $announcement?->body) }}</textarea>
    <x-input-error :messages="$errors->get('body')" class="mt-2" />
</div>

<div class="mb-3">
    <x-input-label for="audience" value="{{ __('Audiência') }}" />
    <select id="audience" name="audience" class="form-select">
        @foreach (['all' => __('Todos'), 'admin' => __('Só admins'), 'client' => __('Só clientes')] as $value => $label)
            <option value="{{ $value }}" @selected(old('audience', $announcement?->audience ?? 'all') === $value)>{{ $label }}</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('audience')" class="mt-2" />
</div>

<div class="row">
    <div class="col-6 mb-3">
        <x-input-label for="starts_at" value="{{ __('Início (opcional)') }}" />
        <input type="datetime-local" id="starts_at" name="starts_at" class="form-control"
               value="{{ old('starts_at', $announcement?->starts_at?->format('Y-m-d\TH:i')) }}">
        <x-input-error :messages="$errors->get('starts_at')" class="mt-2" />
    </div>
    <div class="col-6 mb-3">
        <x-input-label for="ends_at" value="{{ __('Fim (opcional)') }}" />
        <input type="datetime-local" id="ends_at" name="ends_at" class="form-control"
               value="{{ old('ends_at', $announcement?->ends_at?->format('Y-m-d\TH:i')) }}">
        <x-input-error :messages="$errors->get('ends_at')" class="mt-2" />
    </div>
</div>
<div class="form-text mb-3">{{ __('Sem datas, a mensagem fica visível assim que criada, indefinidamente.') }}</div>

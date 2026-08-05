@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium text-text-dim mb-1']) }}>
    {{ $value ?? $slot }}
</label>

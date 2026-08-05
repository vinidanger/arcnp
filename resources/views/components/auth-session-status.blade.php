@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded border border-success/30 bg-success-wash px-3 py-2 text-sm text-success']) }}>
        {{ $status }}
    </div>
@endif

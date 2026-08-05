@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'text-danger text-sm pl-4 mb-0 list-disc']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif

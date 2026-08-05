<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-1.5 rounded border border-transparent bg-accent text-accent-ink px-4 py-2 text-sm font-semibold hover:opacity-90']) }}>
    {{ $slot }}
</button>

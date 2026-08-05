<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-1.5 rounded border border-line-strong bg-transparent text-text px-4 py-2 text-sm font-semibold hover:border-accent hover:text-accent']) }}>
    {{ $slot }}
</button>

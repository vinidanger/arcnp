@props(['align' => 'end', 'contentClasses' => '', 'dropup' => false])

<div class="dropdown {{ $dropup ? 'dropup' : '' }}">
    <div class="dropdown-toggle" data-bs-toggle="dropdown" data-bs-strategy="fixed">
        {{ $trigger }}
    </div>

    <ul class="dropdown-menu {{ $align === 'end' ? 'dropdown-menu-end' : '' }} {{ $contentClasses }}">
        {{ $content }}
    </ul>
</div>

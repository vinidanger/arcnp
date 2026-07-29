@props(['align' => 'end', 'contentClasses' => ''])

<div class="dropdown">
    <div class="dropdown-toggle" style="cursor: pointer;" data-bs-toggle="dropdown" aria-expanded="false">
        {{ $trigger }}
    </div>

    <ul class="dropdown-menu dropdown-menu-{{ $align }} {{ $contentClasses }}">
        {{ $content }}
    </ul>
</div>

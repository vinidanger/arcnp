@props(['align' => 'end', 'contentClasses' => '', 'dropup' => false])

<div class="dropdown {{ $dropup ? 'dropup' : '' }}">
    <div class="dropdown-toggle" style="cursor: pointer;" data-bs-toggle="dropdown" aria-expanded="false">
        {{ $trigger }}
    </div>

    <ul class="dropdown-menu dropdown-menu-{{ $align }} {{ $contentClasses }}">
        {{ $content }}
    </ul>
</div>

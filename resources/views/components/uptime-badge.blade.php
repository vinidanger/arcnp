@props(['model'])

@php
    $badge = match ($model->uptime_status) {
        'up' => 'success',
        'down' => 'danger',
        default => 'secondary',
    };
@endphp

<span class="badge text-bg-{{ $badge }}">{{ status_label($model->uptime_status) }}</span>

@if ($model->uptime_status === 'down' && $model->uptime_down_since)
    <div class="small text-danger mt-1">
        {{ __('Desde') }} {{ $model->uptime_down_since->diffForHumans() }}
    </div>
@endif

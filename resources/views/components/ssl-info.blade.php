@props(['model'])

@php
    $badge = match ($model->ssl_status) {
        'active' => 'success',
        'pending' => 'info',
        'failed' => 'danger',
        default => 'secondary',
    };
@endphp

<span class="badge text-bg-{{ $badge }}">{{ status_label($model->ssl_status) }}</span>

@if ($model->ssl_status === 'failed' && $model->ssl_error)
    <div class="small text-danger mt-1">{{ $model->ssl_error }}</div>
@endif

@if ($model->ssl_status === 'active')
    <div class="small text-secondary mt-1">
        {{ __("Emissor: Let's Encrypt") }}
        @if ($model->ssl_issued_at)
            · {{ __('Emitido em') }} {{ $model->ssl_issued_at->format('d/m/Y') }}
        @endif
    </div>

    @if ($model->ssl_expires_at)
        @php
            $daysLeft = (int) floor(now()->diffInDays($model->ssl_expires_at, false));
            $expiryClass = $daysLeft <= 15 ? 'text-danger' : ($daysLeft <= 30 ? 'text-warning' : 'text-secondary');
        @endphp
        <div class="small {{ $expiryClass }} mt-1">
            {{ __('Expira em') }} {{ $model->ssl_expires_at->format('d/m/Y') }}
            ({{ __('faltam :days dias', ['days' => $daysLeft]) }})
        </div>
    @endif

    <div class="small text-success mt-1">
        <i class="bi bi-arrow-repeat"></i> {{ __('Renovação automática: ativada') }}
    </div>
@endif

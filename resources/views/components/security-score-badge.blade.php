@props(['account', 'detailed' => false])

@php
    $result = \App\Support\SecurityScore::calculate($account);
    $color = match ($result['grade']) {
        'A' => 'success',
        'B' => 'info',
        'C' => 'warning',
        default => 'danger',
    };
    $checkLabels = [
        'waf' => 'WAF ativo',
        'malware' => 'Sem malware ativo',
        'ssl' => 'SSL ativo',
        'cms_updated' => 'CMS atualizado',
        'two_factor' => '2FA ativado',
    ];
@endphp

<span class="badge text-bg-{{ $color }}" title="{{ __('Selo de segurança') }}: {{ $result['score'] }}/100">
    {{ __('Segurança') }}: {{ $result['grade'] }}
</span>

@if ($detailed)
    <ul class="list-unstyled small mt-2 mb-0">
        @foreach ($checkLabels as $key => $label)
            <li>
                @if ($result['checks'][$key])
                    <i class="bi bi-check-circle-fill text-success"></i>
                @else
                    <i class="bi bi-x-circle-fill text-danger"></i>
                @endif
                {{ __($label) }}
            </li>
        @endforeach
    </ul>
@endif

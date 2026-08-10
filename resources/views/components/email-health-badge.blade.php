@props(['mailDomain', 'server'])

@php
    $checks = [
        'spf' => ['label' => 'SPF publicado', 'value' => $mailDomain->spf_valid],
        'dkim' => ['label' => 'DKIM publicado', 'value' => $mailDomain->dkim_valid],
        'dmarc' => ['label' => 'DMARC publicado', 'value' => $mailDomain->dmarc_valid],
        'ptr' => ['label' => 'PTR do servidor bate com o hostname de e-mail', 'value' => $server->ptr_matches_mail_hostname],
        'blacklist' => ['label' => 'IP do servidor não está em blacklist', 'value' => $server->ip_blacklisted === null ? null : ! $server->ip_blacklisted],
    ];

    // Só o "health_checked_at" do PRÓPRIO domínio de e-mail conta como
    // "já verificado" — o do servidor é um dado independente (PTR/
    // blacklist são por servidor, não por domínio) e um servidor com
    // vários domínios de e-mail pode ter checado outro domínio sem
    // nunca ter checado este aqui.
    $checkedAt = $mailDomain->health_checked_at;
@endphp

@if (! $checkedAt)
    <span class="badge text-bg-secondary">{{ __('Ainda não verificado') }}</span>
@else
    @php
        $passCount = collect($checks)->filter(fn ($c) => $c['value'] === true)->count();
        $color = match (true) {
            $passCount === 5 => 'success',
            $passCount >= 3 => 'warning',
            default => 'danger',
        };
    @endphp
    <span class="badge text-bg-{{ $color }}">{{ __('Saúde de e-mail') }}: {{ $passCount }}/5</span>
    <span class="small text-secondary">{{ __('verificado') }} {{ $checkedAt->diffForHumans() }}</span>

    <ul class="list-unstyled small mt-2 mb-0">
        @foreach ($checks as $check)
            <li>
                @if ($check['value'] === true)
                    <i class="bi bi-check-circle-fill text-success"></i>
                @elseif ($check['value'] === false)
                    <i class="bi bi-x-circle-fill text-danger"></i>
                @else
                    <i class="bi bi-question-circle text-secondary"></i>
                @endif
                {{ __($check['label']) }}
            </li>
        @endforeach
    </ul>
@endif

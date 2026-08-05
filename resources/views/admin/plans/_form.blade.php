@php $plan ??= null; @endphp

<div class="mb-2 small text-uppercase text-secondary fw-semibold" style="letter-spacing: .04em;">{{ __('Identificação') }}</div>
<div class="row g-3 mb-4">
    <div class="col-md-8">
        <x-input-label for="name" value="{{ __('Nome') }}" />
        <x-text-input id="name" name="name" type="text" :value="old('name', $plan?->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>
    <div class="col-md-4 d-flex align-items-end">
        <div class="form-check form-switch mb-2">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1" role="switch"
                   @checked(old('is_active', $plan?->is_active ?? true))>
            <label class="form-check-label" for="is_active">{{ __('Plano ativo') }}</label>
        </div>
    </div>
</div>

<div class="mb-2 small text-uppercase text-secondary fw-semibold" style="letter-spacing: .04em;">{{ __('Cotas da conta') }}</div>
<p class="small text-secondary mb-3">{{ __('Limites de quantidade — aplicados na hora de criar recursos (banco, domínio, etc.), verificados pelo Painel.') }}</p>
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <x-input-label for="disk_quota_mb" value="{{ __('Disco (MB)') }}" />
        <x-text-input id="disk_quota_mb" name="disk_quota_mb" type="number" :value="old('disk_quota_mb', $plan?->disk_quota_mb)" required />
        <x-input-error :messages="$errors->get('disk_quota_mb')" class="mt-2" />
    </div>
    <div class="col-6 col-md-3">
        <x-input-label for="bandwidth_quota_mb" value="{{ __('Banda (MB)') }}" />
        <x-text-input id="bandwidth_quota_mb" name="bandwidth_quota_mb" type="number" :value="old('bandwidth_quota_mb', $plan?->bandwidth_quota_mb)" />
    </div>
    <div class="col-6 col-md-3">
        <x-input-label for="max_databases" value="{{ __('Máx. bancos') }}" />
        <x-text-input id="max_databases" name="max_databases" type="number" :value="old('max_databases', $plan?->max_databases ?? 0)" required />
    </div>
    <div class="col-6 col-md-3">
        <x-input-label for="max_addon_domains" value="{{ __('Máx. domínios adicionais') }}" />
        <x-text-input id="max_addon_domains" name="max_addon_domains" type="number" :value="old('max_addon_domains', $plan?->max_addon_domains ?? 0)" required />
        <x-input-error :messages="$errors->get('max_addon_domains')" class="mt-2" />
    </div>
</div>
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <x-input-label for="max_cron_jobs" value="{{ __('Máx. tarefas cron') }}" />
        <x-text-input id="max_cron_jobs" name="max_cron_jobs" type="number" :value="old('max_cron_jobs', $plan?->max_cron_jobs ?? 0)" required />
        <x-input-error :messages="$errors->get('max_cron_jobs')" class="mt-2" />
    </div>
    <div class="col-6 col-md-4">
        <x-input-label for="max_email_accounts" value="{{ __('Máx. contas de e-mail') }}" />
        <x-text-input id="max_email_accounts" name="max_email_accounts" type="number" :value="old('max_email_accounts', $plan?->max_email_accounts ?? 0)" required />
        <x-input-error :messages="$errors->get('max_email_accounts')" class="mt-2" />
    </div>
    <div class="col-6 col-md-4">
        <x-input-label for="max_backups" value="{{ __('Máx. backups') }}" />
        <x-text-input id="max_backups" name="max_backups" type="number" :value="old('max_backups', $plan?->max_backups ?? 5)" required />
        <x-input-error :messages="$errors->get('max_backups')" class="mt-2" />
    </div>
</div>

<div class="mb-2 small text-uppercase text-secondary fw-semibold" style="letter-spacing: .04em;">{{ __('Recursos do servidor') }}</div>
<p class="small text-secondary mb-3">{{ __('Aplicados no cgroup de cada conta desse plano (user-{uid}.slice) — vazio = sem limite. Ver tela "Recursos" de cada conta.') }}</p>
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <x-input-label for="cpu_cores" value="{{ __('Núcleos de CPU') }}" />
        <x-text-input id="cpu_cores" name="cpu_cores" type="number" :value="old('cpu_cores', $plan?->cpu_cores)" />
        <x-input-error :messages="$errors->get('cpu_cores')" class="mt-2" />
        <div class="form-text">{{ __('Aplicado como CPUQuota.') }}</div>
    </div>
    <div class="col-6 col-md-3">
        <x-input-label for="memory_limit_mb" value="{{ __('Limite de RAM (MB)') }}" />
        <x-text-input id="memory_limit_mb" name="memory_limit_mb" type="number" :value="old('memory_limit_mb', $plan?->memory_limit_mb)" />
        <x-input-error :messages="$errors->get('memory_limit_mb')" class="mt-2" />
        <div class="form-text">{{ __('Aplicado como MemoryMax.') }}</div>
    </div>
    <div class="col-6 col-md-3">
        <x-input-label for="max_processes" value="{{ __('Máx. processos simultâneos') }}" />
        <x-text-input id="max_processes" name="max_processes" type="number" :value="old('max_processes', $plan?->max_processes)" />
        <x-input-error :messages="$errors->get('max_processes')" class="mt-2" />
        <div class="form-text">{{ __('Aplicado como TasksMax (PHP, cron, SSH, apps).') }}</div>
    </div>
    <div class="col-6 col-md-3">
        <x-input-label for="io_weight" value="{{ __('Peso de I/O de disco') }}" />
        <x-text-input id="io_weight" name="io_weight" type="number" min="1" max="10000" :value="old('io_weight', $plan?->io_weight)" />
        <x-input-error :messages="$errors->get('io_weight')" class="mt-2" />
        <div class="form-text">{{ __('1 a 10000, padrão 100. Prioridade relativa, não é banda fixa.') }}</div>
    </div>
</div>

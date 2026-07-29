@php $plan ??= null; @endphp

<div class="mb-3">
    <x-input-label for="name" value="{{ __('Nome') }}" />
    <x-text-input id="name" name="name" type="text" :value="old('name', $plan?->name)" required autofocus />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div class="row">
    <div class="col-4 mb-3">
        <x-input-label for="disk_quota_mb" value="{{ __('Disco (MB)') }}" />
        <x-text-input id="disk_quota_mb" name="disk_quota_mb" type="number" :value="old('disk_quota_mb', $plan?->disk_quota_mb)" required />
        <x-input-error :messages="$errors->get('disk_quota_mb')" class="mt-2" />
    </div>
    <div class="col-4 mb-3">
        <x-input-label for="bandwidth_quota_mb" value="{{ __('Banda (MB)') }}" />
        <x-text-input id="bandwidth_quota_mb" name="bandwidth_quota_mb" type="number" :value="old('bandwidth_quota_mb', $plan?->bandwidth_quota_mb)" />
    </div>
    <div class="col-4 mb-3">
        <x-input-label for="max_databases" value="{{ __('Máx. bancos') }}" />
        <x-text-input id="max_databases" name="max_databases" type="number" :value="old('max_databases', $plan?->max_databases ?? 0)" required />
    </div>
</div>

<div class="row">
    <div class="col-4 mb-3">
        <x-input-label for="max_addon_domains" value="{{ __('Máx. domínios adicionais') }}" />
        <x-text-input id="max_addon_domains" name="max_addon_domains" type="number" :value="old('max_addon_domains', $plan?->max_addon_domains ?? 0)" required />
        <x-input-error :messages="$errors->get('max_addon_domains')" class="mt-2" />
    </div>
    <div class="col-4 mb-3">
        <x-input-label for="max_cron_jobs" value="{{ __('Máx. tarefas cron') }}" />
        <x-text-input id="max_cron_jobs" name="max_cron_jobs" type="number" :value="old('max_cron_jobs', $plan?->max_cron_jobs ?? 0)" required />
        <x-input-error :messages="$errors->get('max_cron_jobs')" class="mt-2" />
    </div>
    <div class="col-4 mb-3">
        <x-input-label for="max_email_accounts" value="{{ __('Máx. contas de e-mail') }}" />
        <x-text-input id="max_email_accounts" name="max_email_accounts" type="number" :value="old('max_email_accounts', $plan?->max_email_accounts ?? 0)" required />
        <x-input-error :messages="$errors->get('max_email_accounts')" class="mt-2" />
        <div class="form-text">{{ __('Ainda sem sistema de e-mail — guardado pra quando existir.') }}</div>
    </div>
</div>

<div class="row">
    <div class="col-4 mb-3">
        <x-input-label for="cpu_cores" value="{{ __('Núcleos de CPU') }}" />
        <x-text-input id="cpu_cores" name="cpu_cores" type="number" :value="old('cpu_cores', $plan?->cpu_cores)" />
        <x-input-error :messages="$errors->get('cpu_cores')" class="mt-2" />
        <div class="form-text">{{ __('Ainda sem aplicação real (cgroup) — guardado pra quando existir.') }}</div>
    </div>
    <div class="col-4 mb-3">
        <x-input-label for="max_processes" value="{{ __('Máx. processos simultâneos') }}" />
        <x-text-input id="max_processes" name="max_processes" type="number" :value="old('max_processes', $plan?->max_processes)" />
        <x-input-error :messages="$errors->get('max_processes')" class="mt-2" />
        <div class="form-text">{{ __('Ainda sem aplicação real (pm.max_children) — guardado pra quando existir.') }}</div>
    </div>
</div>

<div class="form-check mb-3">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
           @checked(old('is_active', $plan?->is_active ?? true))>
    <label class="form-check-label" for="is_active">{{ __('Ativo') }}</label>
</div>

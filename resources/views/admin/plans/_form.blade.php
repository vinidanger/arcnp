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

<div class="form-check mb-3">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" class="form-check-input" id="is_active" name="is_active" value="1"
           @checked(old('is_active', $plan?->is_active ?? true))>
    <label class="form-check-label" for="is_active">{{ __('Ativo') }}</label>
</div>

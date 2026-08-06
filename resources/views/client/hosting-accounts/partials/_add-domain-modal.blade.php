{{-- Modal: adicionar domínio --}}
<x-modal name="add-domain-modal" maxWidth="sm" :show="$errors->has('domain') || $errors->has('location')">
    <form method="POST" action="{{ route('client.hosting-accounts.domains.store', $account) }}">
        @csrf
        <div class="modal-header">
            <h5 class="modal-title">{{ __('Adicionar domínio') }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <div class="mb-3">
                <x-input-label for="domain" value="{{ __('Domínio') }}" class="small mb-1" />
                <x-text-input id="domain" name="domain" type="text" placeholder="blog.{{ $account->primary_domain }}" :value="old('domain')" required autofocus />
                <x-input-error :messages="$errors->get('domain')" class="mt-2" />
            </div>
            <div class="mb-3">
                <x-input-label for="domain-type" value="{{ __('Tipo') }}" class="small mb-1" />
                <select id="domain-type" name="type" class="form-select">
                    <option value="subdomain">{{ __('Subdomínio') }}</option>
                    <option value="addon">{{ __('Domínio adicional') }}</option>
                </select>
            </div>
            <div class="mb-0">
                <x-input-label for="domain-location" value="{{ __('Localização') }}" class="small mb-1" />
                <select id="domain-location" name="location" class="form-select">
                    <option value="inside_public_html">{{ __('Dentro de public_html') }}</option>
                    <option value="outside_public_html">{{ __('Fora de public_html (domains/)') }}</option>
                </select>
                <x-input-error :messages="$errors->get('location')" class="mt-2" />
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancelar') }}</button>
            <button type="submit" class="btn btn-primary">{{ __('Adicionar') }}</button>
        </div>
    </form>
</x-modal>

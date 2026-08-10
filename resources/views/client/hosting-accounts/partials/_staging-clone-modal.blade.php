{{-- Modal: clonar site pra staging --}}
<x-modal name="staging-clone-modal" maxWidth="sm" :show="$errors->has('subdomain_label')">
    <form method="POST" action="{{ route('client.hosting-accounts.domains.staging-clone.store', $account) }}">
        @csrf
        <div class="modal-header">
            <h5 class="modal-title">{{ __('Criar cópia de teste (staging)') }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <p class="small text-secondary">
                {{ __('Cria um subdomínio novo com uma cópia dos arquivos (e do banco de dados, se houver) do domínio principal. É um retrato do momento atual — não sincroniza depois, é só pra testar.') }}
            </p>
            <div class="mb-0">
                <x-input-label for="subdomain-label" value="{{ __('Prefixo do subdomínio') }}" class="small mb-1" />
                <div class="input-group">
                    <x-text-input id="subdomain-label" name="subdomain_label" type="text" value="staging" required autofocus />
                    <span class="input-group-text">.{{ $account->primary_domain }}</span>
                </div>
                <x-input-error :messages="$errors->get('subdomain_label')" class="mt-2" />
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('Cancelar') }}</button>
            <button type="submit" class="btn btn-primary">{{ __('Criar cópia') }}</button>
        </div>
    </form>
</x-modal>

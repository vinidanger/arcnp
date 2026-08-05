<x-admin-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">{{ __('Integrações de API') }}</h1>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.api-clients.docs') }}" class="btn btn-outline-secondary btn-sm">
                    {{ __('Documentação da API') }}
                </a>
                <a href="{{ route('admin.api-clients.create') }}" class="btn btn-primary btn-sm">
                    {{ __('Nova credencial') }}
                </a>
            </div>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (session('plain_token'))
        <div class="alert alert-warning">
            <strong>{{ __('Token — copie agora, não aparece de novo.') }}</strong>
            <pre class="mb-0 mt-2 bg-body-secondary p-2 rounded border small">{{ session('plain_token') }}</pre>
            <div class="form-text mt-2">{{ __('Envie como cabeçalho: Authorization: Bearer TOKEN') }}</div>
        </div>
    @endif

    <p class="text-secondary small">
        {{ __('Credenciais pra sistemas externos (ex: outro painel) criarem/gerenciarem contas de hospedagem via API. Ver documentação dos endpoints em /api/v1.') }}
    </p>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>{{ __('Nome') }}</th>
                        <th>{{ __('Último uso') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($apiClients as $apiClient)
                        <tr>
                            <td>{{ $apiClient->name }}</td>
                            <td>{{ $apiClient->last_used_at?->diffForHumans() ?? __('Nunca') }}</td>
                            <td class="text-end">
                                <form method="POST" action="{{ route('admin.api-clients.destroy', $apiClient) }}"
                                      onsubmit="return confirm('{{ __('Revoga essa credencial — o token para de funcionar imediatamente. Continuar?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">{{ __('Revogar') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-secondary py-4">
                                {{ __('Nenhuma credencial cadastrada ainda.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-admin-layout>

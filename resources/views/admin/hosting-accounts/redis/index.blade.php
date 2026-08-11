<x-admin-layout>
    <x-slot name="header">
        <h1 class="h4 mb-0">{{ __('Cache de objeto (Redis)') }} — {{ $account->primary_domain }}</h1>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    @php $redisHost = $account->server->public_ip_address ?: $account->server->ip_address; @endphp

    @if (session('plain_redis_password'))
        <div class="alert alert-warning">
            <strong>{{ __('Senha Redis — copie agora, não aparece de novo.') }}</strong>
            <pre class="mb-0 mt-2 bg-body-secondary p-2 rounded border small">{{ session('plain_redis_password') }}</pre>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <p class="small text-secondary">
                {{ __('Instância compartilhada do servidor, isolada por usuário — a conta só enxerga as próprias chaves. Use as credenciais abaixo no plugin de cache de objeto do site (ex. "Redis Object Cache" pro WordPress).') }}
            </p>

            @if ($account->redis_username)
                <dl class="row mb-3 small">
                    <dt class="col-2">{{ __('Host') }}</dt>
                    <dd class="col-10"><code>{{ $redisHost }}</code></dd>
                    <dt class="col-2">{{ __('Porta') }}</dt>
                    <dd class="col-10"><code>6379</code></dd>
                    <dt class="col-2">{{ __('Usuário') }}</dt>
                    <dd class="col-10"><code>{{ $account->redis_username }}</code></dd>
                </dl>
                <p class="small text-secondary">{{ __('A senha só aparece uma vez, na hora que é gerada — se perdeu, gere uma nova (a antiga para de funcionar).') }}</p>
            @else
                <p class="small text-secondary mb-3">{{ __('Nenhuma credencial gerada ainda.') }}</p>
            @endif

            <form method="POST" action="{{ route('admin.hosting-accounts.redis.regenerate', $account) }}"
                  @if ($account->redis_username) onsubmit="return confirm('{{ __('Isso invalida a senha atual — qualquer plugin já configurado com ela para de funcionar até ser atualizado. Continuar?') }}')" @endif>
                @csrf
                <button type="submit" class="btn btn-sm btn-primary">
                    {{ $account->redis_username ? __('Gerar nova senha') : __('Gerar credenciais') }}
                </button>
            </form>
        </div>
    </div>
</x-admin-layout>

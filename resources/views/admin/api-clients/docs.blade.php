<x-admin-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="h4 mb-0">{{ __('Documentação da API') }}</h1>
            <a href="{{ route('admin.api-clients.index') }}" class="btn btn-sm btn-outline-secondary">{{ __('Voltar') }}</a>
        </div>
    </x-slot>

    @php $base = url('/api/v1'); @endphp

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6">{{ __('Autenticação') }}</h2>
            <p class="small text-secondary mb-2">
                {{ __('Gere um token em "Integrações de API" → "Nova credencial" e envie em todo pedido:') }}
            </p>
            <pre class="bg-light p-2 rounded border small mb-0">Authorization: Bearer SEU_TOKEN
Accept: application/json</pre>
            <p class="small text-secondary mt-2 mb-0">
                {{ __('Todo token tem acesso completo a todas as contas (sem escopo por cliente nessa versão). Trate como um segredo de sistema, não de usuário final.') }}
            </p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6">{{ __('URL base e formato de erro') }}</h2>
            <p class="small mb-2"><code>{{ $base }}</code></p>
            <p class="small text-secondary mb-1">{{ __('Erro de validação (422):') }}</p>
            <pre class="bg-light p-2 rounded border small mb-2">{
  "message": "The client.email field is required.",
  "errors": { "client.email": ["The client.email field is required."] }
}</pre>
            <p class="small text-secondary mb-1">{{ __('Falha ao executar uma ação (422) ou recurso inexistente (404):') }}</p>
            <pre class="bg-light p-2 rounded border small mb-0">{ "message": "descrição do erro" }</pre>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6"><span class="badge text-bg-success me-2">GET</span><code>/plans</code></h2>
            <p class="small text-secondary">{{ __('Lista os planos ativos — use pra saber o que oferecer antes de criar uma conta.') }}</p>
            <pre class="bg-light p-2 rounded border small mb-0">curl {{ $base }}/plans \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Accept: application/json"</pre>
            <p class="small text-secondary mt-2 mb-1">{{ __('Resposta:') }}</p>
            <pre class="bg-light p-2 rounded border small mb-0">{
  "data": [
    {
      "id": 1,
      "name": "Básico",
      "disk_quota_mb": 5000,
      "bandwidth_quota_mb": null,
      "max_databases": 1,
      "max_addon_domains": 0,
      "max_cron_jobs": 0,
      "max_email_accounts": 0,
      "cpu_cores": null,
      "max_processes": null
    }
  ]
}</pre>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6"><span class="badge text-bg-primary me-2">POST</span><code>/hosting-accounts</code></h2>
            <p class="small text-secondary">
                {{ __('Cria a conta de hospedagem. Se "client.email" já existir como cliente, reaproveita — não cria duplicado. Se for cliente novo e "client.password" não for enviado, uma senha é gerada e devolvida UMA VEZ na resposta ("client_password") — se não for capturada aqui, não tem como recuperar depois (mesma lógica de qualquer senha gerada nesse sistema).') }}
            </p>
            <pre class="bg-light p-2 rounded border small mb-0">curl -X POST {{ $base }}/hosting-accounts \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "client": {
      "name": "Fulano de Tal",
      "email": "fulano@exemplo.com",
      "password": null
    },
    "server_id": 1,
    "plan_id": 1,
    "primary_domain": "sitedofulano.com.br",
    "php_version": "8.3"
  }'</pre>
            <p class="small text-secondary mt-2 mb-1">
                {{ __('Resposta 201 (provisionado) ou 422 (falhou provisionar — a conta fica registrada com status "error", cheque "status" na resposta):') }}
            </p>
            <pre class="bg-light p-2 rounded border small mb-0">{
  "data": {
    "id": 42,
    "primary_domain": "sitedofulano.com.br",
    "status": "active",
    "php_version": "8.3",
    "ssl_status": "active",
    "disk_usage_mb": null,
    "plan": { "id": 1, "name": "Básico" },
    "server": { "id": 1, "name": "srv1" },
    "client": { "id": 7, "name": "Fulano de Tal", "email": "fulano@exemplo.com" },
    "created_at": "2026-07-30T12:00:00.000000Z"
  },
  "client_password": "aB3xxxxxxxxxxxxx"
}</pre>
            <p class="small text-secondary mt-2 mb-0">
                {{ __('"client_password" só vem preenchido quando um cliente novo foi criado sem senha explícita. Guarde o "id" da conta — é o que identifica ela nos próximos 4 endpoints.') }}
            </p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6"><span class="badge text-bg-success me-2">GET</span><code>/hosting-accounts/{id}</code></h2>
            <p class="small text-secondary">{{ __('Status atual da conta — útil pra confirmar que terminou de provisionar (status "active") depois da criação.') }}</p>
            <pre class="bg-light p-2 rounded border small mb-0">curl {{ $base }}/hosting-accounts/42 \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Accept: application/json"</pre>
            <p class="small text-secondary mt-2 mb-0">{{ __('Mesmo formato de resposta do POST acima (sem "client_password").') }}</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6"><span class="badge text-bg-primary me-2">POST</span><code>/hosting-accounts/{id}/suspend</code></h2>
                    <p class="small text-secondary">{{ __('Suspende a conta (site/e-mail param de responder). Reversível.') }}</p>
                    <pre class="bg-light p-2 rounded border small mb-0">curl -X POST {{ $base }}/hosting-accounts/42/suspend \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Accept: application/json"</pre>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6"><span class="badge text-bg-primary me-2">POST</span><code>/hosting-accounts/{id}/reactivate</code></h2>
                    <p class="small text-secondary">{{ __('Reverte a suspensão.') }}</p>
                    <pre class="bg-light p-2 rounded border small mb-0">curl -X POST {{ $base }}/hosting-accounts/42/reactivate \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Accept: application/json"</pre>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3 border-danger">
        <div class="card-body">
            <h2 class="h6"><span class="badge text-bg-danger me-2">DELETE</span><code>/hosting-accounts/{id}</code></h2>
            <p class="small text-secondary">
                {{ __('Cancela e apaga a conta pra sempre — remove usuário Linux, site, bancos, e-mails, DNS gerenciado por esse Painel, tudo. Não reversível. O cliente (usuário de login) continua existindo, só a conta de hospedagem some.') }}
            </p>
            <pre class="bg-light p-2 rounded border small mb-0">curl -X DELETE {{ $base }}/hosting-accounts/42 \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Accept: application/json"</pre>
        </div>
    </div>
</x-admin-layout>

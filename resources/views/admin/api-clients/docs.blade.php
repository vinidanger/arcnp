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
            <pre class="bg-body-tertiary p-2 rounded border small mb-0">Authorization: Bearer SEU_TOKEN
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
            <pre class="bg-body-tertiary p-2 rounded border small mb-2">{
  "message": "The client.name field is required.",
  "errors": { "client.name": ["The client.name field is required."] }
}</pre>
            <p class="small text-secondary mb-1">{{ __('Falha ao executar uma ação (422) ou recurso inexistente (404):') }}</p>
            <pre class="bg-body-tertiary p-2 rounded border small mb-0">{ "message": "descrição do erro" }</pre>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6">{{ __('Paginação') }}</h2>
            <p class="small text-secondary mb-2">
                {{ __('Só "GET /hosting-accounts" é paginado (os demais são listas pequenas, escopadas a 1 conta). Aceita "page" e "per_page" (máximo 100, padrão 15) na query string. Resposta:') }}
            </p>
            <pre class="bg-body-tertiary p-2 rounded border small mb-0">{
  "data": [ ... ],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "last_page": 5, "per_page": 15, "total": 67 }
}</pre>
        </div>
    </div>

    <h2 class="h5 mt-4 mb-3">{{ __('Descoberta') }}</h2>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6"><span class="badge text-bg-success me-2">GET</span><code>/plans</code></h2>
            <p class="small text-secondary">{{ __('Lista os planos ativos — use pra saber o que oferecer antes de criar uma conta.') }}</p>
            <pre class="bg-body-tertiary p-2 rounded border small mb-0">curl {{ $base }}/plans \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Accept: application/json"</pre>
            <p class="small text-secondary mt-2 mb-1">{{ __('Resposta:') }}</p>
            <pre class="bg-body-tertiary p-2 rounded border small mb-0">{
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
            <h2 class="h6"><span class="badge text-bg-success me-2">GET</span><code>/servers</code></h2>
            <p class="small text-secondary">{{ __('Lista os servidores — use pra saber o "server_id" antes de criar uma conta.') }}</p>
            <pre class="bg-body-tertiary p-2 rounded border small mb-0">curl {{ $base }}/servers \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Accept: application/json"</pre>
            <p class="small text-secondary mt-2 mb-1">{{ __('Resposta:') }}</p>
            <pre class="bg-body-tertiary p-2 rounded border small mb-0">{
  "data": [
    {
      "id": 1,
      "name": "srv1",
      "hostname": "srv1.exemplo.com",
      "ip_address": "203.0.113.10",
      "public_ip_address": null,
      "ns_hosts": ["ns1.exemplo.com", "ns2.exemplo.com"],
      "os": "AlmaLinux 9",
      "cpu_cores": 4,
      "memory_mb": 8192,
      "disk_gb": 100,
      "agent_status": "online",
      "last_heartbeat_at": "2026-08-06T12:00:00.000000Z",
      "load_avg": 0.42,
      "disk_percent": 31,
      "mem_percent": 55
    }
  ]
}</pre>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6"><span class="badge text-bg-success me-2">GET</span><code>/hosting-accounts</code></h2>
            <p class="small text-secondary">
                {{ __('Lista PAGINADA de todas as contas — filtros opcionais na query string: "status", "server_id", "plan_id", "search" (procura em "primary_domain").') }}
            </p>
            <pre class="bg-body-tertiary p-2 rounded border small mb-0">curl "{{ $base }}/hosting-accounts?status=active&per_page=50" \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Accept: application/json"</pre>
            <p class="small text-secondary mt-2 mb-0">{{ __('Mesmo formato de item do "GET /hosting-accounts/{id}" abaixo, envolvido no formato de paginação já explicado.') }}</p>
        </div>
    </div>

    <h2 class="h5 mt-4 mb-3">{{ __('Provisionamento') }}</h2>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6"><span class="badge text-bg-primary me-2">POST</span><code>/hosting-accounts</code></h2>
            <p class="small text-secondary">
                {{ __('Cria um cliente novo e já provisiona a hospedagem numa chamada só — cada chamada é sempre uma conta nova (1 cliente = 1 hospedagem, sempre; "client.email" é só contato/referência, não identifica cliente, pode repetir entre chamadas ou nem ser enviado).') }}
            </p>
            <pre class="bg-body-tertiary p-2 rounded border small mb-0">curl -X POST {{ $base }}/hosting-accounts \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "client": {
      "name": "Fulano de Tal",
      "email": "fulano@exemplo.com"
    },
    "server_id": 1,
    "plan_id": 1,
    "primary_domain": "sitedofulano.com.br",
    "php_version": "8.3"
  }'</pre>
            <p class="small text-secondary mt-2 mb-1">
                {{ __('Resposta 201 (provisionado) ou 422 (falhou provisionar — a conta fica registrada com status "error", cheque "status" na resposta):') }}
            </p>
            <pre class="bg-body-tertiary p-2 rounded border small mb-0">{
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
  "client_username": "sitedofulano",
  "client_password": "aB3xxxxxxxxxxxxx"
}</pre>
            <p class="small text-secondary mt-2 mb-0">
                {{ __('"client_username" + "client_password" são as credenciais de login do cliente no painel (mesmas do SSH) — aparecem só nessa resposta, uma vez só, guarde agora. Não é mais por e-mail: o cliente loga com o "client_username" (usuário Linux gerado a partir do domínio) direto, estilo cPanel/DirectAdmin. Guarde também o "id" da conta — é o que identifica ela nos próximos 4 endpoints.') }}
            </p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6"><span class="badge text-bg-success me-2">GET</span><code>/hosting-accounts/{id}</code></h2>
            <p class="small text-secondary">{{ __('Status atual da conta — útil pra confirmar que terminou de provisionar (status "active") depois da criação.') }}</p>
            <pre class="bg-body-tertiary p-2 rounded border small mb-0">curl {{ $base }}/hosting-accounts/42 \
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
                    <pre class="bg-body-tertiary p-2 rounded border small mb-0">curl -X POST {{ $base }}/hosting-accounts/42/suspend \
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
                    <pre class="bg-body-tertiary p-2 rounded border small mb-0">curl -X POST {{ $base }}/hosting-accounts/42/reactivate \
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
            <pre class="bg-body-tertiary p-2 rounded border small mb-0">curl -X DELETE {{ $base }}/hosting-accounts/42 \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Accept: application/json"</pre>
        </div>
    </div>

    <p class="small text-secondary mt-4 mb-3">
        {{ __('Os endpoints abaixo são todos "GET", só leitura, escopados a uma conta (troque 42 pelo "id" da conta) — nenhum precisa de corpo de requisição. Campo de senha/segredo NUNCA aparece em nenhum deles (fica só disponível 1x na hora da criação, quando existe).') }}
    </p>

    <h2 class="h5 mt-4 mb-3">{{ __('Domínios e DNS') }}</h2>

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6"><span class="badge text-bg-success me-2">GET</span><code>/hosting-accounts/{id}/domains</code></h2>
                    <p class="small text-secondary">{{ __('Domínios adicionais e subdomínios da conta.') }}</p>
                    <pre class="bg-body-tertiary p-2 rounded border small mb-0">curl {{ $base }}/hosting-accounts/42/domains \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Accept: application/json"</pre>
                    <p class="small text-secondary mt-2 mb-1">{{ __('Resposta (cada item):') }}</p>
                    <pre class="bg-body-tertiary p-2 rounded border small mb-0">{
  "id": 5, "domain": "blog.sitedofulano.com.br", "type": "subdomain",
  "location": "inside_public_html", "subdirectory": "blog",
  "public_path": null, "php_version": "8.3", "status": "active",
  "last_error": null, "ssl_status": "active",
  "ssl_expires_at": "2026-10-01T00:00:00.000000Z", "waf_enabled": false
}</pre>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6"><span class="badge text-bg-success me-2">GET</span><code>/hosting-accounts/{id}/dns</code></h2>
                    <p class="small text-secondary">{{ __('Zona(s) DNS gerenciada(s) pelo Painel pra essa conta, com os registros aninhados.') }}</p>
                    <pre class="bg-body-tertiary p-2 rounded border small mb-0">curl {{ $base }}/hosting-accounts/42/dns \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Accept: application/json"</pre>
                    <p class="small text-secondary mt-2 mb-1">{{ __('Resposta (cada item):') }}</p>
                    <pre class="bg-body-tertiary p-2 rounded border small mb-0">{
  "id": 1, "domain": "sitedofulano.com.br", "admin_email": "postmaster@sitedofulano.com.br",
  "records": [
    { "id": 10, "type": "A", "name": "@", "content": "203.0.113.10", "ttl": 3600, "priority": null }
  ]
}</pre>
                </div>
            </div>
        </div>
    </div>

    <h2 class="h5 mt-4 mb-3">{{ __('Banco de dados e Backups') }}</h2>

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6"><span class="badge text-bg-success me-2">GET</span><code>/hosting-accounts/{id}/databases</code></h2>
                    <p class="small text-secondary">{{ __('Bancos MySQL da conta — sem senha (nunca exposta pela API).') }}</p>
                    <pre class="bg-body-tertiary p-2 rounded border small mb-0">curl {{ $base }}/hosting-accounts/42/databases \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Accept: application/json"</pre>
                    <p class="small text-secondary mt-2 mb-1">{{ __('Resposta (cada item):') }}</p>
                    <pre class="bg-body-tertiary p-2 rounded border small mb-0">{
  "id": 3, "db_name": "sitedofulano_wp", "db_username": "sitedofulano_wp",
  "created_at": "2026-07-30T12:05:00.000000Z"
}</pre>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6"><span class="badge text-bg-success me-2">GET</span><code>/hosting-accounts/{id}/backups</code></h2>
                    <p class="small text-secondary">{{ __('Histórico de backups da conta.') }}</p>
                    <pre class="bg-body-tertiary p-2 rounded border small mb-0">curl {{ $base }}/hosting-accounts/42/backups \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Accept: application/json"</pre>
                    <p class="small text-secondary mt-2 mb-1">{{ __('Resposta (cada item):') }}</p>
                    <pre class="bg-body-tertiary p-2 rounded border small mb-0">{
  "id": 8, "status": "completed", "files": ["backup-20260806.tar.gz"],
  "error": null, "created_at": "2026-08-06T03:00:00.000000Z"
}</pre>
                </div>
            </div>
        </div>
    </div>

    <h2 class="h5 mt-4 mb-3">{{ __('E-mail') }}</h2>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6"><span class="badge text-bg-success me-2">GET</span><code>/hosting-accounts/{id}/mail</code></h2>
            <p class="small text-secondary">{{ __('Domínios de e-mail da conta, com caixas (+ autorresposta/filtros) e encaminhamentos aninhados. Senha de caixa NUNCA aparece aqui.') }}</p>
            <pre class="bg-body-tertiary p-2 rounded border small mb-0">curl {{ $base }}/hosting-accounts/42/mail \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Accept: application/json"</pre>
            <p class="small text-secondary mt-2 mb-1">{{ __('Resposta (cada item):') }}</p>
            <pre class="bg-body-tertiary p-2 rounded border small mb-0">{
  "id": 1, "domain": "sitedofulano.com.br", "dkim_selector": "mail",
  "spf_record_value": "v=spf1 mx a ~all",
  "dmarc_record_value": "v=DMARC1; p=none; rua=mailto:postmaster@sitedofulano.com.br",
  "mailboxes": [
    {
      "id": 4, "local_part": "contato", "email": "contato@sitedofulano.com.br",
      "vacation": { "enabled": false, "subject": null, "message": null },
      "filters": []
    }
  ],
  "forwarders": [
    { "id": 2, "source": "vendas@sitedofulano.com.br", "destination": "contato@gmail.com" }
  ]
}</pre>
        </div>
    </div>

    <h2 class="h5 mt-4 mb-3">{{ __('Segurança e proteções') }}</h2>

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6"><span class="badge text-bg-success me-2">GET</span><code>/hosting-accounts/{id}/malware-scans</code></h2>
                    <p class="small text-secondary">{{ __('Histórico de varreduras de malware. "infected_files" traz cada arquivo com "quarantined_at"/"ignored" quando aplicável.') }}</p>
                    <pre class="bg-body-tertiary p-2 rounded border small mb-0">curl {{ $base }}/hosting-accounts/42/malware-scans \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Accept: application/json"</pre>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6"><span class="badge text-bg-success me-2">GET</span><code>/hosting-accounts/{id}/folder-protections</code></h2>
                    <p class="small text-secondary">{{ __('Pastas protegidas por senha (.htpasswd) — sem hash de senha.') }}</p>
                    <pre class="bg-body-tertiary p-2 rounded border small mb-0">curl {{ $base }}/hosting-accounts/42/folder-protections \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Accept: application/json"</pre>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6"><span class="badge text-bg-success me-2">GET</span><code>/hosting-accounts/{id}/site-redirects</code></h2>
                    <p class="small text-secondary">{{ __('Regras de redirecionamento configuradas nos domínios da conta.') }}</p>
                    <pre class="bg-body-tertiary p-2 rounded border small mb-0">curl {{ $base }}/hosting-accounts/42/site-redirects \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Accept: application/json"</pre>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6"><span class="badge text-bg-success me-2">GET</span><code>/hosting-accounts/{id}/hotlink-protection</code></h2>
                    <p class="small text-secondary">{{ __('Regras de proteção contra hotlink por domínio.') }}</p>
                    <pre class="bg-body-tertiary p-2 rounded border small mb-0">curl {{ $base }}/hosting-accounts/42/hotlink-protection \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Accept: application/json"</pre>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6"><span class="badge text-bg-success me-2">GET</span><code>/hosting-accounts/{id}/mime-type-rules</code></h2>
                    <p class="small text-secondary">{{ __('Tipos MIME customizados por domínio.') }}</p>
                    <pre class="bg-body-tertiary p-2 rounded border small mb-0">curl {{ $base }}/hosting-accounts/42/mime-type-rules \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Accept: application/json"</pre>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6"><span class="badge text-bg-success me-2">GET</span><code>/hosting-accounts/{id}/ssh-keys</code></h2>
                    <p class="small text-secondary">{{ __('Chaves públicas SSH autorizadas na conta.') }}</p>
                    <pre class="bg-body-tertiary p-2 rounded border small mb-0">curl {{ $base }}/hosting-accounts/42/ssh-keys \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Accept: application/json"</pre>
                </div>
            </div>
        </div>
    </div>

    <h2 class="h5 mt-4 mb-3">{{ __('Apps e chamados') }}</h2>

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6"><span class="badge text-bg-success me-2">GET</span><code>/hosting-accounts/{id}/apps</code></h2>
                    <p class="small text-secondary">{{ __('Instalações de app (WordPress/zip genérico) e apps Node/Python, numa resposta só.') }}</p>
                    <pre class="bg-body-tertiary p-2 rounded border small mb-0">curl {{ $base }}/hosting-accounts/42/apps \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Accept: application/json"</pre>
                    <p class="small text-secondary mt-2 mb-1">{{ __('Resposta:') }}</p>
                    <pre class="bg-body-tertiary p-2 rounded border small mb-0">{
  "data": {
    "app_installations": [
      {
        "id": 1, "domain": "sitedofulano.com.br", "path": null, "catalog_slug": "wordpress",
        "status": "active", "detected_version": "6.6.1", "latest_known_version": "6.6.1",
        "is_outdated": false, "site_url": "https://sitedofulano.com.br",
        "database": { "id": 3, "db_name": "sitedofulano_wp" }
      }
    ],
    "hosted_apps": []
  }
}</pre>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6"><span class="badge text-bg-success me-2">GET</span><code>/hosting-accounts/{id}/tickets</code></h2>
                    <p class="small text-secondary">{{ __('Chamados de suporte da conta, com as mensagens aninhadas.') }}</p>
                    <pre class="bg-body-tertiary p-2 rounded border small mb-0">curl {{ $base }}/hosting-accounts/42/tickets \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Accept: application/json"</pre>
                </div>
            </div>
        </div>
    </div>

    <h2 class="h5 mt-4 mb-3">{{ __('Outros') }}</h2>

    <div class="row">
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6"><span class="badge text-bg-success me-2">GET</span><code>/hosting-accounts/{id}/cron-jobs</code></h2>
                    <p class="small text-secondary">{{ __('Tarefas agendadas (cron) da conta.') }}</p>
                    <pre class="bg-body-tertiary p-2 rounded border small mb-0">curl {{ $base }}/hosting-accounts/42/cron-jobs \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Accept: application/json"</pre>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6"><span class="badge text-bg-success me-2">GET</span><code>/hosting-accounts/{id}/ftp-accounts</code></h2>
                    <p class="small text-secondary">{{ __('Contas FTP virtuais da conta — sem senha (hash irreversível, nunca exposto).') }}</p>
                    <pre class="bg-body-tertiary p-2 rounded border small mb-0">curl {{ $base }}/hosting-accounts/42/ftp-accounts \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Accept: application/json"</pre>
                </div>
            </div>
        </div>
    </div>

    <h2 class="h5 mt-4 mb-3">{{ __('Recursos ao vivo') }}</h2>

    <div class="card mb-3">
        <div class="card-body">
            <h2 class="h6"><span class="badge text-bg-success me-2">GET</span><code>/hosting-accounts/{id}/resources</code></h2>
            <p class="small text-secondary">
                {{ __('Único endpoint que não lê banco — consulta o Agent do servidor em tempo real (CPU/RAM/processos, mesmo limite configurado no plano). Pode demorar alguns segundos; devolve 422 se a conta não estiver ativa ou o Agent não responder.') }}
            </p>
            <pre class="bg-body-tertiary p-2 rounded border small mb-0">curl {{ $base }}/hosting-accounts/42/resources \
  -H "Authorization: Bearer SEU_TOKEN" \
  -H "Accept: application/json"</pre>
            <p class="small text-secondary mt-2 mb-0">{{ __('Formato de "data" é o que o Agent devolve (uso de CPU/memória/processos) — pode variar por versão do Agent.') }}</p>
        </div>
    </div>
</x-admin-layout>

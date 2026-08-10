@php
    $base = url('/api/v1');
    $publicDocsUrl = route('api-docs');
@endphp

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

<div class="card mb-4 border-primary">
    <div class="card-body">
        <h2 class="h6">{{ __('Prompt pronto pra integrar com IA') }}</h2>
        <p class="small text-secondary mb-1">
            {{ __('Copie o texto abaixo e cole na IA que você usa pra desenvolver (Claude, ChatGPT, Cursor etc.) — ela recebe o contexto da API pronto pra implementar a integração no seu sistema. Desmarque o que não quiser incluir; por padrão vai tudo.') }}
        </p>
        <p class="small text-secondary mb-3">
            {{ __('Esta página é pública — o link abaixo pode ser compartilhado sem exigir login:') }}
            <a href="{{ $publicDocsUrl }}"><code>{{ $publicDocsUrl }}</code></a>
        </p>

        <div class="d-flex gap-2 mb-2">
            <button type="button" id="ai-select-all" class="btn btn-sm btn-outline-secondary">{{ __('Selecionar todos') }}</button>
            <button type="button" id="ai-select-none" class="btn btn-sm btn-outline-secondary">{{ __('Selecionar nenhum') }}</button>
        </div>

        @php
            // groupBy() reindexa DENTRO de cada grupo (não preserva o
            // índice original) — sem isso, checkboxes de grupos
            // diferentes acabariam com o mesmo "value" e o JS
            // (endpoints[value]) pegaria o item errado. Marca o
            // índice real no array plano ANTES de agrupar.
            $indexedEndpoints = collect($endpoints)->map(fn ($endpoint, $i) => $endpoint + ['index' => $i]);
        @endphp
        <div class="row mb-3">
            @foreach ($indexedEndpoints->groupBy('group') as $groupName => $groupEndpoints)
                <div class="col-md-6 mb-2">
                    <div class="fw-semibold small mb-1">{{ $groupName }}</div>
                    @foreach ($groupEndpoints as $endpoint)
                        @php
                            $methodBadge = match ($endpoint['method']) {
                                'GET' => 'success',
                                'DELETE' => 'danger',
                                default => 'primary',
                            };
                        @endphp
                        <div class="form-check">
                            <input class="form-check-input ai-endpoint-checkbox" type="checkbox" value="{{ $endpoint['index'] }}" id="ai-ep-{{ $endpoint['index'] }}" checked>
                            <label class="form-check-label small" for="ai-ep-{{ $endpoint['index'] }}">
                                <span class="badge text-bg-{{ $methodBadge }} me-1" style="font-size: .65rem;">{{ $endpoint['method'] }}</span>
                                <code>{{ $endpoint['path'] }}</code>
                            </label>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>

        <div class="d-flex justify-content-between align-items-center mb-1">
            <label for="ai-prompt-textarea" class="small text-secondary mb-0">{{ __('Prompt gerado') }}</label>
            <button type="button" id="ai-prompt-copy" class="btn btn-sm btn-primary">{{ __('Copiar prompt') }}</button>
        </div>
        <textarea id="ai-prompt-textarea" class="form-control font-monospace small" rows="12" readonly></textarea>
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

<script>
    (function () {
        const endpoints = @json(collect($endpoints)->values());
        const baseUrl = @json($base);
        const docsUrl = @json($publicDocsUrl);
        const checkboxes = document.querySelectorAll('.ai-endpoint-checkbox');
        const textarea = document.getElementById('ai-prompt-textarea');
        const copyBtn = document.getElementById('ai-prompt-copy');

        function buildPrompt() {
            const selected = Array.from(checkboxes)
                .filter((cb) => cb.checked)
                .map((cb) => endpoints[parseInt(cb.value, 10)]);

            if (selected.length === 0) {
                textarea.value = '{{ __('Selecione ao menos um endpoint acima.') }}';
                return;
            }

            const lines = [];
            lines.push('Quero integrar minha aplicação com a API do Arcn Painel (hospedagem), v1.');
            lines.push('');
            lines.push('URL base: ' + baseUrl);
            lines.push('Documentação completa (acesse antes de implementar, sem precisar de login — tem exemplo real de corpo de requisição e de resposta de cada endpoint): ' + docsUrl);
            lines.push('Autenticação: header "Authorization: Bearer SEU_TOKEN" (gerado em Admin > Integrações de API) + "Accept: application/json" em toda requisição.');
            lines.push('Limite de requisições: 60 por minuto por token — trate HTTP 429 (esperar e repetir).');
            lines.push('Erros: HTTP 422 (validação ou falha ao executar) e 404 (recurso inexistente) trazem { "message": "..." }; 422 de validação também traz "errors" por campo.');
            lines.push('Paginação: só "GET /hosting-accounts" é paginada (query "page"/"per_page", máximo 100) — resposta com "data"/"links"/"meta".');
            lines.push('Nunca exponha nem guarde o token em código-cliente/frontend público — mantenha só no backend.');
            lines.push('');
            lines.push('IMPORTANTE: antes de escrever qualquer código, acesse a URL da documentação acima e leia com atenção o corpo exato de cada requisição (POST) e o formato exato de cada resposta (nomes de campo, aninhamento, valores que podem vir nulos) de todo endpoint que for usar. Não invente nem assuma nome de campo — use exatamente o que estiver documentado, e trate campos ausentes/nulos com segurança.');
            lines.push('');
            lines.push('Implemente a integração cobrindo estes ' + selected.length + ' endpoint(s):');
            lines.push('');

            let currentGroup = null;
            selected.forEach((ep) => {
                if (ep.group !== currentGroup) {
                    currentGroup = ep.group;
                    lines.push('## ' + currentGroup);
                }
                lines.push('- ' + ep.method + ' ' + baseUrl + ep.path + ' — ' + ep.summary);
            });

            textarea.value = lines.join('\n');
        }

        checkboxes.forEach((cb) => cb.addEventListener('change', buildPrompt));

        document.getElementById('ai-select-all').addEventListener('click', () => {
            checkboxes.forEach((cb) => { cb.checked = true; });
            buildPrompt();
        });

        document.getElementById('ai-select-none').addEventListener('click', () => {
            checkboxes.forEach((cb) => { cb.checked = false; });
            buildPrompt();
        });

        copyBtn.addEventListener('click', () => {
            navigator.clipboard.writeText(textarea.value).then(() => {
                const original = copyBtn.textContent;
                copyBtn.textContent = '{{ __('Copiado!') }}';
                setTimeout(() => { copyBtn.textContent = original; }, 1500);
            });
        });

        buildPrompt();
    })();
</script>

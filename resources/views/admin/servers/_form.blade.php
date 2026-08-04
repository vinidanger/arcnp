@php $server ??= null; @endphp

<div class="mb-3">
    <x-input-label for="name" value="{{ __('Nome') }}" />
    <x-text-input id="name" name="name" type="text" :value="old('name', $server?->name)" required autofocus />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div class="mb-3">
    <x-input-label for="ip_address" value="{{ __('IP') }}" />
    <x-text-input id="ip_address" name="ip_address" type="text" :value="old('ip_address', $server?->ip_address)" required />
    <x-input-error :messages="$errors->get('ip_address')" class="mt-2" />
</div>

<div class="mb-3">
    <x-input-label for="public_ip_address" value="{{ __('IP público (opcional)') }}" />
    <x-text-input id="public_ip_address" name="public_ip_address" type="text" :value="old('public_ip_address', $server?->public_ip_address)" />
    <div class="form-text">{{ __('Usado só pra links que o navegador do admin/cliente precisa acessar direto (ex: phpMyAdmin) — se o Painel e o Agent estiverem na mesma máquina e o "IP" acima for 127.0.0.1/interno, preencha aqui o IP público real. Se vazio, usa o campo "IP" acima.') }}</div>
    <x-input-error :messages="$errors->get('public_ip_address')" class="mt-2" />
</div>

<div class="mb-3">
    <x-input-label for="hostname" value="{{ __('Hostname') }}" />
    <x-text-input id="hostname" name="hostname" type="text" :value="old('hostname', $server?->hostname)" />
    <x-input-error :messages="$errors->get('hostname')" class="mt-2" />
</div>

<div class="row">
    <div class="col-6 mb-3">
        <x-input-label for="dns_ns1" value="{{ __('Nameserver 1 (DNS)') }}" />
        <x-text-input id="dns_ns1" name="dns_ns1" type="text" :value="old('dns_ns1', $server?->dns_ns1)" placeholder="ns1.seudominio.com" />
        <x-input-error :messages="$errors->get('dns_ns1')" class="mt-2" />
    </div>
    <div class="col-6 mb-3">
        <x-input-label for="dns_ns2" value="{{ __('Nameserver 2 (DNS)') }}" />
        <x-text-input id="dns_ns2" name="dns_ns2" type="text" :value="old('dns_ns2', $server?->dns_ns2)" placeholder="ns2.seudominio.com" />
        <x-input-error :messages="$errors->get('dns_ns2')" class="mt-2" />
    </div>
    <div class="form-text mt-n2 mb-3">{{ __('Preencha só se esse servidor for autoritativo pra zonas DNS (BIND) — precisa de pelo menos um nameserver configurado pra criar zonas.') }}</div>
</div>

<div class="mb-3">
    <x-input-label for="mail_hostname" value="{{ __('Hostname de e-mail (SMTP/IMAP)') }}" />
    <x-text-input id="mail_hostname" name="mail_hostname" type="text" :value="old('mail_hostname', $server?->mail_hostname)" placeholder="mail.seudominio.com" />
    <div class="form-text">{{ __('Hostname que os clientes configuram no Outlook/celular pra SMTP e IMAP. Precisa ter certificado TLS válido emitido nesse servidor (ver deploy/README.md seção 22 do Agent). Só preencha se o Postfix/Dovecot já estiverem instalados.') }}</div>
    <x-input-error :messages="$errors->get('mail_hostname')" class="mt-2" />
</div>

<div class="mb-3">
    <x-input-label for="ftp_hostname" value="{{ __('Hostname de FTP') }}" />
    <x-text-input id="ftp_hostname" name="ftp_hostname" type="text" :value="old('ftp_hostname', $server?->ftp_hostname)" placeholder="ftp.seudominio.com" />
    <div class="form-text">{{ __('Opcional — por padrão os clientes conectam direto no IP do servidor, com certificado autoassinado (ver deploy/README.md seção 33 do Agent). Só preencha se quiser um hostname com certificado confiável emitido de verdade (Let\'s Encrypt), sem aviso no cliente FTP.') }}</div>
    <x-input-error :messages="$errors->get('ftp_hostname')" class="mt-2" />
</div>

<div class="row">
    <div class="col-6 mb-3">
        <x-input-label for="agent_port" value="{{ __('Porta do Agent') }}" />
        <x-text-input id="agent_port" name="agent_port" type="number" :value="old('agent_port', $server?->agent_port ?? 8443)" required />
        <x-input-error :messages="$errors->get('agent_port')" class="mt-2" />
    </div>
    <div class="col-6 mb-3">
        <x-input-label for="use_tls" value="{{ __('Usa TLS') }}" />
        <select id="use_tls" name="use_tls" class="form-select">
            <option value="1" @selected(old('use_tls', $server?->use_tls ?? true))>{{ __('Sim (produção)') }}</option>
            <option value="0" @selected(! old('use_tls', $server?->use_tls ?? true))>{{ __('Não (dev local)') }}</option>
        </select>
    </div>
</div>

<div class="mb-3">
    <x-input-label for="os" value="{{ __('Sistema operacional') }}" />
    <x-text-input id="os" name="os" type="text" :value="old('os', $server?->os)" placeholder="AlmaLinux 9" />
</div>

<div class="row">
    <div class="col-4 mb-3">
        <x-input-label for="cpu_cores" value="{{ __('vCPUs') }}" />
        <x-text-input id="cpu_cores" name="cpu_cores" type="number" :value="old('cpu_cores', $server?->cpu_cores)" />
    </div>
    <div class="col-4 mb-3">
        <x-input-label for="memory_mb" value="{{ __('RAM (MB)') }}" />
        <x-text-input id="memory_mb" name="memory_mb" type="number" :value="old('memory_mb', $server?->memory_mb)" />
    </div>
    <div class="col-4 mb-3">
        <x-input-label for="disk_gb" value="{{ __('Disco (GB)') }}" />
        <x-text-input id="disk_gb" name="disk_gb" type="number" :value="old('disk_gb', $server?->disk_gb)" />
    </div>
</div>

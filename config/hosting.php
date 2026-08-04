<?php

return [
    // Precisa espelhar exatamente as chaves de php_versions em
    // config/provisioning.php no Agent — senão o Agent rejeita a versão.
    'php_versions' => ['8.1', '8.2', '8.3', '8.4'],

    'default_php_version' => '8.3',

    // Porta dedicada do vhost do phpMyAdmin no Agent (deploy/README.md
    // seção 15) — pública, diferente da 8443 privada do Agent.
    'phpmyadmin_port' => 8444,

    // Porta dedicada do vhost do Roundcube (webmail) no Agent
    // (deploy/README.md seção 24) — pública, mesma lógica da 8444.
    'webmail_port' => 8445,

    // Porta dedicada do ttyd (terminal web) no Agent (deploy/README.md
    // seção 34) — um processo só pro servidor inteiro, não por conta
    // (mesma ideia do phpMyAdmin/webmail): o "terminal" é só um
    // xterm.js na aba do navegador que abre "ssh -t localhost", quem
    // autentica de verdade é o sshd com a senha de Acesso SSH que a
    // conta já tem configurada — nenhum sistema de credencial novo.
    'terminal_port' => 8446,

    // Quantas cópias mais recentes de cada backup (arquivos + cada
    // banco) o Agent mantém antes de apagar as mais antigas.
    'backup_retention' => 5,

    // "disabled" primeiro é o valor padrão (opt-in, não gera backup
    // sem o admin/cliente pedir).
    'backup_frequencies' => ['disabled', 'daily', 'weekly'],

    // Fallback usado só antes do admin salvar algo em "Configurações"
    // (tabela settings, chave "max_upload_mb") — dali em diante o valor
    // salvo no banco manda. Lembrar que o efetivo também depende do
    // client_max_body_size do nginx e do upload_max_filesize/post_max_size
    // do PHP-FPM, tanto do Painel quanto do Agent (ver deploy/README.md
    // do Agent, seção 26) — subir só aqui não basta se a infra estiver menor.
    'max_upload_mb' => 100,

    // Espelha config('provisioning.default_pool_settings') do Agent
    // (em MB/segundos aqui, não "128M"/"30" — a formatação pro Agent
    // acontece em HostingAccountProvisioningService::formatPoolSettings).
    // Usado como valor mostrado no formulário de "Configurações de PHP"
    // de uma conta antes dela ter algo salvo em php_fpm_settings.
    'default_pool_settings' => [
        'memory_limit' => 128,
        'upload_max_filesize' => 64,
        'post_max_size' => 64,
        'max_execution_time' => 30,
        'max_input_time' => 60,
        'max_input_vars' => 1000,
        'max_file_uploads' => 20,
        // Nome sem ponto de propósito — vira nome de campo/chave JSON
        // aqui, o PHP mangla ponto em nome de campo de formulário pra
        // underscore de qualquer forma. Só vira "session.gc_maxlifetime"
        // (o nome real da diretiva) no payload pro Agent, montado em
        // HostingAccountProvisioningService::formatPoolSettings.
        'session_gc_maxlifetime' => 1440,
        'display_errors' => false,
        'log_errors' => true,
        'error_reporting' => 'production',
        'file_uploads' => true,
        'short_open_tag' => false,
        'disable_functions' => [],
    ],

    // Opções do select de error_reporting no formulário de PHP — chave
    // salva em php_fpm_settings, valor é o rótulo mostrado. A expressão
    // PHP real de cada uma fica em
    // HostingAccountProvisioningService::ERROR_REPORTING_PRESETS.
    'error_reporting_presets' => [
        'production' => 'Produção (esconde avisos e notices)',
        'all' => 'Todos os erros (E_ALL)',
        'none' => 'Nenhum (silencioso)',
    ],

    // Precisa bater exatamente com App\Support\PhpFpmPoolSettings::DISABLABLE_FUNCTIONS
    // no Agent — lá é revalidado de novo por defesa em profundidade,
    // essa lista aqui só controla o que aparece como checkbox.
    'disablable_php_functions' => [
        'exec', 'shell_exec', 'system', 'passthru', 'proc_open', 'popen', 'show_source',
    ],
];

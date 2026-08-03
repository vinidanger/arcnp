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
];

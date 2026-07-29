<?php

return [
    // Precisa espelhar exatamente as chaves de php_versions em
    // config/provisioning.php no Agent — senão o Agent rejeita a versão.
    'php_versions' => ['8.1', '8.2', '8.3', '8.4'],

    'default_php_version' => '8.3',

    // Porta dedicada do vhost do phpMyAdmin no Agent (deploy/README.md
    // seção 15) — pública, diferente da 8443 privada do Agent.
    'phpmyadmin_port' => 8444,

    // Quantas cópias mais recentes de cada backup (arquivos + cada
    // banco) o Agent mantém antes de apagar as mais antigas.
    'backup_retention' => 5,

    // "disabled" primeiro é o valor padrão (opt-in, não gera backup
    // sem o admin/cliente pedir).
    'backup_frequencies' => ['disabled', 'daily', 'weekly'],
];

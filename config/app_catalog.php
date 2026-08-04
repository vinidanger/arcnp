<?php

/**
 * Catálogo do instalador de apps (item 15 do roadmap). Array de config,
 * não tabela — só 2 entradas, cada uma com lógica de instalação própria
 * e diferente (não é um sistema de plugins genérico), então uma tabela
 * seria complexidade sem benefício real por enquanto.
 */
return [
    'wordpress' => [
        'name' => 'WordPress',
        'requires_database' => true,
        'download_url' => 'https://wordpress.org/latest.zip',
        'min_php_version' => '7.4',
    ],

    'generic_zip' => [
        'name' => 'App genérico (upload de .zip)',
        'requires_database' => false,
    ],
];

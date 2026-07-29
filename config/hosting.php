<?php

return [
    // Precisa espelhar exatamente as chaves de php_versions em
    // config/provisioning.php no Agent — senão o Agent rejeita a versão.
    'php_versions' => ['8.1', '8.2', '8.3', '8.4'],

    'default_php_version' => '8.3',
];

<?php
// app/config/config.php

return [
    // Você pode usar a função env('CHAVE', 'padrao') para puxar do seu .env
    'base_url' => env('APP_URL'), // null fará o auto-detect base_url
    
    'default_controller' => env('DEFAULT_CONTROLLER', 'Home'),
    'default_method' => env('DEFAULT_METHOD', 'index'),
    
    // Você pode registrar novas chaves do env livremente:
    'app_env' => env('APP_ENV', 'dev'),
];
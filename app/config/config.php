<?php
// app/config/config.php

// Configurações do Sistema
define('APP_NAME', 'BT Log Transportes');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/bt-log-transportes/public');
define('UPLOAD_PATH', __DIR__ . '/../../storage/uploads/');

// Configurações de Segurança
define('ENCRYPTION_KEY', 'sua-chave-secreta-aqui-2024-btlog');
define('SESSION_TIMEOUT', 3600); // 1 hora em segundos

// Configurações de Desenvolvimento
define('DEBUG', true);
define('LOG_LEVEL', 'DEBUG'); // DEBUG, INFO, ERROR

// Cores padrão BT Log
define('COLOR_PRIMARY', '#FF6B00');
define('COLOR_SECONDARY', '#666666');
define('COLOR_BACKGROUND', '#F5F5F5');

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Headers de segurança
if (!headers_sent()) {
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
}
?>
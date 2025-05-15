<?php
// config_smtp.php

return [
    'smtp' => [
        'host' => '', // Servidor SMTP
        'port' => 587, // Puerto SMTP (587 para TLS, 465 para SSL)
        'username' => '', // Email del remitente
        'password' => '', // Contraseña o contraseña de aplicación
        'encryption' => 'tls', // 'tls' o 'ssl'
        'from_email' => '', // Email de remitente
        'from_name' => 'Sistema PEQUIVEN' // Nombre del remitente
    ],
    'recovery' => [
        'token_expiry' => '1 hour', // Tiempo de expiración del token
        'base_url' => 'http://localhost/PEQUIVEN' // URL base para enlaces
    ]
];
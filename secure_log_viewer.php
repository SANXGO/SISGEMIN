<?php
// secure_log_viewer.php
session_start();
require_once __DIR__ . '/includes/audit.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php'); // Redirigir a login
    exit();
}

// Verificar permisos de administrador (id_cargo = 3)
if ($_SESSION['id_cargo'] != 3) {
    header('HTTP/1.0 403 Forbidden');
    die('Acceso denegado. Solo administradores pueden ver este registro.');
}

// Clave de encriptación (debe coincidir con la usada para escribir)
$encryptionKey = 'tu_clave_secreta_aqui';

// Leer y desencriptar el archivo
$encryptedLogs = file(AUDIT_LOG_FILE, FILE_IGNORE_NEW_LINES);
$logs = [];

foreach ($encryptedLogs as $line) {
    $data = base64_decode($line);
    $ivSize = openssl_cipher_iv_length('aes-256-cbc');
    $iv = substr($data, 0, $ivSize);
    $encrypted = substr($data, $ivSize);
    $logs[] = openssl_decrypt($encrypted, 'aes-256-cbc', $encryptionKey, 0, $iv);
}

// Mostrar interfaz segura
?>
<!DOCTYPE html>
<html>
<head>
    <title>Visualizador de Logs de Auditoría</title>
    <style>
        .log-entry { font-family: monospace; margin-bottom: 10px; }
    </style>
</head>
<body>
    <h1>Logs de Auditoría</h1>
    <div id="logs">
        <?php foreach ($logs as $log): ?>
            <div class="log-entry"><?= htmlspecialchars($log) ?></div>
        <?php endforeach; ?>
    </div>
</body>
</html>
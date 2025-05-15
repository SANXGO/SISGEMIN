<?php
session_start();

// Registrar cierre de sesión si el usuario estaba logueado
if (isset($_SESSION['id_usuario'])) {
    require_once __DIR__ . '/includes/audit.php';
    
    logAuditAction('Login', 'Logout', "Cierre de sesión", [
        'user_id' => $_SESSION['id_usuario'],
        'username' => $_SESSION['usuario'],
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);
}

session_destroy();
header("Location: login.php");
exit();
?>
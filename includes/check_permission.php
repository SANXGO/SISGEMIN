<?php

if (!isset($_SESSION['usuario'])) {
    header("Location: ../login.php");
    exit();
}

require_once __DIR__ . '/conexion.php';

// Obtener la ruta actual
$ruta_actual = basename($_SERVER['PHP_SELF']);

// Verificar si el usuario tiene permiso para acceder a este módulo
$stmt = $pdo->prepare("
    SELECT 1 
    FROM permisos_cargo pc
    JOIN modulos m ON pc.id_modulo = m.id_modulo
    WHERE pc.id_cargo = ? AND pc.acceso = 1 AND m.ruta_modulo LIKE ?
");

// Buscar coincidencia exacta o parcial (para subdirectorios)
$stmt->execute([$_SESSION['id_cargo'], "%$ruta_actual"]);
$tiene_permiso = $stmt->fetch();

if (!$tiene_permiso) {
    header("Location: ../index.php?error=no_permission");
    exit();
}
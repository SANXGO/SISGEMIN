<?php
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/../../includes/audit.php';

session_start();

header('Content-Type: application/json');

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'error' => 'no_autenticado']);
    exit;
}
if (!isset($_GET['id_ubicacion'])) {
    echo json_encode(['success' => false, 'message' => 'ID de ubicación no proporcionado']);
    exit;
}

$id_ubicacion = $_GET['id_ubicacion'];

try {
    $pdo->beginTransaction();
    
    // 1. Activar la ubicación
    $stmt = $pdo->prepare("UPDATE ubicacion SET estado = 'activo' WHERE id_ubicacion = ?");
    $stmt->execute([$id_ubicacion]);
    
    // 2. Activar todos los equipos asociados a esta ubicación
    $stmt = $pdo->prepare("UPDATE equipos SET estado = 'activo' WHERE id_ubicacion = ?");
    $stmt->execute([$id_ubicacion]);
    
    $pdo->commit();
    logAuditAction('historial ubicacion', 'Update', "habilitar ubicacion", $_GET);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>
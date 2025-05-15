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

if (!isset($_GET['id_repuesto'])) {
    echo json_encode(['success' => false, 'message' => 'ID de repuesto no proporcionado']);
    exit;
}

$id_repuesto = $_GET['id_repuesto'];

try {
    $pdo->beginTransaction();
    
    // Actualizar estado del repuesto
    $stmt = $pdo->prepare("UPDATE repuestos SET estado = 'activo' WHERE id_repuestos = ?");
    $stmt->execute([$id_repuesto]);
    
    $pdo->commit();
    logAuditAction('historial repuesto', 'Update', "Habilitar repuesto", $_GET);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>
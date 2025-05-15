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




if (!isset($_GET['id_planta'])) {
    echo json_encode(['success' => false, 'message' => 'ID de planta no proporcionado']);
    exit;
}

$id_planta = $_GET['id_planta'];

try {
    $pdo->beginTransaction();

    // Habilitar la planta
    $stmt = $pdo->prepare("UPDATE planta SET estado = 'activo' WHERE id_planta = ?");
    $stmt->execute([$id_planta]);

    // Habilitar todas las ubicaciones de esta planta
    $stmt = $pdo->prepare("UPDATE ubicacion SET estado = 'activo' WHERE id_planta = ?");
    $stmt->execute([$id_planta]);

    // Habilitar todas las manual de esta planta
    $stmt = $pdo->prepare("UPDATE manual SET estado = 'activo' WHERE id_planta = ?");
    $stmt->execute([$id_planta]);

    // Habilitar todas las usuario de esta planta
    $stmt = $pdo->prepare("UPDATE usuario SET estado = 'activo' WHERE id_planta = ?");
    $stmt->execute([$id_planta]);


    // Habilitar todos los equipos de las ubicaciones de esta planta
    $stmt = $pdo->prepare("
        UPDATE equipos e
        JOIN ubicacion u ON e.id_ubicacion = u.id_ubicacion
        SET e.estado = 'activo'
        WHERE u.id_planta = ?
    ");
    $stmt->execute([$id_planta]);

    $pdo->commit();
    logAuditAction('historial Plant', 'Update', "Habilitar planta", $_GET);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>
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

if (!isset($_GET['tag_number'])) {
    echo json_encode(['success' => false, 'message' => 'Tag number no proporcionado']);
    exit;
}

$tag_number = $_GET['tag_number'];


try {
    // Deshabilitar el equipo
    $stmt = $pdo->prepare("UPDATE equipos SET estado = 'inactivo' WHERE Tag_Number = ?");
    $stmt->execute([$tag_number]);
    logAuditAction('historial equipos', 'Update', "Deshabilitar equipo", $_GET);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>
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
    // Verificar si la ubicación y planta están activas
    $stmt = $pdo->prepare("
        SELECT u.estado AS estado_ubicacion, p.estado AS estado_planta
        FROM equipos e
        JOIN ubicacion u ON e.id_ubicacion = u.id_ubicacion
        JOIN planta p ON u.id_planta = p.id_planta
        WHERE e.Tag_Number = ?
    ");
    $stmt->execute([$tag_number]);
    $result = $stmt->fetch();

    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Equipo no encontrado']);
        exit;
    }

    if ($result['estado_ubicacion'] !== 'activo' || $result['estado_planta'] !== 'activo') {
        echo json_encode([
            'success' => false, 
            'message' => 'No se puede habilitar el equipo porque la ubicación o la planta están inactivas'
        ]);
        exit;
    }

    // Habilitar el equipo
    $stmt = $pdo->prepare("UPDATE equipos SET estado = 'activo' WHERE Tag_Number = ?");
    $stmt->execute([$tag_number]);
    logAuditAction('historial equipos', 'Update', "Habilitar equipo", $_GET);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>
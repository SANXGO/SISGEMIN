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

// Verificar método de solicitud
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

// Verificar existencia de parámetro
if (!isset($_GET['id_manual'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de manual no proporcionado']);
    exit;
}

$id_manual = filter_var($_GET['id_manual'], FILTER_VALIDATE_INT);

if ($id_manual === false || $id_manual <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de manual inválido']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Verificar si el manual existe
    $stmt = $pdo->prepare("SELECT id_manual FROM manual WHERE id_manual = ?");
    $stmt->execute([$id_manual]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Manual no encontrado']);
        exit;
    }
    
    // Deshabilitar el manual
    $stmt = $pdo->prepare("UPDATE manual SET estado = 'inactivo' WHERE id_manual = ?");
    $stmt->execute([$id_manual]);
    
    $pdo->commit();
    logAuditAction('historial manual', 'Update', "Deshabilitar manual", $_GET);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>
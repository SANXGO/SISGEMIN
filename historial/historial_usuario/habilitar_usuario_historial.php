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

if (!isset($_GET['id_usuario'])) {
    echo json_encode(['success' => false, 'message' => 'ID de usuario no proporcionado']);
    exit;
}

$id_usuario = intval($_GET['id_usuario']);

try {
    $pdo->beginTransaction();
    
    // Habilitar el usuario
    $stmt = $pdo->prepare("UPDATE usuario SET estado = 'activo' WHERE id_usuario = ?");
    $stmt->execute([$id_usuario]);
    
    $pdo->commit();
    logAuditAction('historial usuario', 'Update', "habilitar usuario", $_GET);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>
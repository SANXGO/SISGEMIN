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

if (!isset($_GET['id_fabricante'])) {
    echo json_encode(['success' => false, 'message' => 'ID de fabricante no proporcionado']);
    exit;
}

$id_fabricante = $_GET['id_fabricante'];

try {
    $pdo->beginTransaction();
    
    // Primero deshabilitar todos los repuestos asociados
    $stmt = $pdo->prepare("UPDATE repuestos SET estado = 'inactivo' WHERE id_fabricante = ?");
    $stmt->execute([$id_fabricante]);
    
    // Luego deshabilitar el fabricante
    $stmt = $pdo->prepare("UPDATE fabricantes SET estado = 'inactivo' WHERE id_fabricante = ?");
    $stmt->execute([$id_fabricante]);
    
    $pdo->commit();
    logAuditAction('historial fabricante', 'Update', "Deshabilitar fabricante", $_GET);

    echo json_encode([
        'success' => true,
        'message' => 'Fabricante y sus repuestos asociados deshabilitados exitosamente'
    ]);
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error en la base de datos: ' . $e->getMessage()
    ]);
}
?>
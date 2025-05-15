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

if (isset($_GET['id_puente'])) {
    $id_puente = $_GET['id_puente'];
    logAuditAction('Repuesto-tag', 'Delete', "borrado de relacion ", $_POST);

    
    try {
        $stmt = $pdo->prepare("DELETE FROM equipo_repuesto WHERE id_puente = ?");
        $stmt->execute([$id_puente]);
        
        if ($stmt->rowCount() > 0) {
            logAuditAction('Repuesto-tag', 'Delete', "borrado exitoso de relacion ", $_POST);

            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'No se encontró la relación']);
        }
        exit();
    } catch (PDOException $e) {
        error_log('Error al eliminar relación: ' . $e->getMessage());
        echo json_encode(['success' => false, 'error' => 'Error en la base de datos']);
        exit();
    }
}

echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
?>
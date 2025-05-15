<?php
require_once __DIR__ . '/../includes/conexion.php';
require_once __DIR__ . '/../includes/audit.php';



session_start();

header('Content-Type: application/json');

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'error' => 'no_autenticado']);
    exit;
}

// Verificar si se recibió el ID del manual a borrar
if (isset($_GET['id_manual'])) {
    $id_manual = intval($_GET['id_manual']);

    if ($id_manual > 0) {
        try {
            
            $stmt = $pdo->prepare("DELETE FROM manual WHERE id_manual = ?");
            
            // Ejecutar la consulta
            $stmt->execute([$id_manual]);
            
            if ($stmt->rowCount() > 0) {
                logAuditAction('Manual', 'Delete', "borrado de manual", $_GET);

                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'No se encontró el manual para eliminar']);
            }
            exit();
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => 'Error en la base de datos: ' . $e->getMessage()]);
            exit();
        }
    }
}

echo json_encode(['success' => false, 'error' => 'ID de manual inválido']);
exit();
?>
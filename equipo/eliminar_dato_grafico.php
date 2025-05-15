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


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $tagNumber = $_POST['tag_number'] ?? ''; // Asegúrate de que el tag_number se envíe en la solicitud

    try {
        $stmt = $pdo->prepare("DELETE FROM equipo_graficos WHERE id = ?");
        $stmt->execute([$id]);
        
        // Llamar a logAuditAction después de eliminar el dato
        logAuditAction('equipos', 'GraphData', "Eliminado dato del gráfico (ID: $id) del equipo $tagNumber");

        echo json_encode(['success' => true, 'message' => 'Dato eliminado correctamente']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar el dato: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>
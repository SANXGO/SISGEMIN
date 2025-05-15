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
    $tagNumber = $_POST['tag_number'] ?? '';
    $ejeX = $_POST['ejeX'] ?? '';
    $ejeY = $_POST['ejeY'] ?? '';

    try {
        $stmt = $pdo->prepare("INSERT INTO equipo_graficos (tag_number, eje_x, eje_y) VALUES (?, ?, ?)");
        $stmt->execute([$tagNumber, $ejeX, $ejeY]);
        
        // Llamar a logAuditAction después de guardar el dato
        logAuditAction('equipos', 'GraphData', "Agregado dato al gráfico del equipo $tagNumber", [
            'eje_x' => $ejeX,
            'eje_y' => $ejeY
        ]);

        echo json_encode(['success' => true, 'message' => 'Dato guardado correctamente']);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Error al guardar el dato: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>
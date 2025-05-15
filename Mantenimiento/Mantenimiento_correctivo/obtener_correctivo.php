<?php
require_once __DIR__ . '/../../includes/conexion.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'ID no proporcionado']);
    exit;
}

$id_intervencion = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_intervencion) {
    echo json_encode(['error' => 'ID inválido']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM intervencion WHERE id_inter = ?");
    $stmt->execute([$id_intervencion]);
    $intervencion = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($intervencion) {
        echo json_encode($intervencion);
    } else {
        echo json_encode(['error' => 'Intervención no encontrada']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
exit;
?>
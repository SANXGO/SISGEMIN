<?php
require_once __DIR__ . '/../../includes/conexion.php';

header('Content-Type: application/json');

$id_repuesto = $_GET['id_repuesto'] ?? '';

if (empty($id_repuesto)) {
    echo json_encode(['error' => 'ID de repuesto no proporcionado']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT r.*, f.nombres as fabricante 
        FROM repuestos r
        JOIN fabricantes f ON r.id_fabricante = f.id_fabricante
        WHERE r.id_repuestos = ?
    ");
    $stmt->execute([$id_repuesto]);
    $repuesto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($repuesto) {
        echo json_encode($repuesto);
    } else {
        echo json_encode(['error' => 'Repuesto no encontrado']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error al obtener detalles: ' . $e->getMessage()]);
}
<?php
require_once __DIR__ . '/../../includes/conexion.php';

header('Content-Type: application/json');

$id_ubicacion = $_GET['id_ubicacion'] ?? null;

if (!$id_ubicacion) {
    echo json_encode(['success' => false, 'error' => 'ID de ubicación no proporcionado']);
    exit;
}

try {
    // Consulta para obtener tags activos en la ubicación especificada
    $query = "SELECT Tag_Number FROM equipos 
              WHERE id_ubicacion = ? AND estado = 'activo'
              ORDER BY Tag_Number";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$id_ubicacion]);
    $tags = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'success' => true,
        'tags' => $tags
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error en la base de datos: ' . $e->getMessage()
    ]);
}
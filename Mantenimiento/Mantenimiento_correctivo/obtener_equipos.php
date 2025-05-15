<?php
require_once __DIR__ . '/../../includes/conexion.php';

header('Content-Type: application/json');

$id_ubicacion = $_GET['id_ubicacion'] ?? '';

if (empty($id_ubicacion)) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT Tag_Number 
        FROM equipos 
        WHERE id_ubicacion = ? AND estado = 'activo'
        ORDER BY Tag_Number
    ");
    $stmt->execute([$id_ubicacion]);
    $equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($equipos);
} catch (PDOException $e) {
    error_log('Error en obtener_equipos.php: ' . $e->getMessage());
    echo json_encode(['error' => 'Error al cargar equipos']);
}
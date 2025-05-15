<?php
require_once __DIR__ . '/../includes/conexion.php';

header('Content-Type: application/json');

$id_ubicacion = $_GET['id_ubicacion'] ?? null;

if (!$id_ubicacion) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT Tag_Number, Instrument_Type_Desc 
        FROM equipos 
        WHERE id_ubicacion = ? 
        AND estado = 'activo'
        ORDER BY Tag_Number
    ");
    $stmt->execute([$id_ubicacion]);
    $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($tags);
} catch (PDOException $e) {
    error_log('Error en obtener_tags.php: ' . $e->getMessage());
    echo json_encode([]);
}
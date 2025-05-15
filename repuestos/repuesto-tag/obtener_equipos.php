<?php
require_once __DIR__ . '/../../includes/conexion.php';

header('Content-Type: application/json');

$id_ubicacion = $_GET['id_ubicacion'] ?? null;

if (!$id_ubicacion) {
    echo json_encode([]);
    exit;
}

try {
    $sql = "SELECT Tag_Number, Instrument_Type_Desc 
            FROM equipos 
            WHERE id_ubicacion = ? AND estado = 'activo'
            ORDER BY Tag_Number";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_ubicacion]);
    $equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($equipos);
} catch (PDOException $e) {
    error_log('Error al obtener equipos: ' . $e->getMessage());
    echo json_encode([]);
}
?>
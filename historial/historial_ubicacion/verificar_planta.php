<?php
require_once __DIR__ . '/../../includes/conexion.php';

header('Content-Type: application/json');

if (!isset($_GET['id_ubicacion'])) {
    echo json_encode(['error' => 'ID de ubicación no proporcionado']);
    exit;
}

$id_ubicacion = $_GET['id_ubicacion'];

try {
    // Consulta para obtener el estado de la planta asociada a la ubicación
    $stmt = $pdo->prepare("
        SELECT p.estado 
        FROM ubicacion u
        JOIN planta p ON u.id_planta = p.id_planta
        WHERE u.id_ubicacion = :id_ubicacion
    ");
    $stmt->bindParam(':id_ubicacion', $id_ubicacion);
    $stmt->execute();
    
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$resultado) {
        echo json_encode(['error' => 'Ubicación no encontrada']);
        exit;
    }
    
    echo json_encode([
        'planta_activa' => $resultado['estado'] === 'activo'
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
}
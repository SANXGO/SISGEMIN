<?php
require_once __DIR__ . '/../../includes/conexion.php';

header('Content-Type: application/json');

if (!isset($_GET['tag_number']) || empty($_GET['tag_number'])) {
    echo json_encode([]);
    exit;
}

$tagNumber = $_GET['tag_number'];

try {
    // Consulta para obtener los repuestos asociados al Tag Number
    $query = "SELECT r.id_repuestos, r.real_part, r.descripcion 
              FROM repuestos r
              JOIN equipo_repuesto er ON r.id_repuestos = er.id_repuesto
              JOIN equipos e ON er.id_equipo = e.Tag_Number
              WHERE e.Tag_Number = :tag_number AND r.estado = 'activo'
              ORDER BY r.real_part";
    
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':tag_number', $tagNumber, PDO::PARAM_STR);
    $stmt->execute();
    
    $repuestos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($repuestos);
} catch (PDOException $e) {
    error_log("Error al obtener repuestos: " . $e->getMessage());
    echo json_encode([]);
}
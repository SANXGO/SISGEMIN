<?php
require_once __DIR__ . '/../includes/conexion.php';

$tagNumber = $_GET['tag_number'] ?? '';

try {
    $stmt = $pdo->prepare("SELECT * FROM equipo_graficos WHERE tag_number = ? ORDER BY fecha_creacion ASC");
    $stmt->execute([$tagNumber]);
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($datos);
} catch (PDOException $e) {
    echo json_encode([]);
}
?>
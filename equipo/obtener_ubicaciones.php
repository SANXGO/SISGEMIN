<?php
require_once __DIR__ . '/../includes/conexion.php';

if (isset($_GET['id_planta'])) {
    $idPlanta = $_GET['id_planta'];
    
    try {
        $stmt = $pdo->prepare("SELECT id_ubicacion, descripcion FROM ubicacion WHERE id_planta = ?");
        $stmt->execute([$idPlanta]);
        $ubicaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode($ubicaciones);
    } catch (PDOException $e) {
        die(json_encode(['error' => 'Error al obtener ubicaciones: ' . $e->getMessage()]));
    }
}
?>
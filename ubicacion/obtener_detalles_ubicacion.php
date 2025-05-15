<?php
require_once __DIR__ . '/../includes/conexion.php';

if (isset($_GET['id_ubicacion'])) {
    $id_ubicacion = $_GET['id_ubicacion'];
    
    try {
        $stmt = $pdo->prepare("
            SELECT u.id_ubicacion, u.id_planta, u.descripcion, p.nombres AS nombre_planta 
            FROM ubicacion u
            JOIN planta p ON u.id_planta = p.id_planta
            WHERE u.id_ubicacion = ?
        ");
        $stmt->execute([$id_ubicacion]);
        $ubicacion = $stmt->fetch(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode($ubicacion);
    } catch (PDOException $e) {
        die("Error al obtener detalles: " . $e->getMessage());
    }
}
?>
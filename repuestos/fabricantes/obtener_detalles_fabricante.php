<?php
require_once __DIR__ . '/../../includes/conexion.php';

header('Content-Type: application/json');

if (isset($_GET['id_fabricante'])) {
    $id_fabricante = $_GET['id_fabricante'];
    
    try {
        $stmt = $pdo->prepare("SELECT id_fabricante, nombres FROM fabricantes WHERE id_fabricante = ?");
        $stmt->execute([$id_fabricante]);
        $fabricante = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($fabricante) {
            echo json_encode($fabricante);
        } else {
            echo json_encode(['error' => 'Fabricante no encontrado']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Error al obtener los datos']);
    }
} else {
    echo json_encode(['error' => 'ID de fabricante no proporcionado']);
}
?>
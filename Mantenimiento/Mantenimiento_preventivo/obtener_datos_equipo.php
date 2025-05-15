<?php
error_reporting(0); // Desactivar errores PHP
ini_set('display_errors', 0);

require_once __DIR__ . '/../../includes/conexion.php';

// Verificar si la conexión a la base de datos es exitosa
if ($pdo === null) {
    echo json_encode(['error' => 'Error de conexión a la base de datos']);
    exit;
}

header('Content-Type: application/json'); 

$tagNumber = isset($_GET['tag_number']) ? $_GET['tag_number'] : '';
// obtener_datos_equipo.php
$query = "SELECT e.instrument_type_desc, p.nombres AS planta 
          FROM equipos e
          JOIN ubicacion u ON e.id_ubicacion = u.id_ubicacion
          JOIN planta p ON u.id_planta = p.id_planta
          WHERE e.Tag_Number = ?";

$stmt = $pdo->prepare($query);
$stmt->execute([$tagNumber]);
$equipo = $stmt->fetch(PDO::FETCH_ASSOC);

if ($equipo) {
    echo json_encode($equipo);
} else {
    echo json_encode(['error' => 'Equipo no encontrado']);
}
?>
<?php
require_once __DIR__ . '/../../includes/conexion.php';

if (isset($_GET['tag_number'])) {
    $tagNumber = $_GET['tag_number'];

    // Consulta para obtener el nombre de la planta basado en el Tag_Number
    $query = "
        SELECT p.nombres 
        FROM equipos e
        JOIN ubicacion u ON e.id_ubicacion = u.id_ubicacion
        JOIN planta p ON u.id_planta = p.id_planta
        WHERE e.Tag_Number = :tag_number
    ";

    $stmt = $pdo->prepare($query);
    $stmt->execute(['tag_number' => $tagNumber]);
    $planta = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($planta) {
        echo json_encode(['success' => true, 'planta' => $planta['nombres']]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Planta no encontrada']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Tag Number no proporcionado']);
}
?>
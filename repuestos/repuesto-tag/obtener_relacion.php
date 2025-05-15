<?php
// repuestos/obtener_relacion.php
require_once __DIR__ . '/../../includes/conexion.php';

if (isset($_GET['id_puente'])) {
    $id_puente = $_GET['id_puente'];
    
    $sql = "
        SELECT 
            er.id_puente,
            r.descripcion,
            r.real_part,
            e.Tag_Number,
            e.Instrument_Type_Desc
        FROM 
            equipo_repuesto er
        JOIN 
            repuestos r ON er.id_repuesto = r.id_repuestos
        JOIN 
            equipos e ON er.id_equipo = e.Tag_Number
        WHERE 
            er.id_puente = ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_puente]);
    $relacion = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($relacion) {
        header('Content-Type: application/json');
        echo json_encode($relacion);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Relación no encontrada']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'ID de relación no proporcionado']);
}
?>
<?php
require_once __DIR__ . '/../includes/conexion.php';
require_once __DIR__ . '/../includes/audit.php';

session_start();

header('Content-Type: application/json');

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'error' => 'no_autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_actividad = $_POST['id_actividad'];
    logAuditAction('actividades', 'Update', "Editar actividad $id_actividad", $_POST);

    $id_usuario = $_POST['id_usuario'];
    $orden = $_POST['orden'];
    $num_permiso = $_POST['num_permiso'];
    $planta = $_POST['planta'];
    $ubicacion = $_POST['ubicacion'];
    $tag_number = $_POST['tag_number'];
    $actividad = $_POST['actividad'];
    $especialistas = $_POST['especialistas'];
    $tiempo = $_POST['tiempo'];
    $recurso_apoyo = $_POST['recurso_apoyo'];
    $fecha = $_POST['fecha'];
    $avance = $_POST['avance'];
    $observacion = $_POST['observacion'];

    $sql = "UPDATE actividades SET 
            id_usuario = ?, 
            orden = ?, 
            num_permiso = ?, 
            planta = ?, 
            ubicacion = ?, 
            tag_number = ?, 
            actividad = ?, 
            especialistas = ?, 
            tiempo = ?, 
            recurso_apoyo = ?, 
            fecha = ?, 
            avance = ?, 
            observacion = ?
            WHERE id_actividad = ?";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_usuario, $orden, $num_permiso, $planta, $ubicacion, $tag_number, $actividad, $especialistas, $tiempo, $recurso_apoyo, $fecha, $avance, $observacion, $id_actividad]);
    logAuditAction('actividades', 'Update', "editado exitosamente actividad $id_actividad", $_POST);

    header("Location: ../planificacion/detalles_actividades.php?id=".urlencode($id_actividad));
        exit;
}
?>
<?php
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/../../includes/audit.php';


session_start();

header('Content-Type: application/json');

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'error' => 'no_autenticado']);
    exit;
}


// Verificar si la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Verificar si se proporcionó un ID
if (!isset($_POST['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'ID no proporcionado']);
    exit;
}

$id_mantenimiento = $_POST['id'];
logAuditAction('Mantenimiento preventivo', 'Update', "Edicion de mantenimiento preventivo $id_mantenimiento", $_POST);

// Recoger los datos del formulario
$tag_number = $_POST['tag_number'] ?? '';
$fecha = $_POST['fecha'] ?? '';
$planta = $_POST['planta'] ?? '';
$orden = $_POST['orden'] ?? '';
$instalacion = $_POST['instalacion'] ?? '';
$serial = $_POST['serial'] ?? '';
$modelo = $_POST['modelo'] ?? '';
$tipo_mantenimiento = $_POST['mantenimiento'] ?? '';
$medicion_metrica = $_POST['medicion_metrica'] ?? '';
$vdc_24 = $_POST['24vdc'] ?? '';
$observaciones = $_POST['observaciones'] ?? '';
$sintomas = $_POST['sintomas'] ?? '';

try {
    // Preparar la consulta SQL para actualizar el registro
    $stmt = $pdo->prepare("UPDATE mantenimiento SET 
        Tag_Number = ?,
        fecha = ?,
        planta = ?,
        orden = ?,
        instalacion = ?,
        serial = ?,
        modelo = ?,
        mantenimiento = ?,
        medicion_metrica = ?,
        `24vdc` = ?,
        observaciones = ?,
        sintomas = ?
        WHERE id_mantenimiento = ?");

    // Ejecutar la consulta con los parámetros
    $stmt->execute([
        $tag_number,
        $fecha,
        $planta,
        $orden,
        $instalacion,
        $serial,
        $modelo,
        $tipo_mantenimiento,
        $medicion_metrica,
        $vdc_24,
        $observaciones,
        $sintomas,
        $id_mantenimiento
    ]);

    // Devolver respuesta JSON
    header('Content-Type: application/json');

    logAuditAction('Mantenimiento preventivo', 'Update', "Editado exitosamente el mantenimiento preventivo $id_mantenimiento", $_POST);

    echo json_encode(['success' => true, 'message' => 'Mantenimiento actualizado correctamente']);
    exit;
} catch (PDOException $e) {
    // En caso de error, devolver respuesta JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Error al actualizar el mantenimiento: ' . $e->getMessage()]);
    exit;
}
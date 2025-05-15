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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../actividades.php');
    exit;
}

// Validar y sanitizar los datos de entrada
$id_usuario = filter_input(INPUT_POST, 'id_usuario', FILTER_VALIDATE_INT);
logAuditAction('actividades', 'Update', "Agregar actividad  $id_usuario", $_POST);

$orden = filter_input(INPUT_POST, 'orden', FILTER_SANITIZE_STRING);
$num_permiso = filter_input(INPUT_POST, 'num_permiso', FILTER_SANITIZE_STRING);
$planta = filter_input(INPUT_POST, 'planta', FILTER_SANITIZE_STRING);
$ubicacion = filter_input(INPUT_POST, 'ubicacion', FILTER_SANITIZE_STRING);
$tag_number = filter_input(INPUT_POST, 'tag_number', FILTER_SANITIZE_STRING);
$actividad = filter_input(INPUT_POST, 'actividad', FILTER_SANITIZE_STRING);
$especialistas = filter_input(INPUT_POST, 'especialistas', FILTER_SANITIZE_STRING);
$tiempo = filter_input(INPUT_POST, 'tiempo', FILTER_SANITIZE_STRING);
$recurso_apoyo = filter_input(INPUT_POST, 'recurso_apoyo', FILTER_SANITIZE_STRING);
$fecha = filter_input(INPUT_POST, 'fecha', FILTER_SANITIZE_STRING);
$avance = filter_input(INPUT_POST, 'avance', FILTER_VALIDATE_INT);
$observacion = filter_input(INPUT_POST, 'observacion', FILTER_SANITIZE_STRING);

// Validaciones adicionales
$errores = [];

// 1. Validar Orden (solo números, máximo 12 dígitos, único)
if (!preg_match('/^\d{1,12}$/', $orden)) {
    $errores[] = "El campo Orden debe contener solo números (máximo 12 dígitos)";
} else {
    // Verificar si la orden ya existe
    $stmt = $pdo->prepare("SELECT id_actividad FROM actividades WHERE orden = ?");
    $stmt->execute([$orden]);
    if ($stmt->fetch()) {
        $errores[] = "La orden ingresada ya existe en la base de datos";
    }
}

// 2. Validar Número de Permiso (solo números, máximo 10 dígitos)
if (!preg_match('/^\d{1,10}$/', $num_permiso)) {
    $errores[] = "El campo Número de Permiso debe contener solo números (máximo 10 dígitos)";
}

// 3. Validar Actividad (hasta 115 caracteres)
if (strlen($actividad) > 115) {
    $errores[] = "El campo Actividad no puede exceder los 115 caracteres";
}

// 4. Validar Especialistas (letras y números, hasta 15 caracteres)
if (!preg_match('/^[a-zA-Z0-9\s]{1,15}$/', $especialistas)) {
    $errores[] = "El campo Especialistas solo puede contener letras y números (máximo 15 caracteres)";
}

// 5. Validar Tiempo (formato HH:MM)
if (!preg_match('/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/', $tiempo)) {
    $errores[] = "El campo Tiempo debe tener formato HH:MM";
}

// 6. Validar Recurso de Apoyo (letras y números, hasta 20 caracteres)
if (!preg_match('/^[a-zA-Z0-9\s]{1,20}$/', $recurso_apoyo)) {
    $errores[] = "El campo Recurso de Apoyo solo puede contener letras y números (máximo 20 caracteres)";
}

// 7. Validar Fecha (entre 2000 y hoy)
$fechaActual = new DateTime();
$fechaIngresada = new DateTime($fecha);
$fechaMinima = new DateTime('2000-01-01');

if ($fechaIngresada < $fechaMinima || $fechaIngresada > $fechaActual) {
    $errores[] = "La fecha debe estar entre el año 2000 y la fecha actual";
}

// 8. Validar Avance (1-100)
if ($avance < 1 || $avance > 100) {
    $errores[] = "El avance debe ser un valor entre 1 y 100";
}

// 9. Validar Observación (hasta 60 caracteres)
if (strlen($observacion) > 60) {
    $errores[] = "El campo Observación no puede exceder los 60 caracteres";
}

// Si hay errores, redirigir con mensajes
if (!empty($errores)) {
    $_SESSION['error'] = implode("<br>", $errores);
    header('Location: ../index.php?tabla=actividades');
    exit;
}

try {
    // Insertar la nueva actividad
    $stmt = $pdo->prepare("
        INSERT INTO actividades (
            id_usuario, orden, num_permiso, planta, ubicacion, tag_number, 
            actividad, especialistas, tiempo, recurso_apoyo, fecha, avance, observacion
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $id_usuario, $orden, $num_permiso, $planta, $ubicacion, $tag_number,
        $actividad, $especialistas, $tiempo, $recurso_apoyo, $fecha, $avance, $observacion
    ]);
    
    $_SESSION['success'] = "Actividad agregada correctamente";
} catch (PDOException $e) {
    $_SESSION['error'] = "Error al agregar la actividad: " . $e->getMessage();
}
logAuditAction('actividades', 'Update', "registrado exitosamente la actividad $id_usuario", $_POST);

header('Location: ../index.php?tabla=actividades');
exit;
?>
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
// Verificar si es una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Validar y obtener datos del formulario
$id_intervencion = filter_input(INPUT_POST, 'id_intervencion', FILTER_VALIDATE_INT);
if (!$id_intervencion) {
    echo json_encode(['success' => false, 'error' => 'ID inválido']);
    exit;
}

logAuditAction('Mantenimiento correctivo', 'Update', "Edicion de mantenimiento correctivo $id_intervencion", $_POST);

// Recoger y sanitizar los datos
$datos = [
    'descripcion' => trim(filter_input(INPUT_POST, 'descripcion', FILTER_SANITIZE_STRING)),
    'fecha' => trim(filter_input(INPUT_POST, 'fecha', FILTER_SANITIZE_STRING)),
    'repuestos' => trim(filter_input(INPUT_POST, 'repuestos', FILTER_SANITIZE_STRING)),
    'materiales' => trim(filter_input(INPUT_POST, 'materiales', FILTER_SANITIZE_STRING)),
    'tiempo' => trim(filter_input(INPUT_POST, 'tiempo', FILTER_SANITIZE_STRING)),
    'responsable' => trim(filter_input(INPUT_POST, 'responsable', FILTER_SANITIZE_STRING))
];

// Validar datos requeridos
$errores = [];

if (empty($datos['descripcion'])) {
    $errores[] = 'La descripción es requerida';
} elseif (strlen($datos['descripcion']) > 90) {
    $errores[] = 'La descripción no debe exceder los 90 caracteres';
}

if (empty($datos['fecha'])) {
    $errores[] = 'La fecha es requerida';
} else {
    $fecha = new DateTime($datos['fecha']);
    $hoy = new DateTime();
    $hoy->setTime(0, 0, 0);
    $añoMinimo = new DateTime('2000-01-01');
    
    if ($fecha > $hoy || $fecha < $añoMinimo) {
        $errores[] = 'La fecha debe estar entre el año 2000 y hoy';
    }
}

if (empty($datos['repuestos'])) {
    $errores[] = 'Los repuestos son requeridos';
} elseif (strlen($datos['repuestos']) > 30) {
    $errores[] = 'Los repuestos no deben exceder los 30 caracteres';
}

if (empty($datos['materiales'])) {
    $errores[] = 'Los materiales son requeridos';
} elseif (strlen($datos['materiales']) > 30) {
    $errores[] = 'Los materiales no deben exceder los 30 caracteres';
}

if (empty($datos['responsable'])) {
    $errores[] = 'El responsable es requerido';
} elseif (!preg_match('/^[A-Za-zÁ-Úá-ú\s]{1,15}$/', $datos['responsable'])) {
    $errores[] = 'El responsable debe contener solo letras y tener máximo 15 caracteres';
}

if (!empty($errores)) {
    echo json_encode(['success' => false, 'error' => implode(', ', $errores)]);
    exit;
}

try {
    // Preparar consulta de actualización (sin updated_at)
    $sql = "UPDATE intervencion SET 
            descripcion = ?,
            fecha = ?,
            repuestos = ?,
            materiales = ?,
            tiempo = ?,
            responsable = ?
            WHERE id_inter = ?";
    
    $stmt = $pdo->prepare($sql);
    
    // Ejecutar la actualización (un parámetro menos)
    $result = $stmt->execute([
        $datos['descripcion'],
        $datos['fecha'],
        $datos['repuestos'],
        $datos['materiales'],
        $datos['tiempo'],
        $datos['responsable'],
        $id_intervencion
    ]);
    

    logAuditAction('Mantenimiento correctivo', 'Update', "Editado exitosamente el mantenimiento correctivo $id_intervencion", $_POST);

    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Error al actualizar el mantenimiento']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
exit;
?>
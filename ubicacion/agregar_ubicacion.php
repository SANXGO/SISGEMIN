<?php
require_once __DIR__ . '/../includes/conexion.php';
require_once __DIR__ . '/../includes/audit.php';

// Iniciar sesión para obtener el usuario
session_start();

header('Content-Type: application/json');

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'error' => 'no_autenticado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_ubicacion']) || !isset($_POST['id_planta']) || !isset($_POST['descripcion'])) {
    echo json_encode(['success' => false, 'error' => 'datos_invalidos']);
    exit;
}

$idUbicacion = trim($_POST['id_ubicacion']);
// Pasar el ID de usuario a la función de auditoría
logAuditAction('ubicacion', 'Update', "Inicio de registro de la ubicacion $idUbicacion", $_POST, $_SESSION['id_usuario']);

$idPlanta = $_POST['id_planta'];
$descripcion = trim($_POST['descripcion']);

// Validar que el ID de ubicación no exista ya
$stmt = $pdo->prepare("SELECT COUNT(*) FROM ubicacion WHERE id_ubicacion = :id_ubicacion");
$stmt->bindValue(':id_ubicacion', $idUbicacion, PDO::PARAM_STR);
$stmt->execute();

if ($stmt->fetchColumn() > 0) {
    echo json_encode(['success' => false, 'error' => 'id_existente']);
    exit;
}

// Insertar la nueva ubicación
try {
    $stmt = $pdo->prepare("INSERT INTO ubicacion (id_ubicacion, id_planta, descripcion) VALUES (:id_ubicacion, :id_planta, :descripcion)");
    $stmt->bindValue(':id_ubicacion', $idUbicacion, PDO::PARAM_STR);
    $stmt->bindValue(':id_planta', $idPlanta, PDO::PARAM_INT);
    $stmt->bindValue(':descripcion', $descripcion, PDO::PARAM_STR);
    $stmt->execute();
    
    // Pasar el ID de usuario a la función de auditoría
    logAuditAction('ubicacion', 'Update', "registro exitoso de la ubicacion $idUbicacion", $_POST, $_SESSION['id_usuario']);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'error_bd', 'message' => $e->getMessage()]);
}
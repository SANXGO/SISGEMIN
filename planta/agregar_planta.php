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
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['nombres'])) {
    echo json_encode(['success' => false, 'error' => 'datos_invalidos']);
    exit;
}

$nombre = trim($_POST['nombres']);

logAuditAction('planta', 'Create', "Inicio de creación de la nueva planta", $nombre, $_POST, $_SESSION['id_usuario']);

// Validar longitud máxima
if (strlen($nombre) > 30) {
    echo json_encode(['success' => false, 'error' => 'nombre_demasiado_largo']);
    exit;
}

// Validar que no exista ya
$stmt = $pdo->prepare("SELECT COUNT(*) FROM planta WHERE nombres = :nombre");
$stmt->bindValue(':nombre', $nombre, PDO::PARAM_STR);
$stmt->execute();

if ($stmt->fetchColumn() > 0) {
    echo json_encode(['success' => false, 'error' => 'planta_existente']);
    exit;
}

// Insertar la nueva planta
try {
    $stmt = $pdo->prepare("INSERT INTO planta (nombres) VALUES (:nombre)");
    $stmt->bindValue(':nombre', $nombre, PDO::PARAM_STR);
    $stmt->execute();
    
    logAuditAction('planta', 'Create', "creacion exitosa de la nueva planta", $nombre, $_POST);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'error_bd', 'message' => $e->getMessage()]);
}
?>
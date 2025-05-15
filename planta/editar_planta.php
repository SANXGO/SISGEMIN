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

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_planta']) || !isset($_POST['nombres'])) {
    echo json_encode(['success' => false, 'error' => 'datos_invalidos']);
    exit;
}

$idPlanta = $_POST['id_planta'];
logAuditAction('planta', 'Update', "Inicio de edición de la planta $idPlanta", $_POST, $_SESSION['id_usuario']);

$nombre = trim($_POST['nombres']);

// Validar longitud máxima
if (strlen($nombre) > 30) {
    echo json_encode(['success' => false, 'error' => 'nombre_demasiado_largo']);
    exit;
}

// Validar que no exista ya (excluyendo el registro actual)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM planta WHERE nombres = :nombre AND id_planta != :id_planta");
$stmt->bindValue(':nombre', $nombre, PDO::PARAM_STR);
$stmt->bindValue(':id_planta', $idPlanta, PDO::PARAM_INT);
$stmt->execute();

if ($stmt->fetchColumn() > 0) {
    echo json_encode(['success' => false, 'error' => 'planta_existente']);
    exit;
}

// Actualizar la planta
try {
    $stmt = $pdo->prepare("UPDATE planta SET nombres = :nombre WHERE id_planta = :id_planta");
    $stmt->bindValue(':nombre', $nombre, PDO::PARAM_STR);
    $stmt->bindValue(':id_planta', $idPlanta, PDO::PARAM_INT);
    $stmt->execute();
    

    logAuditAction('planta', 'Update', "edición exitosa de la planta $idPlanta", $_POST);


    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'error_bd', 'message' => $e->getMessage()]);
}
?>
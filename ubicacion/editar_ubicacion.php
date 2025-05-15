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
    $id_ubicacion = $_POST['id_ubicacion'];
    logAuditAction('ubicacion', 'Update', "Inicio de edición de la ubicacion $id_ubicacion", $_POST, $_SESSION['id_usuario']);

    $id_planta = $_POST['id_planta'];
    $descripcion = $_POST['descripcion'];

    try {
        $stmt = $pdo->prepare("UPDATE ubicacion SET id_planta = ?, descripcion = ? WHERE id_ubicacion = ?");
        $stmt->execute([$id_planta, $descripcion, $id_ubicacion]);

        logAuditAction('ubicacion', 'Update', "edición exitosa de la ubicacion $id_ubicacion", $_POST);

        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'error_bd', 'message' => $e->getMessage()]);
    }
}
?>
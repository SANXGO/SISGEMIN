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


if (isset($_GET['id_actividad'])) {
    $id_actividad = $_GET['id_actividad'];
    logAuditAction('actividades', 'Update', "Editar Actividad $id_actividad", $_POST);

    $sql = "DELETE FROM actividades WHERE id_actividad = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id_actividad]);
    logAuditAction('actividades', 'Update', "borrado exitosamente Actividad $id_actividad", $_POST);

    header('Location: ../index.php?tabla=actividades');
    exit;
}
?>
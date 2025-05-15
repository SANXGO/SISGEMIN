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
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = $_GET['id'];
    logAuditAction('Mantenimiento preventivo', 'Update', "Borrar el mantenimiento preventivo $id", $_POST);

    $query = "DELETE FROM mantenimiento WHERE id_mantenimiento = ?";
    $stmt = $pdo->prepare($query);
    
    if ($stmt->execute([$id])) {
        logAuditAction('Mantenimiento preventivo', 'Update', "borrado exitosamente el  mantenimiento preventivo $id", $_POST);

        header('Location: ../../index.php?tabla=preventivo&success=delete');
    } else {
        header('Location: ../../index.php?tabla=preventivo&error=No se pudo eliminar el registro');
    }
    exit();
}
?>
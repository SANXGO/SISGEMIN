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
// Verificar si es una petición AJAX
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $id = $_GET['id'];
    logAuditAction('Mantenimiento Correctivo', 'Update', "Borrar Mantenimiento Correctivo $id", $_POST);

    try {
        $query = "DELETE FROM intervencion WHERE id_inter = ?";
        $stmt = $pdo->prepare($query);
        $success = $stmt->execute([$id]);

        if ($isAjax) {
            // Respuesta para AJAX
            header('Content-Type: application/json');
            logAuditAction('Mantenimiento Correctivo', 'Update', "Borrado exitosamente Mantenimiento Correctivo $id", $_POST);

            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Registro eliminado correctamente' : 'Error al eliminar el registro'
            ]);
            exit;
        } else {
            // Redirección tradicional
            header('Location: ../../index.php?tabla=Mantenimiento_correctivo&borrado=1');
            exit();
        }
    } catch (PDOException $e) {
        error_log("Error al eliminar intervención: " . $e->getMessage());
        
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Error en la base de datos: ' . $e->getMessage()
            ]);
            exit;
        } else {
            header('Location: ../../index.php?tabla=Mantenimiento_correctivo&error=1');
            exit();
        }
    }
}

// Si no es GET con ID o hay otro error
if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Método no permitido o ID no proporcionado'
    ]);
} else {
    header('Location: ../../index.php?tabla=Mantenimiento_correctivo&error=1');
}
exit;
?>
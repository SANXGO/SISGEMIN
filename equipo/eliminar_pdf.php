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

try {
    if (!isset($_POST['id'])) {
        throw new Exception("ID no proporcionado.");
    }

    $id = $_POST['id'];
    $tagNumber = '12345'; // Aquí debes definir el número de etiqueta correspondiente

    // Obtener la ruta del archivo
    $stmt = $pdo->prepare("SELECT ruta_archivo FROM archivos_pdf WHERE id = ?");
    $stmt->execute([$id]);
    $pdf = $stmt->fetch();

    if (!$pdf) {
        throw new Exception("Archivo no encontrado en la base de datos.");
    }

    // Eliminar el archivo del sistema de archivos
    $filePath = $_SERVER['DOCUMENT_ROOT'] . $pdf['ruta_archivo'];
    if (file_exists($filePath)) {
        if (!unlink($filePath)) {
            throw new Exception("No se pudo eliminar el archivo físico.");
        }
    }

    // Eliminar el registro de la base de datos
    $stmt = $pdo->prepare("DELETE FROM archivos_pdf WHERE id = ?");
    if (!$stmt->execute([$id])) {
        throw new Exception("No se pudo eliminar el registro de la base de datos.");
    }

    // Llamar a logAuditAction después de eliminar el archivo y el registro
    logAuditAction('equipos', 'Delete', "Eliminación de PDF (ID: $id) del equipo $tagNumber");

    echo json_encode([
        'success' => true,
        'message' => 'Archivo eliminado correctamente.'
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
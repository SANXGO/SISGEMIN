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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $tagNumber = $_POST['tag_number'] ?? '';
    logAuditAction('Mantenimiento Correctivo', 'Update', "Agregar Mantenimiento Correctivo $tagNumber", $_POST);

    $descripcion = $_POST['descripcion'] ?? '';
    $idRepuesto = $_POST['repuestos'] ?? null;
    $fecha = $_POST['fecha'] ?? '';
    $materiales = $_POST['materiales'] ?? '';
    $responsable = $_POST['responsable'] ?? '';
    $tiempo = $_POST['tiempo'] ?? null;
    $mantenimiento = $_POST['mantenimiento_correctivo'] ?? '';
    
    try {
        // Obtener el nombre del repuesto si se seleccionó uno
        $nombreRepuesto = '';
        if ($idRepuesto) {
            $stmt = $pdo->prepare("SELECT real_part FROM repuestos WHERE id_repuestos = ?");
            $stmt->execute([$idRepuesto]);
            $repuesto = $stmt->fetch();
            $nombreRepuesto = $repuesto ? $repuesto['real_part'] : '';
        }
        
        // Insertar la intervención
        $query = "INSERT INTO intervencion 
                  (Tag_Number, descripcion, fecha, mantenimiento_correctivo, repuestos, materiales, tiempo, responsable)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($query);
        $success = $stmt->execute([
            $tagNumber,
            $descripcion,
            $fecha,
            $mantenimiento,
            $nombreRepuesto,
            $materiales,
            $tiempo,
            $responsable
        ]);
        
        if ($isAjax) {
            // Respuesta para AJAX
            header('Content-Type: application/json');
            logAuditAction('Mantenimiento Correctivo', 'Update', "Agregado exitosamente Mantenimiento Correctivo $tagNumber", $_POST);

            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Registro agregado correctamente' : 'Error al agregar el registro'
            ]);
            exit;
        } else {
            // Redirección tradicional
            header('Location: ../../index.php?tabla=Mantenimiento_correctivo&agregado=1');
            exit;
        }
    } catch (PDOException $e) {
        error_log("Error al agregar intervención: " . $e->getMessage());
        
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Error en la base de datos: ' . $e->getMessage()
            ]);
            exit;
        } else {
            header('Location: ../../index.php?tabla=Mantenimiento_correctivo&error=1');
            exit;
        }
    }
}

// Si no es POST o hay otro error
if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Método no permitido o datos incorrectos'
    ]);
} else {
    header('Location: ../../index.php?tabla=Mantenimiento_correctivo&error=1');
}
exit;
?>
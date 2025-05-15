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

// Verificar si se recibieron datos por POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener y sanitizar los datos del formulario


    $id_planta = isset($_POST['id_planta']) ? intval($_POST['id_planta']) : 0;
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
    logAuditAction('manual', 'Update', "Inicio de agregar categoria $descripcion", $_POST);

    
    // Validar los datos
    if ($id_planta <= 0 || empty($descripcion)) {
        echo json_encode(['success' => false, 'error' => 'Datos inválidos']);
        exit();
    }
    
    try {
        // Preparar la consulta SQL
        $stmt = $pdo->prepare("INSERT INTO manual (id_planta, descripcion) VALUES (?, ?)");
        
        // Ejecutar la consulta con los parámetros
        $stmt->execute([$id_planta, $descripcion]);
        

        logAuditAction('manual', 'Update', "registro exitoso al agregar la categoria $descripcion", $_POST);

        echo json_encode(['success' => true]);
        exit();
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error en la base de datos: ' . $e->getMessage()]);
        exit();
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}
?>
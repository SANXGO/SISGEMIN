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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_repuesto = $_POST['id_repuestos'] ?? '';
    logAuditAction('Repuesto', 'Create', "Inicio de edicion de repuesto", $_POST);

    $id_fabricante = $_POST['id_fabricante'] ?? '';
    $descripcion = trim($_POST['descripcion'] ?? '');
    $real_part = trim($_POST['real_part'] ?? '');
    $sectional_drawing = trim($_POST['sectional_drawing'] ?? '');

    // Validar datos
    $errors = [];
    
    if (empty($id_repuesto)) {
        $errors[] = 'ID de repuesto no proporcionado';
    }
    
    if (empty($id_fabricante)) {
        $errors[] = 'El fabricante es obligatorio';
    }
    
    if (empty($descripcion)) {
        $errors[] = 'La descripción es obligatoria';
    } elseif (strlen($descripcion) > 35) {
        $errors[] = 'La descripción no puede exceder los 35 caracteres';
    }
    
    if (empty($real_part)) {
        $errors[] = 'El Real Part es obligatorio';
    } elseif (strlen($real_part) > 35) {
        $errors[] = 'El Real Part no puede exceder los 35 caracteres';
    }
    
    if (empty($sectional_drawing)) {
        $errors[] = 'El Sectional Drawing es obligatorio';
    } elseif (strlen($sectional_drawing) > 35) {
        $errors[] = 'El Sectional Drawing no puede exceder los 35 caracteres';
    }
    
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'error' => implode('<br>', $errors)]);
        exit();
    }

    try {
        $stmt = $pdo->prepare("UPDATE repuestos 
                              SET id_fabricante = ?, descripcion = ?, real_part = ?, sectional_drawing = ?
                              WHERE id_repuestos = ?");
        $stmt->execute([$id_fabricante, $descripcion, $real_part, $sectional_drawing, $id_repuesto]);
        logAuditAction('Repuesto', 'Create', " edicion  exitosa de  repuesto", $_POST);

        echo json_encode(['success' => true]);
        exit();
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Error al actualizar repuesto: ' . $e->getMessage()]);
        exit();
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}
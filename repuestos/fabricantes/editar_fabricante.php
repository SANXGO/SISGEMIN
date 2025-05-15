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
    $id_fabricante = $_POST['id_fabricante'];
    logAuditAction('Fabricantes', 'Create', "edicion de fabricante", $_POST);

    $nombres = trim($_POST['nombres']);
    
    // Validar longitud del nombre
    if (strlen($nombres) > 40) {
        header('Location: ../../index.php?tabla=fabricantes&error=1&mensaje=El nombre no puede exceder los 40 caracteres');
        exit();
    }
    
    // Validar que no esté vacío
    if (empty($nombres)) {
        header('Location: ../../index.php?tabla=fabricantes&error=1&mensaje=El nombre del fabricante es requerido');
        exit();
    }
    
    try {
        // Verificar si el fabricante ya existe (excluyendo el actual)
        $stmtVerificar = $pdo->prepare("SELECT COUNT(*) FROM fabricantes WHERE nombres = ? AND id_fabricante != ?");
        $stmtVerificar->execute([$nombres, $id_fabricante]);
        $existe = $stmtVerificar->fetchColumn();
        
        if ($existe > 0) {
            header('Location: ../../index.php?tabla=fabricantes&error=1&mensaje=Este fabricante ya existe');
            exit();
        }
        
        // Actualizar el fabricante
        $stmt = $pdo->prepare("UPDATE fabricantes SET nombres = ? WHERE id_fabricante = ?");
        $stmt->execute([$nombres, $id_fabricante]);
        
        logAuditAction('Fabricantes', 'Update', "edicion exitosa de fabricante", $_POST);

        header('Location: ../../index.php?tabla=fabricantes&success=1&mensaje=Fabricante actualizado correctamente');
        exit();
    } catch (PDOException $e) {
        header('Location: ../../index.php?tabla=fabricantes&error=1&mensaje=Error al actualizar el fabricante');
        exit();
    }
}
?>
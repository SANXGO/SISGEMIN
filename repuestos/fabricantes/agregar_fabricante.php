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
    $nombres = trim($_POST['nombres']);
    logAuditAction('Fabricantes', 'Create', "creacion de fabricante", $_POST);

    
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
        // Verificar si el fabricante ya existe
        $stmtVerificar = $pdo->prepare("SELECT COUNT(*) FROM fabricantes WHERE nombres = ?");
        $stmtVerificar->execute([$nombres]);
        $existe = $stmtVerificar->fetchColumn();
        
        if ($existe > 0) {
            header('Location: ../../index.php?tabla=fabricantes&error=1&mensaje=Este fabricante ya existe');
            exit();
        }
        
        // Insertar el nuevo fabricante
        $stmt = $pdo->prepare("INSERT INTO fabricantes (nombres) VALUES (?)");
        $stmt->execute([$nombres]);
        
        logAuditAction('Fabricantes', 'Update', "creacion exitosa de fabricante", $_POST);

        header('Location: ../../index.php?tabla=fabricantes&success=1&mensaje=Fabricante agregado correctamente');
        exit();
    } catch (PDOException $e) {
        header('Location: ../../index.php?tabla=fabricantes&error=1&mensaje=Error al agregar el fabricante');
        exit();
    }
}
?>
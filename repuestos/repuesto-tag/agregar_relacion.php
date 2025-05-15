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
    $id_repuesto = $_POST['id_repuesto'] ?? '';
    
    $id_equipo = $_POST['id_equipo'] ?? '';
    logAuditAction('Repuesto-tag', 'Create', "Inicio de creación de nueva relacion", $_POST);


    if (!empty($id_repuesto) && !empty($id_equipo)) {
        try {
            $stmt = $pdo->prepare("SELECT id_puente FROM equipo_repuesto WHERE id_repuesto = ? AND id_equipo = ?");
            $stmt->execute([$id_repuesto, $id_equipo]);
            
            if ($stmt->rowCount() === 0) {
                $stmt = $pdo->prepare("INSERT INTO equipo_repuesto (id_repuesto, id_equipo) VALUES (?, ?)");
                $stmt->execute([$id_repuesto, $id_equipo]);
                
                if ($stmt->rowCount() > 0) {
                    logAuditAction('Relacion-tag', 'Create', "creación exitosa  de nueva relacion", $_POST);

                    header('Location: /uploads/index.php?tabla=repuestos_tag&success=1');
                    exit();
                }
            } else {
                header('Location: /uploads/index.php?tabla=repuestos_tag&error=2');
                exit();
            }
        } catch (PDOException $e) {
            error_log('Error al agregar relación: ' . $e->getMessage());
            header('Location: /uploads/index.php?tabla=repuestos_tag&error=1');
            exit();
        }
    } else {
        header('Location: /uploads/index.php?tabla=repuestos_tag&error=3');
        exit();
    }
}

header('Location: /uploads/index.php?tabla=repuestos_tag&error=4');
exit();
?>
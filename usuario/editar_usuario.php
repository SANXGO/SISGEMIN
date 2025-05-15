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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar que todos los campos requeridos estén presentes
    $required_fields = ['id_usuario', 'nombre', 'apellido', 'id_cargo', 'telefono', 'cedula', 'correo', 'pass', 'id_planta'];
    
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            die("El campo $field es requerido");
        }
    }

    // Asignar variables con filtrado
    $id_usuario = filter_var($_POST['id_usuario'], FILTER_VALIDATE_INT);
    logAuditAction('Usuario', 'Create', "Inicio de edicion de equipo", $_POST);

    $nombre = htmlspecialchars(trim($_POST['nombre']));
    $apellido = htmlspecialchars(trim($_POST['apellido']));
    $id_cargo = filter_var($_POST['id_cargo'], FILTER_VALIDATE_INT);
    $telefono = htmlspecialchars(trim($_POST['telefono']));
    $cedula = filter_var($_POST['cedula'], FILTER_VALIDATE_INT);
    $correo = filter_var($_POST['correo'], FILTER_VALIDATE_EMAIL);
    $pass = $_POST['pass']; // No filtrar para permitir caracteres especiales
    $id_planta = filter_var($_POST['id_planta'], FILTER_VALIDATE_INT);

    // Validaciones adicionales
    if (!$id_usuario || !$id_cargo || !$id_planta) {
        die("ID de usuario, cargo o planta inválido");
    }

    if (!preg_match('/^[0-9]{7,15}$/', $telefono)) {
        die("Formato de teléfono inválido");
    }

    if (!preg_match('/^[0-9]{6,12}$/', $cedula)) {
        die("Formato de cédula inválido");
    }

    if (!$correo) {
        die("Correo electrónico inválido");
    }

    try {
        // Actualizar el usuario
        $stmt = $pdo->prepare("UPDATE usuario SET 
            nombre = ?, 
            apellido = ?, 
            id_cargo = ?, 
            telefono = ?, 
            cedula = ?, 
            correo = ?, 
            pass = ?, 
            id_planta = ? 
            WHERE id_usuario = ?");
        
        $success = $stmt->execute([
            $nombre, 
            $apellido, 
            $id_cargo, 
            $telefono, 
            $cedula, 
            $correo, 
            $pass, 
            $id_planta, 
            $id_usuario
        ]);

        if ($success && $stmt->rowCount() > 0) {
            // Redirigir con mensaje de éxito
            logAuditAction('Usuario', 'Update', "edicion exitosa de equipo", $_POST);

            header("Location: ../usuario/detalles_usuario.php?id=".urlencode($id_usuario)."&success=1");
            exit;
        } else {
            // No se actualizó ningún registro
            header("Location: ../usuario/detalles_usuario.php?id=".urlencode($id_usuario)."&error=no_changes");
            exit;
        }
    } catch (PDOException $e) {
        // Error en la base de datos
        header("Location: ../usuario/detalles_usuario.php?id=".urlencode($id_usuario)."&error=db_error");
        exit;
    }
} else {
    // Método no permitido
    header("Location: ../index.php?tabla=usuario");
    exit;
}
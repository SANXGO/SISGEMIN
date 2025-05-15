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
    $idplanta = $_POST['id_planta'];
    $cedula = $_POST['cedula'];
    logAuditAction('Usuario', 'Create', "Inicio de creación de nuevo usuario", $_POST);

    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $id_cargo = $_POST['id_cargo'];
    $telefono = $_POST['telefono'];
    $correo = $_POST['correo'];
    $pass = $_POST['pass'];
    // Hash the password using SHA-256
    $pass = hash('sha256', $pass);

    if (empty($idplanta)) {
        die("Error: El campo 'id_planta' no puede estar vacío.");
    }

    $stmt = $pdo->prepare("INSERT INTO usuario (id_planta, cedula, nombre, apellido, id_cargo, telefono, correo, pass) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$idplanta, $cedula, $nombre, $apellido, $id_cargo, $telefono, $correo, $pass]);
    logAuditAction('Usuario', 'Create', "creacion exitosa de nuevo usuario", $_POST);

    header('Location: ../index.php?tabla=usuario&agregado=1');
    exit;
}
?>
<?php
session_start();
require_once __DIR__ . '/includes/conexion.php';
require 'vendor/autoload.php';

// Verificar token
$token = $_GET['token'] ?? '';
$error = '';
$success = '';

if (!empty($token)) {
    // Buscar usuario por token
    $sql = "SELECT id_usuario, expiracion_token FROM usuario 
            WHERE token_recuperacion = :token AND estado = 'activo'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['token' => $token]);
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();
        
        // Verificar expiración
        if (strtotime($user['expiracion_token']) > time()) {
            // Procesar formulario de nuevo password
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $newPassword = $_POST['password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';
                
                // Validaciones
                if (empty($newPassword) || strlen($newPassword) < 5) {
                    $error = "La contraseña debe tener al menos 5 caracteres";
                } elseif (!preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/[0-5]/', $newPassword)) {
                    $error = "La contraseña debe contener letras y números";
                } elseif ($newPassword !== $confirmPassword) {
                    $error = "Las contraseñas no coinciden";
                } else {
                    // Actualizar contraseña
                    $hashedPassword = hash('sha256', $newPassword);
                    
                    $updateSql = "UPDATE usuario SET pass = :pass, 
                                 token_recuperacion = NULL, 
                                 expiracion_token = NULL 
                                 WHERE id_usuario = :id";
                    $updateStmt = $pdo->prepare($updateSql);
                    $updateStmt->execute([
                        'pass' => $hashedPassword,
                        'id' => $user['id_usuario']
                    ]);
                    
                    $success = "Contraseña actualizada correctamente. Puede iniciar sesión ahora.";
                }
            }
        } else {
            $error = "El enlace de recuperación ha expirado";
        }
    } else {
        $error = "Token de recuperación inválido";
    }
} else {
    $error = "Token no proporcionado";
}

// Mostrar formulario de nuevo password si no hay error y no se ha completado
$showForm = empty($error) && empty($success);
?>

<!DOCTYPE html>
<html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="Sistema de Gestión de Mantenimiento de PEQUIVEN">
        <link rel="icon" href="favicon.png">
        <title>PEQUIVEN - Recuperar Contraseña</title>
        
        <!-- Favicon -->
        <link rel="icon" href="assets/img/favicon.png" type="image/png">
        
        <!-- Hojas de estilo -->
        <link href="assets/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="assets/css/all.min.css">
        <link rel="stylesheet" href="assets/css/login.css?v=<?= filemtime('assets/css/login.css') ?>">
<body class="login-body">
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="login-card shadow-lg">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <img src="pequi.png" alt="PEQUIVEN Logo" class="login-logo mb-3">
                    <h2 class="h4 mb-2">Restablecer Contraseña</h2>
                </div>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                    <div class="text-center mt-3">
                        <a href="login.php" class="btn btn-primary">Volver al login</a>
                    </div>
                <?php elseif (!empty($success)): ?>
                    <div class="alert alert-success"><?= $success ?></div>
                    <div class="text-center mt-3">
                        <a href="login.php" class="btn btn-primary">Ir al login</a>
                    </div>
                <?php elseif ($showForm): ?>
                    <form method="post">
                        <div class="mb-3">
                            <label for="password" class="form-label">Nueva contraseña</label>
                            <input type="password" class="form-control" id="password" name="password" required
                                   pattern="^(?=.*[A-Za-z])(?=.*\d).{5,}$"
                                   title="Mínimo 5 caracteres con letras y números">
                            <div class="form-text">Mínimo 5 caracteres con letras y números</div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmar contraseña</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-danger w-100">Guardar nueva contraseña</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
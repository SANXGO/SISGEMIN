<?php
session_start();
require_once __DIR__ . '/includes/conexion.php';

// Incluye PHPMailer correctamente
require_once __DIR__ . '/includes/PHPMailer.php';
require_once __DIR__ . '/includes/SMTP.php';
require_once __DIR__ . '/includes/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Configuración de seguridad
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

$error = '';
$success = '';

// Reemplaza el bloque de envío de correo con esto:
if ($user) {
    // Configurar PHPMailer
    $mail = new PHPMailer(true);
    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host = 'localhost'; // Cambia esto por tu servidor SMTP
        $mail->SMTPAuth = true;
        $mail->Username = 'merequetengue6970@gmail.com'; // Tu usuario SMTP
        $mail->Password = ''; // Tu contraseña SMTP
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // O ENCRYPTION_SMTPS para SSL
        $mail->Port = 25; // Puerto SMTP (587 para TLS, 465 para SSL)

        // Resto del código...
        // Remitente y destinatario
        $mail->setFrom('no-reply@pequiven.com', 'PEQUIVEN');
        $mail->addAddress($user['merequetengue6970@gmail.com'], $user['sam']);

        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = "Recuperación de contraseña - PEQUIVEN";
        $mail->Body = "Hola " . htmlspecialchars($user['nombre']) . ",<br><br>
            Hemos recibido una solicitud para restablecer tu contraseña.<br><br>
            Por favor haz clic en el siguiente enlace para restablecer tu contraseña:<br>
            <a href=\"$resetLink\">$resetLink</a><br><br>
            Este enlace expirará en 1 hora.<br><br>
            Si no solicitaste este cambio, puedes ignorar este mensaje.<br><br>
            Atentamente,<br>Equipo de PEQUIVEN";

        $mail->send();
        $success = "Se ha enviado un correo con instrucciones para restablecer tu contraseña.";
    } catch (Exception $e) {
        $error = "Hubo un error al enviar el correo. Por favor intenta nuevamente. Error: " . $mail->ErrorInfo;
    }
}

?>

<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Recuperación de contraseña - PEQUIVEN">
    <link rel="icon" href="favicon.png">
    <title>PEQUIVEN - Recuperar Contraseña</title>
    
    <!-- Favicon -->
    <link rel="icon" href="assets/img/favicon.png" type="image/png">
    
    <!-- Hojas de estilo -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/all.min.css">
    <link rel="stylesheet" href="assets/css/login.css?v=<?= filemtime('assets/css/login.css') ?>">
</head>
<body class="login-body">
    <!-- Botón de cambio de tema -->
    <button class="btn btn-sm btn-theme-toggle position-fixed top-0 end-0 m-3" id="themeToggle" aria-label="Cambiar tema">
        <i class="fas fa-moon"></i>
    </button>
    
    <div class="container d-flex justify-content-center align-items-center min-vh-100">
        <div class="login-card shadow-lg">
            <div class="card-body p-5">
                <!-- Encabezado -->
                <div class="text-center mb-4">
                    <img src="pequi.png" alt="PEQUIVEN Logo" class="login-logo mb-3" width="120" height="auto" loading="eager">
                    <h1 class="h3 mb-2 fw-bold">Recuperar Contraseña</h1>
                    <p class="text-muted">Ingresa tu número de cédula para recuperar tu contraseña</p>
                </div>
                
                <!-- Alertas -->
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <div><?= htmlspecialchars($error) ?></div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-check-circle me-2"></i>
                            <div><?= htmlspecialchars($success) ?></div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                <?php endif; ?>
                
                <!-- Formulario de Recuperación -->
                <form id="forgotPasswordForm" action="forgot_password.php" method="post" novalidate>
                    <!-- Campo Cédula -->
                    <div class="mb-4">
                        <label for="cedula" class="form-label">Cédula</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                            <input type="text" class="form-control" id="cedula" name="cedula" 
                                   placeholder="Ingrese su cédula" required
                                   pattern="[0-9]+" 
                                   title="La cédula solo debe contener números">
                        </div>
                        <div class="form-text">Ingrese el número de cédula asociado a su cuenta</div>
                    </div>
                    
                    <!-- Botón de Submit -->
                    <button type="submit" class="btn btn-danger w-100 py-2 mb-3">
                        <i class="fas fa-paper-plane me-2"></i> ENVIAR ENLACE DE RECUPERACIÓN
                    </button>
                    
                    <!-- Volver al login -->
                    <div class="text-center mt-3">
                        <a href="login.php" class="text-decoration-none">
                            <i class="fas fa-arrow-left me-1"></i> Volver al inicio de sesión
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- Pie de página -->
            <div class="card-footer text-center py-3">
                <small class="text-muted">
                    &copy; <?= date('Y') ?> PEQUIVEN - Todos los derechos reservados
                </small>
            </div>
        </div>
    </div>
    
    <!-- Scripts -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestión del tema oscuro/claro
        document.addEventListener('DOMContentLoaded', function() {
            const themeToggle = document.getElementById('themeToggle');
            const html = document.documentElement;
            
            // Cargar preferencia del usuario
            const savedTheme = localStorage.getItem('theme') || 'light';
            html.setAttribute('data-bs-theme', savedTheme);
            updateThemeIcon(savedTheme);
            
            // Manejar el cambio de tema
            themeToggle.addEventListener('click', () => {
                const currentTheme = html.getAttribute('data-bs-theme');
                const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
                
                html.setAttribute('data-bs-theme', newTheme);
                localStorage.setItem('theme', newTheme);
                updateThemeIcon(newTheme);
            });
            
            function updateThemeIcon(theme) {
                themeToggle.innerHTML = theme === 'dark' 
                    ? '<i class="fas fa-sun"></i>' 
                    : '<i class="fas fa-moon"></i>';
            }
        });
    </script>
</body>
</html>
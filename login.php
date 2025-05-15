<?php
session_start();

// Configuración de seguridad
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

// Incluir la conexión a la base de datos
require_once __DIR__ . '/includes/conexion.php';

// Constantes de configuración
define('MAX_LOGIN_ATTEMPTS', 3);
define('LOGIN_TIMEOUT', 60); // 1 minuto en segundos

// Inicializar variables de sesión para control de intentos
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = 0;
}

/**
 * Valida los datos de entrada del login
 */
function validateLoginInput($username, $password) {
    $errors = [];
    
    // Validar cédula (solo números)
    if (!preg_match('/^[0-9]+$/', $username)) {
        $errors[] = "La cédula solo debe contener números.";
    }
    
    // Validar contraseña (letras y números)
    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errors[] = "La contraseña debe contener letras y números.";
    }
    
    return $errors;
}

/**
 * Autentica al usuario contra la base de datos
 */
function authenticateUser($pdo, $username, $password) {
    $sql = "SELECT id_usuario, cedula, nombre, apellido, id_cargo, pass FROM usuario WHERE cedula = :cedula";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['cedula' => $username]);
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();
        
        // Verificar contraseña con SHA-256
        $hashedPassword = hash('sha256', $password);
        if ($user['pass'] === $hashedPassword) {
            return $user;
        }
    }
    
    return false;
}

/**
 * Configura la sesión del usuario después de login exitoso
 */
function setupUserSession($user) {
    // Regenerar ID de sesión para prevenir fijación de sesión
    session_regenerate_id(true);

    $_SESSION['usuario'] = $user['cedula'];
    $_SESSION['id_usuario'] = $user['id_usuario'];
    $_SESSION['nombre'] = $user['nombre'];
    $_SESSION['apellido'] = $user['apellido'];
    $_SESSION['id_cargo'] = $user['id_cargo'];
    $_SESSION['login_attempts'] = 0;
}

/**
 * Redirección segura
 */
function redirect($url) {
    header("Location: $url");
    exit();
}

/**
 * Registra intentos de login (para posible auditoría)
 */
function logLoginAttempt($username, $success) {
    // En una implementación real, esto debería guardarse en una tabla de logs
    error_log("Login attempt - User: $username - Success: " . ($success ? 'Yes' : 'No'));
}

/**
 * Formatea segundos a minutos:segundos
 */
function formatSeconds($seconds) {
    $minutes = floor($seconds / 60);
    $remainingSeconds = $seconds % 60;
    return sprintf("%d:%02d", $minutes, $remainingSeconds);
}

/**
 * Procesar recuperación de contraseña
 */
function processPasswordRecovery($pdo, $email) {
    // Buscar usuario por email
    $sql = "SELECT id_usuario, nombre, apellido FROM usuario WHERE correo = :email AND estado = 'activo'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch();
        
        // Generar token seguro
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Actualizar base de datos
        $updateSql = "UPDATE usuario SET token_recuperacion = :token, 
                     expiracion_token = :expiry WHERE id_usuario = :id";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([
            'token' => $token,
            'expiry' => $expiry,
            'id' => $user['id_usuario']
        ]);
        
        // Enviar email de recuperación
        require 'vendor/autoload.php';
        $config = include 'config_smtp.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Configuración SMTP
            $mail->isSMTP();
            $mail->Host = $config['smtp']['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $config['smtp']['username'];
            $mail->Password = $config['smtp']['password'];
            $mail->SMTPSecure = $config['smtp']['encryption'];
            $mail->Port = $config['smtp']['port'];
            
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            
            // Configuración del email
            $mail->setFrom($config['smtp']['from_email'], $config['smtp']['from_name']);
            $mail->addAddress($email, $user['nombre'] . ' ' . $user['apellido']);
            $mail->Subject = 'Recuperación de contraseña - PEQUIVEN';
            
            // Cuerpo del email
            $resetLink = $config['recovery']['base_url'] . "/reset_password.php?token=$token";
            $mail->isHTML(true);
            $mail->Body = "
                <h2>Recuperación de contraseña</h2>
                <p>Hola {$user['nombre']},</p>
                <p>Hemos recibido una solicitud para restablecer tu contraseña.</p>
                <p>Por favor haz clic en el siguiente enlace para continuar:</p>
                <p><a href='$resetLink'>$resetLink</a></p>
                <p>Si no solicitaste este cambio, por favor ignora este mensaje.</p>
                <p>El enlace expirará en 1 hora.</p>
            ";
            
            $mail->send();
            return "Se ha enviado un enlace de recuperación a tu email";
        } catch (Exception $e) {
            error_log("Error al enviar email: " . $mail->ErrorInfo);
            return "Error al enviar el email de recuperación. Por favor intente más tarde.";
        }
    } else {
        return "No se encontró una cuenta activa con ese email";
    }
}

// Procesamiento del formulario de recuperación
if (isset($_GET['action']) && $_GET['action'] === 'forgot') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email'] ?? '');
        
        // Validar email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $recoveryError = "Por favor ingrese un email válido";
        } else {
            $recoveryResult = processPasswordRecovery($pdo, $email);
            if (strpos($recoveryResult, 'Se ha enviado') !== false) {
                $recoverySuccess = $recoveryResult;
            } else {
                $recoveryError = $recoveryResult;
            }
        }
    }
    
    // Mostrar formulario de recuperación
    ?>
    <!DOCTYPE html>
    <html lang="es" data-bs-theme="light">
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
                        <h1 class="h3 mb-2 fw-bold">PEQUIVEN</h1>
                        <p class="text-muted">Recuperar Contraseña</p>
                    </div>
                    
                    <!-- Alertas -->
                    <?php if (isset($recoveryError)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <div><?= $recoveryError ?></div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($recoverySuccess)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle me-2"></i>
                                <div><?= $recoverySuccess ?></div>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                        </div>
                        <div class="text-center mt-3">
                            <a href="login.php" class="btn btn-primary">Volver al login</a>
                        </div>
                    <?php else: ?>
                        <!-- Formulario de Recuperación -->
                        <form id="recoveryForm" action="login.php?action=forgot" method="post" novalidate>
                            <!-- Campo Email -->
                            <div class="mb-3">
                                <label for="email" class="form-label">Email registrado</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           placeholder="Ingrese su email" required>
                                </div>
                            </div>
                            
                            <!-- Botón de Submit -->
                            <button type="submit" class="btn btn-danger w-100 py-2 mb-3">
                                <i class="fas fa-paper-plane me-2"></i> ENVIAR ENLACE
                            </button>
                        </form>
                        
                        <div class="text-center">
                            <a href="login.php" class="text-decoration-none">Volver al login</a>
                        </div>
                    <?php endif; ?>
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
    <?php
    exit();
}

// Procesamiento del formulario de login normal
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar timeout por intentos fallidos
    if ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS && 
        (time() - $_SESSION['last_attempt_time']) < LOGIN_TIMEOUT) {
        $error = "Demasiados intentos fallidos. Por favor espere " . 
                 formatSeconds(LOGIN_TIMEOUT - (time() - $_SESSION['last_attempt_time'])) . 
                 " antes de intentar nuevamente.";
    } else {
        // Sanitizar y validar entradas
        $username = trim($_POST['usuario'] ?? '');
        $password = $_POST['pass'] ?? '';
        
        // Validaciones
        $validationErrors = validateLoginInput($username, $password);
        
        if (!empty($validationErrors)) {
            $error = implode("<br>", $validationErrors);
        } else {
            // Autenticación del usuario
            $user = authenticateUser($pdo, $username, $password);
            
            if ($user) {
                // Login exitoso
                setupUserSession($user);
                logLoginAttempt($username, true);
                
                // Redirección segura
                redirect('index.php');
            } else {
                // Login fallido
                $error = "Credenciales incorrectas.";
                if ($_SESSION['login_attempts'] < MAX_LOGIN_ATTEMPTS) {
                    $_SESSION['login_attempts']++;
                    if ($_SESSION['login_attempts'] === MAX_LOGIN_ATTEMPTS) {
                        $_SESSION['last_attempt_time'] = time();
                    }
                }
                logLoginAttempt($username, false);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de Gestión de Mantenimiento de PEQUIVEN">
    <link rel="icon" href="favicon.png">
    <title>PEQUIVEN - Iniciar Sesión</title>
    
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
                    <h1 class="h3 mb-2 fw-bold">PEQUIVEN</h1>
                    <p class="text-muted">Sistema de Gestión para Mantenimientos e </br>Instrumentación y Control</p>
                    
                </div>
                
                <!-- Alertas -->
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <div><?= $error ?></div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS && (time() - $_SESSION['last_attempt_time']) < LOGIN_TIMEOUT): ?>
                    <div class="alert alert-warning d-flex align-items-center">
                        <i class="fas fa-clock me-2"></i>
                        <div>
                            Demasiados intentos fallidos. Podrá intentar nuevamente en 
                            <span id="countdown"><?= formatSeconds(LOGIN_TIMEOUT - (time() - $_SESSION['last_attempt_time'])) ?></span>.
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Formulario de Login -->
                <form id="loginForm" action="login.php" method="post" novalidate>
                    <!-- Campo Cédula -->
                    <div class="mb-3">
                        <label for="usuario" class="form-label">Cédula</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                            <input type="text" class="form-control" id="usuario" name="usuario" 
                                   placeholder="Ingrese su cédula" required
                                   pattern="[0-9]+" 
                                   title="La cédula solo debe contener números"
                                   <?= ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS && (time() - $_SESSION['last_attempt_time']) < LOGIN_TIMEOUT) ? 'disabled' : '' ?>>
                        </div>
                        <div class="form-text">Solo números</div>
                    </div>
                    
                    <!-- Campo Contraseña -->
                    <div class="mb-4">
                        <label for="pass" class="form-label">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="pass" name="pass" 
                                   placeholder="Ingrese su contraseña" required
                                   pattern="^(?=.*[A-Za-z])(?=.*\d).+$" 
                                   title="La contraseña debe contener letras y números"
                                   <?= ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS && (time() - $_SESSION['last_attempt_time']) < LOGIN_TIMEOUT) ? 'disabled' : '' ?>>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">Al menos una letra y un número</div>
                    </div>
                    
                    <!-- Enlace de recuperación -->
                    <div class="text-center mb-3">
                        <a href="login.php?action=forgot" class="text-decoration-none">¿Olvidaste tu contraseña?</a>
                    </div>
                    
                    <!-- Botón de Submit -->
                    <button type="submit" class="btn btn-danger w-100 py-2 mb-3" 
                            id="submitBtn"
                            <?= ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS && (time() - $_SESSION['last_attempt_time']) < LOGIN_TIMEOUT) ? 'disabled' : '' ?>>
                        <i class="fas fa-sign-in-alt me-2"></i> INICIAR SESIÓN
                    </button>
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
            
            // Mostrar/ocultar contraseña
            document.getElementById('togglePassword').addEventListener('click', function() {
                const passwordInput = document.getElementById('pass');
                const icon = this.querySelector('i');
                
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.replace('fa-eye', 'fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.replace('fa-eye-slash', 'fa-eye');
                }
            });
            
            // Contador regresivo para intentos fallidos
            <?php if ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS && (time() - $_SESSION['last_attempt_time']) < LOGIN_TIMEOUT): ?>
                let secondsLeft = <?= LOGIN_TIMEOUT - (time() - $_SESSION['last_attempt_time']) ?>;
                const countdownElement = document.getElementById('countdown');
                const submitBtn = document.getElementById('submitBtn');
                const inputs = document.querySelectorAll('#loginForm input');
                
                const timerInterval = setInterval(() => {
                    secondsLeft--;
                    
                    if (secondsLeft <= 0) {
                        clearInterval(timerInterval);
                        submitBtn.disabled = false;
                        inputs.forEach(input => input.disabled = false);
                        document.querySelector('.alert-warning').style.display = 'none';
                        location.reload();
                    } else {
                        countdownElement.textContent = formatTime(secondsLeft);
                    }
                }, 1000);
                
                function formatTime(seconds) {
                    const mins = Math.floor(seconds / 60);
                    const secs = seconds % 60;
                    return `${mins}:${secs < 10 ? '0' : ''}${secs}`;
                }
            <?php endif; ?>
        });
    </script>
</body>
</html>
<style>
    a{
        color: #b71513 !important;
    }
</style>
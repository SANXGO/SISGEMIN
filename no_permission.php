<?php
// no_permission.php
$page_title = "Acceso Denegado";
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="favicon.png">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #343a40;
        }

        .denied-container {
            text-align: center;
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            width: 90%;
        }

        h1 {
            color: #dc3545;
            margin-bottom: 20px;
        }

        .denied-icon {
            font-size: 80px;
            color: #dc3545;
            margin-bottom: 20px;
        }

        p {
            font-size: 18px;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #b71513;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #721c24;
        }

        .error-details {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            color: #721c24;
            font-size: 14px;
            text-align: left;
        }
    </style>
</head>

<body>
    <div class="denied-container">
        <div class="denied-icon">🚫</div>
        <h1>ACCESO DENEGADO</h1>
        <p>No tienes permisos suficientes para acceder a esta página.</p>
        <p>Por favor, contacta al administrador si crees que esto es un error.</p>

        <?php
        /*
        // Mostrar detalles adicionales si está en modo desarrollo
        if (isset($_SESSION['user_role'])) {
            echo '<div class="error-details">';
            echo '<strong>Información adicional:</strong><br>';
            echo 'Usuario: ' . htmlspecialchars($_SESSION['username'] ?? 'No identificado') . '<br>';
            echo 'Rol: ' . htmlspecialchars($_SESSION['user_role'] ?? 'No asignado') . '<br>';
            echo 'IP: ' . htmlspecialchars($_SERVER['REMOTE_ADDR']) . '<br>';
            echo 'Hora: ' . date('Y-m-d H:i:s');
            echo '</div>';
        }
        */
        ?>

        <a href="index.php" class="btn">Volver al inicio</a>
    </div>
</body>

</html>
<?php
require_once __DIR__ . '/../includes/conexion.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación del usuario
if (!isset($_SESSION['id_usuario']) || empty($_SESSION['id_usuario'])) {
    header('HTTP/1.0 401 Unauthorized');
    echo json_encode(['error' => 'Usuario no autenticado']);
    exit;
}

$id_usuario = $_SESSION['id_usuario'];

try {
    // Obtener información del usuario actual con su planta
    $stmt = $pdo->prepare("
        SELECT u.nombre, u.id_planta, p.nombres as nombre_planta 
        FROM usuario u
        JOIN planta p ON u.id_planta = p.id_planta
        WHERE u.id_usuario = ?
    ");
    $stmt->execute([$id_usuario]);
    $usuario_actual = $stmt->fetch();

    if (!$usuario_actual) {
        header('HTTP/1.0 404 Not Found');
        echo json_encode(['error' => 'Usuario no encontrado en la base de datos']);
        exit;
    }

    // Obtener parámetros de búsqueda con sanitización básica
    $orden = filter_input(INPUT_GET, 'orden', FILTER_SANITIZE_STRING) ?? '';
    $fecha = filter_input(INPUT_GET, 'fecha', FILTER_SANITIZE_STRING) ?? '';
    $general = filter_input(INPUT_GET, 'general', FILTER_SANITIZE_STRING) ?? '';

    // Construir la consulta con filtros
    $query = "
        SELECT a.*, u.nombre as nombre_usuario 
        FROM actividades a
        JOIN usuario u ON a.id_usuario = u.id_usuario
        WHERE a.planta = :planta
    ";

    $params = [':planta' => $usuario_actual['nombre_planta']];

    if (!empty($orden)) {
        $query .= " AND a.orden LIKE :orden";
        $params[':orden'] = "%$orden%";
    }

    if (!empty($fecha)) {
        // Validar formato de fecha
        if (DateTime::createFromFormat('Y-m-d', $fecha) !== false) {
            $query .= " AND a.fecha = :fecha";
            $params[':fecha'] = $fecha;
        }
    }

    if (!empty($general)) {
        $query .= " AND (
            a.orden LIKE :general1 OR 
            a.num_permiso LIKE :general2 OR 
            a.planta LIKE :general3 OR 
            a.ubicacion LIKE :general4 OR 
            a.tag_number LIKE :general5 OR 
            a.actividad LIKE :general6 OR 
            a.especialistas LIKE :general7 OR 
            a.recurso_apoyo LIKE :general8 OR 
            u.nombre LIKE :general9
        )";
        for ($i = 1; $i <= 9; $i++) {
            $params[":general$i"] = "%$general%";
        }
    }

    $query .= " ORDER BY a.id_actividad DESC";

    $stmt = $pdo->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->execute();
    $actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mostrar resultados
    foreach ($actividades as $actividad): ?>
        <tr data-id="<?= htmlspecialchars($actividad['id_actividad']) ?>" style="cursor: pointer;">
            <td>
                <a href="planificacion/detalles_actividades.php?id=<?= htmlspecialchars($actividad['id_actividad']) ?>" class="text-danger">
                    <?= htmlspecialchars($actividad['id_actividad']) ?>
                </a>
            </td>
            <td><?= htmlspecialchars($actividad['orden']) ?></td>
            <td><?= htmlspecialchars($actividad['planta']) ?></td>
            <td><?= htmlspecialchars($actividad['tag_number']) ?></td>
            <td class="text-truncate-cell" 
                data-bs-toggle="tooltip" data-bs-placement="top" 
                title="<?= htmlspecialchars($actividad['actividad']) ?>">
                <?= htmlspecialchars($actividad['actividad']) ?>
            </td>
            <td><?= htmlspecialchars($actividad['fecha']) ?></td>
        </tr>
    <?php endforeach;

} catch (PDOException $e) {
    header('HTTP/1.0 500 Internal Server Error');
    echo json_encode(['error' => 'Error en la base de datos: ' . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    header('HTTP/1.0 400 Bad Request');
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
?>
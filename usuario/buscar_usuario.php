<?php
require_once __DIR__ . '/../includes/conexion.php';

$search = isset($_GET['search']) ? $_GET['search'] : '';
$limit = isset($_GET['limit']) ? $_GET['limit'] : 10;

// Consulta base con JOIN para obtener el nombre del cargo directamente
$sql = "SELECT u.*, c.nombre_cargo
        FROM usuario u
        LEFT JOIN cargo c ON u.id_cargo = c.id_cargo
        WHERE u.estado = 'activo'";

// Agregar la condición de búsqueda si hay un término
if (!empty($search)) {
    $sql .= " AND (u.cedula LIKE ? OR u.nombre LIKE ? OR u.apellido LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
} else {
    $params = []; // No hay parámetros de búsqueda si el campo está vacío
}

// Manejar el límite
if ($limit !== 'all') {
    $sql .= " LIMIT ?";
    $params[] = (int)$limit; // Agregar el límite como parámetro
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$usuarios = $stmt->fetchAll();

// Obtener plantas (necesario para mapear los nombres)
$stmt_plantas = $pdo->query("SELECT id_planta, nombres FROM planta");
$plantasMap = array_column($stmt_plantas->fetchAll(), 'nombres', 'id_planta');

// Generar HTML de resultados
foreach ($usuarios as $usuario): ?>
    <tr data-id="<?= $usuario['id_usuario'] ?>">
        <td>
            <a href="usuario/detalles_usuario.php?id=<?= $usuario['id_usuario'] ?>" class="text-danger">
                <?= htmlspecialchars($usuario['id_usuario']) ?>
            </a>
        </td>
        <td><?= htmlspecialchars($usuario['nombre']) ?></td>
        <td><?= htmlspecialchars($usuario['apellido']) ?></td>
        <td><?= htmlspecialchars($usuario['nombre_cargo'] ?? 'Sin cargo') ?></td>
        <td><?= htmlspecialchars($usuario['cedula']) ?></td>
        <td><?= isset($plantasMap[$usuario['id_planta']]) ? htmlspecialchars($plantasMap[$usuario['id_planta']]) : 'Planta no encontrada' ?></td>
        <td><?= htmlspecialchars($usuario['telefono']) ?></td>
    </tr>
<?php endforeach; ?>
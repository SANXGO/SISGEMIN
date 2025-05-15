<?php
require_once __DIR__ . '/../includes/conexion.php';

$search = isset($_GET['search']) ? $_GET['search'] : '';

// Consulta para buscar ubicaciones por Tag Number
$stmt = $pdo->prepare("
    SELECT u.id_ubicacion, p.nombres AS nombre_planta, u.descripcion 
    FROM ubicacion u
    JOIN planta p ON u.id_planta = p.id_planta
    WHERE u.id_ubicacion LIKE ?
");
$stmt->execute(["%$search%"]); // Usamos % para buscar coincidencias parciales
$equipos = $stmt->fetchAll();

foreach ($equipos as $equipo): ?>
    <tr>
        <td><?= htmlspecialchars($equipo['id_ubicacion']) ?></td>
        <td><?= htmlspecialchars($equipo['nombre_planta']) ?></td>
        <td><?= htmlspecialchars($equipo['descripcion']) ?></td>
    </tr>
<?php endforeach;
?>
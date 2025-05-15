<?php
require_once __DIR__ . '/../../includes/conexion.php';

$limit = $_GET['limit'] ?? 10;
$searchTerm = $_GET['searchTerm'] ?? '';
$searchType = $_GET['searchType'] ?? 'tag';
$fecha = $_GET['fecha'] ?? '';
$fecha_inicio = $_GET['fecha_inicio'] ?? '';
$fecha_fin = $_GET['fecha_fin'] ?? '';

$query = "SELECT i.*, u.descripcion as ubicacion 
          FROM intervencion i
          JOIN equipos e ON i.Tag_Number = e.Tag_Number
          JOIN ubicacion u ON e.id_ubicacion = u.id_ubicacion
          WHERE 1=1";
$params = [];

if (!empty($searchTerm)) {
    if ($searchType === 'tag') {
        $query .= " AND i.Tag_Number LIKE ?";
        $params[] = "%$searchTerm%";
    } elseif ($searchType === 'ubicacion') {
        $query .= " AND u.descripcion LIKE ?";
        $params[] = "%$searchTerm%";
    }
}

if (!empty($fecha)) {
    $query .= " AND i.fecha = ?";
    $params[] = $fecha;
} elseif (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $query .= " AND i.fecha BETWEEN ? AND ?";
    $params[] = $fecha_inicio;
    $params[] = $fecha_fin;
}

if ($limit !== 'all') {
    $query .= " LIMIT ?";
    $params[] = (int)$limit;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$intervenciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($intervenciones as $row): ?>
<tr data-id="<?= $row['id_inter'] ?>">
    <td><?= htmlspecialchars($row['Tag_Number']) ?></td>
    <td><?= htmlspecialchars($row['ubicacion']) ?></td>
    <td class="text-truncate" style="max-width: 300px;" 
        data-bs-toggle="tooltip" data-bs-placement="top" 
        title="<?= htmlspecialchars($row['descripcion']) ?>">
        <?= htmlspecialchars($row['descripcion']) ?>
    </td>
    <td><?= htmlspecialchars($row['fecha']) ?></td>
    <td><?= htmlspecialchars($row['responsable']) ?></td>
</tr>
<?php endforeach; ?>
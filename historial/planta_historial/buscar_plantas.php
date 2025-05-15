<?php
require_once __DIR__ . '/../../includes/conexion.php';

header('Content-Type: text/html; charset=utf-8');

$search = $_GET['search'] ?? '';

$sql = "SELECT id_planta, nombres, estado FROM planta";
$params = [];

if (!empty($search)) {
    $sql .= " WHERE LOWER(nombres) LIKE ?";
    $params[] = '%' . strtolower($search) . '%';
}

$sql .= " ORDER BY nombres";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$plantas = $stmt->fetchAll();

if (empty($plantas)) {
    echo '<tr><td colspan="4" class="text-center">No se encontraron plantas</td></tr>';
    exit;
}

foreach ($plantas as $planta) {
    $badgeClass = $planta['estado'] === 'activo' ? 'bg-success' : 'bg-secondary';
    echo '<tr id="planta-' . htmlspecialchars($planta['id_planta']) . '">';
    echo '<td>' . htmlspecialchars($planta['id_planta']) . '</td>';
    echo '<td>' . htmlspecialchars($planta['nombres']) . '</td>';
    echo '<td><span class="badge ' . $badgeClass . '">' . ucfirst($planta['estado']) . '</span></td>';
    echo '<td>';
    
    if ($planta['estado'] === 'inactivo') {
        echo '<button class="btn btn-sm btn-success" ';
        echo 'onclick="confirmarCambioEstado(' . $planta['id_planta'] . ', \'habilitar\', \'' . htmlspecialchars(addslashes($planta['nombres'])) . '\')">';
        echo 'Habilitar</button>';
    } else {
        echo '<button class="btn btn-sm btn-warning" ';
        echo 'onclick="confirmarCambioEstado(' . $planta['id_planta'] . ', \'deshabilitar\', \'' . htmlspecialchars(addslashes($planta['nombres'])) . '\')">';
        echo 'Deshabilitar</button>';
    }
    
    echo '</td>';
    echo '</tr>';
}
?>
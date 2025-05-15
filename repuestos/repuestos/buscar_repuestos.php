<?php
require_once __DIR__ . '/../../includes/conexion.php';

header('Content-Type: text/html');

$search = $_GET['search'] ?? '';

if (empty($search)) {
    die('Término de búsqueda no proporcionado');
}

try {
    $stmt = $pdo->prepare("
        SELECT r.id_repuestos, f.nombres as fabricante, r.descripcion, r.real_part, r.sectional_drawing
        FROM repuestos r
        JOIN fabricantes f ON r.id_fabricante = f.id_fabricante
        WHERE  r.estado='activo'AND r.descripcion LIKE ?
        ORDER BY r.id_repuestos
    ");
    $stmt->execute(["%$search%"]);
    $repuestos = $stmt->fetchAll();
    
    foreach ($repuestos as $repuesto) {
        echo '<tr onclick="seleccionarRepuesto(\'' . $repuesto['id_repuestos'] . '\')" style="cursor: pointer;">';
        echo '<td>' . htmlspecialchars($repuesto['id_repuestos']) . '</td>';
        echo '<td>' . htmlspecialchars($repuesto['fabricante']) . '</td>';
        echo '<td>' . htmlspecialchars($repuesto['descripcion']) . '</td>';
        echo '<td>' . htmlspecialchars($repuesto['real_part']) . '</td>';
        echo '<td>' . htmlspecialchars($repuesto['sectional_drawing']) . '</td>';
        echo '</tr>';
    }
} catch (PDOException $e) {
    echo '<tr><td colspan="5">Error al buscar repuestos: ' . $e->getMessage() . '</td></tr>';
}
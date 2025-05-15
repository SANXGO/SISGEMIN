<?php
require_once __DIR__ . '/../includes/conexion.php';
require_once __DIR__ . '/../includes/audit.php';

$search = isset($_GET['search']) ? $_GET['search'] : '';
$id_planta = isset($_GET['id_planta']) ? (int)$_GET['id_planta'] : 0;

try {
    // Registrar la acción de búsqueda
    logAuditAction('ubicaciones', 'Search', "Búsqueda realizada: " . $search);

    $stmt = $pdo->prepare("
        SELECT u.*, p.NOMBRES as nombre_planta 
        FROM ubicacion u
        JOIN planta p ON u.ID_PLANTA = p.ID_PLANTA
        WHERE u.estado = 'activo' 
          AND p.estado = 'activo'
          AND u.id_planta = ?
          AND (u.id_ubicacion LIKE ? OR u.nombre LIKE ?)
    ");
    $stmt->execute([$id_planta, "%$search%", "%$search%"]);
    $ubicaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $html = '';
    if (!empty($ubicaciones)) {
        foreach ($ubicaciones as $ubicacion) {
            $html .= '<tr onclick="seleccionarUbicacion(\'' . htmlspecialchars($ubicacion['id_ubicacion']) . '\')" style="cursor: pointer;">';
            $html .= '<td>' . htmlspecialchars($ubicacion['id_ubicacion']) . '</td>';
            $html .= '<td>' . htmlspecialchars($ubicacion['nombre']) . '</td>';
            $html .= '<td>' . htmlspecialchars($ubicacion['nombre_planta']) . '</td>';
            $html .= '</tr>';
        }
    } else {
        $html .= '<tr><td colspan="3">No se encontraron resultados.</td></tr>';
    }

    echo $html;
} catch (PDOException $e) {
    die("Error en la consulta: " . $e->getMessage());
}
?>
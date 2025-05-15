<?php
require_once __DIR__ . '/../includes/conexion.php';

$search = isset($_GET['search']) ? $_GET['search'] : '';
$id_planta = isset($_GET['id_planta']) ? (int)$_GET['id_planta'] : 0;

try {
    $stmt = $pdo->prepare("
        SELECT e.* 
        FROM equipos e
        JOIN ubicacion u ON e.id_ubicacion = u.id_ubicacion
        JOIN planta p ON u.id_planta = p.id_planta
        WHERE p.estado = 'activo' 
          AND u.estado = 'activo'
          AND e.estado = 'activo'
          AND u.id_planta = ?
          AND (u.id_ubicacion LIKE ? OR u.descripcion LIKE ?)
        ORDER BY e.Tag_Number
    ");
    $stmt->execute([$id_planta, "%$search%", "%$search%"]);
    $equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $html = '';
    if (!empty($equipos)) {
        foreach ($equipos as $equipo) {
            $html .= '<tr onclick="seleccionarEquipo(\'' . htmlspecialchars($equipo['Tag_Number']) . '\')" style="cursor: pointer;">';
            $html .= '<td>';
            $html .= '<a href="equipo/detalle_equipo.php?tag_number=' . htmlspecialchars($equipo['Tag_Number']) . '" class="text-danger">';
            $html .= htmlspecialchars($equipo['Tag_Number']);
            $html .= '</a>';
            $html .= '</td>';
            $html .= '<td>' . htmlspecialchars($equipo['id_ubicacion']) . '</td>';
            $html .= '<td>' . htmlspecialchars($equipo['Instrument_Type_Desc']) . '</td>';
            $html .= '</tr>';
        }
    } else {
        $html .= '<tr><td colspan="3">No se encontraron equipos en las ubicaciones que coincidan con "' . htmlspecialchars($search) . '".</td></tr>';
    }

    echo $html;
} catch (PDOException $e) {
    die("Error en la consulta: " . $e->getMessage());
}
?>
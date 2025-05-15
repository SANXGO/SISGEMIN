<?php
require_once __DIR__ . '/../includes/conexion.php';

$limit = isset($_GET['limit']) ? $_GET['limit'] : 10;

try {
    if ($limit === 'all') {
        $stmt = $pdo->query("SELECT * FROM equipos");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM equipos LIMIT ?");
        $stmt->execute([$limit]);
    }
    $equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $html = '';
    if (!empty($equipos)) {
        foreach ($equipos as $equipo) {
            $html .= '<tr onclick="seleccionarEquipo(\'' . htmlspecialchars($equipo['Tag_Number']) . '\')" style="cursor: pointer;">';
            $html .= '<td><a href="equipo/detalle_equipo.php?tag_number=' . htmlspecialchars($equipo['Tag_Number']) . '">' . htmlspecialchars($equipo['Tag_Number']) . '</a></td>';
            $html .= '<td>' . htmlspecialchars($equipo['id_ubicacion']) . '</td>';
            $html .= '<td>' . htmlspecialchars($equipo['Instrument_Type_Desc']) . '</td>';
            $html .= '</tr>';
        }
    } else {
        $html .= '<tr><td colspan="3" class="text-center">No se encontraron equipos.</td></tr>';
    }

    echo $html;
} catch (PDOException $e) {
    die("Error en la consulta: " . $e->getMessage());
}
?>
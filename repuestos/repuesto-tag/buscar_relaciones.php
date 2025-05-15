<?php
// repuestos/buscar_relaciones.php
require_once __DIR__ . '/../../includes/conexion.php';

$search = $_GET['search'] ?? '';
$tipo = $_GET['tipo'] ?? 'descripcion';

// Consulta base
$sql = "
    SELECT 
        er.id_puente,
        r.id_repuestos,
        r.descripcion,
        r.real_part,
        e.Tag_Number,
        e.Instrument_Type_Desc
    FROM 
        equipo_repuesto er
    JOIN 
        repuestos r ON er.id_repuesto = r.id_repuestos
    JOIN 
        equipos e ON er.id_equipo = e.Tag_Number
";

// Preparar parámetros
$params = [];
$whereAdded = false;

if (!empty($search)) {
    if ($tipo === 'descripcion') {
        $sql .= " WHERE (r.descripcion LIKE ? OR r.real_part LIKE ?)";
        $params[] = '%'.$search.'%';
        $params[] = '%'.$search.'%';
    } else {
        $sql .= " WHERE e.Tag_Number LIKE ?";
        $params[] = '%'.$search.'%';
    }
    $whereAdded = true;
}

$sql .= " ORDER BY r.descripcion, e.Tag_Number";

try {
    $stmt = $pdo->prepare($sql);
    
    // Vincular parámetros si existen
    if (!empty($params)) {
        foreach ($params as $key => $value) {
            $stmt->bindValue($key + 1, $value); // Los parámetros posicionales empiezan en 1
        }
    }
    
    $stmt->execute();
    $relaciones = $stmt->fetchAll();

    if (empty($relaciones)) {
        echo '<tr><td colspan="5" class="text-center">No se encontraron resultados</td></tr>';
    } else {
        foreach ($relaciones as $relacion) {
            echo '<tr onclick="seleccionarRelacion(\''.$relacion['id_puente'].'\')" style="cursor: pointer;">';
            echo '<td>'.htmlspecialchars($relacion['id_puente']).'</td>';
            echo '<td>'.htmlspecialchars($relacion['descripcion']).'</td>';
            echo '<td>'.htmlspecialchars($relacion['real_part']).'</td>';
            echo '<td>'.htmlspecialchars($relacion['Tag_Number']).'</td>';
            echo '<td>'.htmlspecialchars($relacion['Instrument_Type_Desc']).'</td>';
            echo '</tr>';
        }
    }
} catch (PDOException $e) {
    error_log('Error en buscar_relaciones.php: ' . $e->getMessage());
    echo '<tr><td colspan="5" class="text-center text-danger">Error al realizar la búsqueda</td></tr>';
}
?>
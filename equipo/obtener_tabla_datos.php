<?php
require_once __DIR__ . '/../includes/conexion.php';

$tagNumber = $_GET['tag_number'] ?? '';

try {
    $stmt = $pdo->prepare("SELECT * FROM equipo_graficos WHERE tag_number = ? ORDER BY fecha_creacion DESC");
    $stmt->execute([$tagNumber]);
    $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($datos) > 0) {
        foreach ($datos as $dato) {
            echo "<tr>
                    <td>" . htmlspecialchars(date('d/m/Y H:i', strtotime($dato['fecha_creacion']))) . "</td>
                    <td>" . htmlspecialchars($dato['eje_x']) . "</td>
                    <td>" . htmlspecialchars($dato['eje_y']) . "</td>
                    <td>
                        <button class='btn btn-sm btn-danger' onclick='eliminarDato(" . $dato['id'] . ")'>
                            <i class='bi bi-trash'></i> Eliminar
                        </button>
                    </td>
                </tr>";
        }
    } else {
        echo "<tr><td colspan='4' class='text-center'>No hay datos registrados</td></tr>";
    }
} catch (PDOException $e) {
    echo "<tr><td colspan='4' class='text-center'>Error al cargar datos</td></tr>";
}
?>
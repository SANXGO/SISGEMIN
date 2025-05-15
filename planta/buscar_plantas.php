<?php
require_once __DIR__ . '/../includes/conexion.php';

$search = isset($_GET['search']) ? $_GET['search'] : '';

// Consulta para buscar plantas por nombre
$stmt = $pdo->prepare("
    SELECT id_planta, nombres 
    FROM planta
    WHERE nombres LIKE ? AND estado = 'activo'
");
$stmt->execute(["%$search%"]); // Usamos % para buscar coincidencias parciales
$plantas = $stmt->fetchAll();

foreach ($plantas as $planta): ?>
    <tr onclick="seleccionarPlanta('<?= $planta['id_planta'] ?>')" style="cursor: pointer;">
        <td><?= htmlspecialchars($planta['id_planta']) ?></td>
        <td><?= htmlspecialchars($planta['nombres']) ?></td>
    </tr>
<?php endforeach;
?>
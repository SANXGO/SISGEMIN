<?php
require_once __DIR__ . '/../../includes/conexion.php';

if (isset($_GET['search'])) {
    $search = '%' . $_GET['search'] . '%';
    
    $stmt = $pdo->prepare("SELECT id_fabricante, nombres FROM fabricantes WHERE estado = 'activo' AND nombres LIKE ? ORDER BY id_fabricante");
    $stmt->execute([$search]);
    $fabricantes = $stmt->fetchAll();
    
    foreach ($fabricantes as $fabricante) {
        echo '<tr onclick="seleccionarFabricante(\'' . $fabricante['id_fabricante'] . '\')" style="cursor: pointer;">';
        echo '<td>' . htmlspecialchars($fabricante['id_fabricante']) . '</td>';
        echo '<td>' . htmlspecialchars($fabricante['nombres']) . '</td>';
        echo '</tr>';
    }
}
?>
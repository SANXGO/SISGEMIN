<?php
require_once __DIR__ . '/../../includes/conexion.php';

header('Content-Type: application/json');

$nombre = isset($_GET['nombre']) ? trim($_GET['nombre']) : '';
$idExcluir = isset($_GET['id_excluir']) ? (int)$_GET['id_excluir'] : 0;

try {
    if ($idExcluir > 0) {
        // Verificar existencia excluyendo un ID específico (para edición)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM fabricantes WHERE nombres = ? AND id_fabricante != ?");
        $stmt->execute([$nombre, $idExcluir]);
    } else {
        // Verificar existencia simple (para agregar)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM fabricantes WHERE nombres = ?");
        $stmt->execute([$nombre]);
    }
    
    $existe = $stmt->fetchColumn() > 0;
    
    echo json_encode(['existe' => $existe]);
} catch (PDOException $e) {
    echo json_encode(['existe' => false]);
}
?>
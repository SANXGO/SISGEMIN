<?php
require_once __DIR__ . '/../includes/conexion.php';

header('Content-Type: application/json');

if (!isset($_GET['nombre'])) {
    echo json_encode(['existe' => false]);
    exit;
}

$nombre = trim($_GET['nombre']);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM planta WHERE nombres = :nombre");
$stmt->bindValue(':nombre', $nombre, PDO::PARAM_STR);
$stmt->execute();

echo json_encode(['existe' => $stmt->fetchColumn() > 0]);
?>
<?php
require_once __DIR__ . '/../includes/conexion.php';

header('Content-Type: application/json');

if (!isset($_GET['nombre']) || !isset($_GET['id_actual'])) {
    echo json_encode(['existe' => false]);
    exit;
}

$nombre = trim($_GET['nombre']);
$idActual = $_GET['id_actual'];

$stmt = $pdo->prepare("SELECT COUNT(*) FROM planta WHERE nombres = :nombre AND id_planta != :id_actual");
$stmt->bindValue(':nombre', $nombre, PDO::PARAM_STR);
$stmt->bindValue(':id_actual', $idActual, PDO::PARAM_INT);
$stmt->execute();

echo json_encode(['existe' => $stmt->fetchColumn() > 0]);
?>
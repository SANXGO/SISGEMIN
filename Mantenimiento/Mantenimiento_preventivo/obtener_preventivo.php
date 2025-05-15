<?php
require_once __DIR__ . '/../../includes/conexion.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'ID no proporcionado']);
    exit;
}

$id_mantenimiento = $_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM mantenimiento WHERE id_mantenimiento = ?");
$stmt->execute([$id_mantenimiento]);
$mantenimiento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$mantenimiento) {
    echo json_encode(['error' => 'Mantenimiento no encontrado']);
    exit;
}

echo json_encode($mantenimiento);
?>
<?php
require_once __DIR__ . '/../includes/conexion.php';

header('Content-Type: application/json');

if (!isset($_GET['id_ubicacion']) || empty($_GET['id_ubicacion'])) {
    echo json_encode(['existe' => false]);
    exit;
}

$idUbicacion = trim($_GET['id_ubicacion']);

// Preparar la consulta para verificar si el ID ya existe
$stmt = $pdo->prepare("SELECT COUNT(*) FROM ubicacion WHERE id_ubicacion = :id_ubicacion");
$stmt->bindValue(':id_ubicacion', $idUbicacion, PDO::PARAM_STR);
$stmt->execute();
$existe = $stmt->fetchColumn() > 0;

echo json_encode(['existe' => $existe]);
?>
<?php
require_once __DIR__ . '/../includes/conexion.php';

header('Content-Type: application/json');

// Validar parámetros
if (!isset($_GET['campo']) || !isset($_GET['valor'])) {
    echo json_encode(['error' => 'Parámetros incompletos']);
    exit;
}

$campo = $_GET['campo'];
$valor = $_GET['valor'];
$excluir = isset($_GET['excluir']) ? (int)$_GET['excluir'] : 0;

// Validar campo permitido
if (!in_array($campo, ['cedula', 'correo'])) {
    echo json_encode(['error' => 'Campo no válido']);
    exit;
}

// Consulta para verificar existencia
$sql = "SELECT COUNT(*) as total FROM usuario WHERE $campo = ? AND id_usuario != ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$valor, $excluir]);
$resultado = $stmt->fetch();

echo json_encode(['existe' => $resultado['total'] > 0]);
?>
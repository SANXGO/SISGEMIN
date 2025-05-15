<?php
require_once __DIR__ . '/../includes/conexion.php';

$orden = filter_input(INPUT_GET, 'orden', FILTER_SANITIZE_STRING);

if (empty($orden)) {
    echo json_encode(['existe' => false]);
    exit;
}

$stmt = $pdo->prepare("SELECT id_actividad FROM actividades WHERE orden = ?");
$stmt->execute([$orden]);
$existe = (bool)$stmt->fetch();

echo json_encode(['existe' => $existe]);
<?php
require_once __DIR__ . '/../../includes/conexion.php';

// Obtener parámetros de búsqueda
$searchTerm = $_GET['searchTerm'] ?? '';
$searchType = $_GET['searchType'] ?? 'tag';
$searchFechaInicio = $_GET['searchFechaInicio'] ?? '';
$searchFechaFin = $_GET['searchFechaFin'] ?? '';

// Obtener el id_usuario de la sesión
session_start();
$id_usuario = $_SESSION['id_usuario'] ?? null;

if (!$id_usuario) {
    die(json_encode(['error' => 'Usuario no autenticado']));
}

// Consulta para obtener el id_planta del usuario
$stmt = $pdo->prepare("SELECT id_planta FROM usuario WHERE id_usuario = ?");
$stmt->execute([$id_usuario]);
$usuario = $stmt->fetch();

if (!$usuario) {
    die(json_encode(['error' => 'Usuario no encontrado']));
}

$id_planta_usuario = $usuario['id_planta'];

// Construir consulta base con filtros
$where = "m.Tag_Number IN (SELECT e.Tag_Number FROM equipos e 
                          JOIN ubicacion u ON e.id_ubicacion = u.id_ubicacion 
                          WHERE u.id_planta = :id_planta)";
$params = [':id_planta' => $id_planta_usuario];

if (!empty($searchTerm)) {
    if ($searchType === 'tag') {
        $where .= " AND m.Tag_Number LIKE :tag";
        $params[':tag'] = "%$searchTerm%";
    } elseif ($searchType === 'ubicacion') {
        $where .= " AND m.Tag_Number IN (SELECT e.Tag_Number FROM equipos e 
                                        JOIN ubicacion u ON e.id_ubicacion = u.id_ubicacion 
                                        WHERE u.descripcion LIKE :ubicacion)";
        $params[':ubicacion'] = "%$searchTerm%";
    }
}

if (!empty($searchFechaInicio) && !empty($searchFechaFin)) {
    $where .= " AND m.fecha BETWEEN :fechaInicio AND :fechaFin";
    $params[':fechaInicio'] = $searchFechaInicio;
    $params[':fechaFin'] = $searchFechaFin;
} elseif (!empty($searchFechaInicio)) {
    $where .= " AND m.fecha = :fechaInicio";
    $params[':fechaInicio'] = $searchFechaInicio;
} elseif (!empty($searchFechaFin)) {
    $where .= " AND m.fecha = :fechaFin";
    $params[':fechaFin'] = $searchFechaFin;
}

// Consulta para los datos
$query = "SELECT m.*, u.descripcion as ubicacion 
          FROM mantenimiento m
          JOIN equipos e ON m.Tag_Number = e.Tag_Number
          JOIN ubicacion u ON e.id_ubicacion = u.id_ubicacion
          WHERE $where ORDER BY m.fecha DESC";
$stmt = $pdo->prepare($query);

// Bind parameters
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->execute();
$resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($resultados);
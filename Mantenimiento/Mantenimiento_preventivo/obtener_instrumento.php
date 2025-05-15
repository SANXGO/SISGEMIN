<?php
require_once __DIR__ . '/../../includes/conexion.php';

header('Content-Type: application/json'); // IMPORTANTE: Cabecera JSON

try {
    if (!isset($_GET['tag_number'])) {
        throw new Exception('Tag number no proporcionado');
    }

    $tagNumber = $_GET['tag_number'];
    
    $query = "SELECT Instrument_Type_Desc FROM equipos WHERE Tag_Number = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$tagNumber]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        throw new Exception('Tag number no encontrado');
    }

    echo json_encode([
        'success' => true,
        'Instrument_Type_Desc' => $result['Instrument_Type_Desc']
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
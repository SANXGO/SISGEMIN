<?php
require_once __DIR__ . '/../includes/conexion.php';

$response = ['existe' => false];

if (isset($_GET['tag_number'])) {
    $tagNumber = trim($_GET['tag_number']);
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM equipos WHERE Tag_Number = ?");
        $stmt->execute([$tagNumber]);
        $count = $stmt->fetchColumn();
        
        $response['existe'] = ($count > 0);
    } catch (PDOException $e) {
        // En caso de error, asumimos que no existe para no bloquear el formulario
        $response['existe'] = false;
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>
<?php
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/../../includes/audit.php';

session_start();

header('Content-Type: application/json');

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'error' => 'no_autenticado']);
    exit;
}
// Verificar si se envió el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar y sanitizar los datos del formulario
        $tag_number = filter_input(INPUT_POST, 'tag_number', FILTER_SANITIZE_STRING);
        logAuditAction('Mantenimiento Preventivo', 'Update', "Agregar Mantenimiento Preventivo $tag_number", $_POST);

        $id_tipo = filter_input(INPUT_POST, 'id_tipo', FILTER_SANITIZE_STRING);
        $fecha = filter_input(INPUT_POST, 'fecha', FILTER_SANITIZE_STRING);
        $planta = filter_input(INPUT_POST, 'planta', FILTER_SANITIZE_STRING);
        $orden = filter_input(INPUT_POST, 'orden', FILTER_SANITIZE_STRING);
        $instalacion = filter_input(INPUT_POST, 'instalacion', FILTER_SANITIZE_STRING);
        $serial = filter_input(INPUT_POST, 'serial', FILTER_SANITIZE_STRING);
        $modelo = filter_input(INPUT_POST, 'modelo', FILTER_SANITIZE_STRING);
        $mantenimiento = filter_input(INPUT_POST, 'mantenimiento', FILTER_SANITIZE_STRING);
        $medicion_metrica = filter_input(INPUT_POST, 'medicion_metrica', FILTER_SANITIZE_STRING);
        $voltaje = filter_input(INPUT_POST, '24vdc', FILTER_SANITIZE_STRING);
        $observaciones = filter_input(INPUT_POST, 'observaciones', FILTER_SANITIZE_STRING);
        $sintomas = filter_input(INPUT_POST, 'sintomas', FILTER_SANITIZE_STRING);

        // Validaciones adicionales
        if (empty($tag_number) || empty($fecha) || empty($orden)) {
            throw new Exception('Los campos obligatorios no pueden estar vacíos');
        }

        // Preparar la consulta SQL
        $sql = "INSERT INTO mantenimiento (
            Tag_Number, 
            id_tipo, 
            fecha, 
            planta, 
            orden, 
            instalacion, 
            serial, 
            modelo, 
            mantenimiento, 
            medicion_metrica, 
            `24vdc`, 
            observaciones, 
            sintomas
        ) VALUES (
            :tag_number, 
            :id_tipo, 
            :fecha, 
            :planta, 
            :orden, 
            :instalacion, 
            :serial, 
            :modelo, 
            :mantenimiento, 
            :medicion_metrica, 
            :voltaje, 
            :observaciones, 
            :sintomas
        )";

        $stmt = $pdo->prepare($sql);

        // Bind de parámetros
        $stmt->bindParam(':tag_number', $tag_number, PDO::PARAM_STR);
        $stmt->bindParam(':id_tipo', $id_tipo, PDO::PARAM_STR);
        $stmt->bindParam(':fecha', $fecha, PDO::PARAM_STR);
        $stmt->bindParam(':planta', $planta, PDO::PARAM_STR);
        $stmt->bindParam(':orden', $orden, PDO::PARAM_STR);
        $stmt->bindParam(':instalacion', $instalacion, PDO::PARAM_STR);
        $stmt->bindParam(':serial', $serial, PDO::PARAM_STR);
        $stmt->bindParam(':modelo', $modelo, PDO::PARAM_STR);
        $stmt->bindParam(':mantenimiento', $mantenimiento, PDO::PARAM_STR);
        $stmt->bindParam(':medicion_metrica', $medicion_metrica, PDO::PARAM_STR);
        $stmt->bindParam(':voltaje', $voltaje, PDO::PARAM_STR);
        $stmt->bindParam(':observaciones', $observaciones, PDO::PARAM_STR);
        $stmt->bindParam(':sintomas', $sintomas, PDO::PARAM_STR);

        // Ejecutar la consulta
        if ($stmt->execute()) {
            // Respuesta JSON para éxito
            logAuditAction('Mantenimiento Preventivo', 'Update', "Registrado exitosamente Mantenimiento Preventivo $tag_number", $_POST);

            echo json_encode([
                'success' => true,
                'message' => 'Mantenimiento agregado exitosamente',
                'redirect' => '../../index.php?tabla=Mantenimiento_preventivo&agregado=1'
            ]);
            exit;
        } else {
            throw new Exception('Error al ejecutar la consulta');
        }
    } catch (Exception $e) {
        // Respuesta JSON para error
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit;
    }
} else {
    // Si no es POST, redireccionar

    header('Location: ../../index.php');
    exit;
}
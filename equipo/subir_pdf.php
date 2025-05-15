<?php
require_once __DIR__ . '/../includes/conexion.php';
require_once __DIR__ . '/../includes/audit.php';


session_start();

header('Content-Type: application/json');

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'error' => 'no_autenticado']);
    exit;
}



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    try {
        $tagNumber = $_POST['tag_number'];
        $uploadDir = '../equipo/pdf/' . $tagNumber . '/';
        
        // Verificar si la carpeta del Tag_Number existe, si no, crearla
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                throw new Exception('No se pudo crear el directorio para almacenar el PDF');
            }
        }

        // Verificar si se subió un archivo
        if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Error al subir el archivo');
        }

        // Validar que sea un PDF
        $fileType = mime_content_type($_FILES['pdf_file']['tmp_name']);
        if ($fileType !== 'application/pdf') {
            throw new Exception('Solo se permiten archivos PDF');
        }

        // Obtener el nombre del archivo y la ruta de destino
        $fileName = basename($_FILES['pdf_file']['name']);
        $uploadFilePath = $uploadDir . $fileName;

        // Mover el archivo subido a la carpeta correspondiente
        if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $uploadFilePath)) {
            // Insertar la ruta del archivo en la base de datos
            $stmt = $pdo->prepare("INSERT INTO archivos_pdf (Tag_Number, ruta_archivo) VALUES (?, ?)");
            $stmt->execute([$tagNumber, $uploadFilePath]);
            

            logAuditAction('equipos', 'Upload', "Subida de PDF para el equipo $tagNumber", [
                'file_name' => $_FILES['pdf_file']['name'],
                'file_size' => $_FILES['pdf_file']['size']
            ]);


            
            echo json_encode([
                'success' => true,
                'message' => 'PDF subido correctamente'
            ]);
            exit();
        } else {
            throw new Exception('Error al mover el archivo subido');
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit();
    }
}

// Si no es POST, devolver error
echo json_encode([
    'success' => false,
    'message' => 'Método no permitido'
]);
exit();
?>
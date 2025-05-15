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
    // Obtener datos del formulario
    $tag_number = $_POST['tag_number'];
    logAuditAction('equipos', 'Create', "Inicio de creación de nuevo equipo", $_POST);
    if (preg_match('/\s/', $tag_number)) {
        echo json_encode(['success' => false, 'message' => 'El Tag Number no puede contener espacios']);
        exit;
    }
    
    $id_ubicacion = $_POST['id_ubicacion'];
    $instrument_type = $_POST['instrument_type'];
    
    // Campos opcionales
    $cantidad = $_POST['cantidad'] ?? null;
    $f_location = $_POST['f_location'] ?? null;
    $service_upper = $_POST['service_upper'] ?? null;
    $p_id_no = $_POST['p_id_no'] ?? null;
    $sys_tag = $_POST['sys_tag'] ?? null;
    $line_size = $_POST['line_size'] ?? null;
    $rating = $_POST['rating'] ?? null;
    $facing = $_POST['facing'] ?? null;
    $lineclass = $_POST['lineclass'] ?? null;
    $system_in = $_POST['system_in'] ?? null;
    $system_out = $_POST['system_out'] ?? null;
    $io_type_out = $_POST['io_type_out'] ?? null;
    $signal_cond = $_POST['signal_cond'] ?? null;
    $crtl_act = $_POST['crtl_act'] ?? null;
    $state_0 = $_POST['state_0'] ?? null;
    $state_1 = $_POST['state_1'] ?? null;
    $po_number = $_POST['po_number'] ?? null;
    $junction_box_no = $_POST['junction_box_no'] ?? null;
    $foto_perfil = null;
    $herramientas = $_POST['herramientas'] ?? null;
$empacadura = $_POST['empacadura'] ?? null;
$esparragos = $_POST['esparragos'] ?? null;

    // Procesar la imagen si se subió
    if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
        // Configuraciones para la imagen
        $directorioImagenes = __DIR__ . '/../assets/img/equipos/';
        $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'gif'];
        $tamanoMaximo = 2 * 1024 * 1024; // 2MB
        
        // Obtener información del archivo
        $archivoInfo = pathinfo($_FILES['foto_perfil']['name']);
        $extension = strtolower($archivoInfo['extension']);
        
        // Validar extensión
        if (!in_array($extension, $extensionesPermitidas)) {
            echo json_encode(['success' => false, 'message' => 'Formato de imagen no permitido. Use JPG, PNG o GIF']);
            exit;
        }
        
        // Validar tamaño
        if ($_FILES['foto_perfil']['size'] > $tamanoMaximo) {
            echo json_encode(['success' => false, 'message' => 'La imagen es demasiado grande (máx 2MB)']);
            exit;
        }
        
        // Crear directorio si no existe
        if (!file_exists($directorioImagenes)) {
            mkdir($directorioImagenes, 0755, true);
        }
        
        // Generar nombre único para la imagen
        $nombreImagen = 'equipo_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
        $rutaDestino = $directorioImagenes . $nombreImagen;
        
        // Mover el archivo
        if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $rutaDestino)) {
            $foto_perfil = $nombreImagen;
        } else {
            echo json_encode(['success' => false, 'message' => 'Error al subir la imagen']);
            exit;
        }
    }

    try {
        // Preparar la consulta SQL incluyendo foto_perfil
        $stmt = $pdo->prepare("INSERT INTO equipos (
            Tag_Number, id_ubicacion, Instrument_Type_Desc, Cantidad, 
            F_location, Service_Upper, P_ID_No, SYS_TAG, Line_size, 
            Rating, Facing, Lineclass, SYSTEM_IN, SYSTEM_OUT, IO_TYPE_OUT, 
            SIGNAL_COND, CRTL_ACT, STATE_0, STATE_1, Po_Number, Junction_box_no, 
            foto_perfil, Herramientas, Empacadura, Esparragos
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt->execute([
            $tag_number, $id_ubicacion, $instrument_type, $cantidad, 
            $f_location, $service_upper, $p_id_no, $sys_tag, $line_size, 
            $rating, $facing, $lineclass, $system_in, $system_out, $io_type_out, 
            $signal_cond, $crtl_act, $state_0, $state_1, $po_number, $junction_box_no, 
            $foto_perfil, $herramientas, $empacadura, $esparragos
        ]);
        logAuditAction('equipos', 'Create', "Creacion exitosa de nuevo equipo", $_POST);
        echo json_encode(['success' => true, 'message' => 'Equipo agregado correctamente']);
    } catch (PDOException $e) {
        // Si hay error, eliminar la imagen si se subió
        if ($foto_perfil && file_exists($rutaDestino)) {
            unlink($rutaDestino);
        }
        
        echo json_encode(['success' => false, 'message' => 'Error al agregar equipo: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Método no permitido']);
?>
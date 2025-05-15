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
        // Obtener todos los datos del formulario
        $tag_number = $_POST['tag_number'];
        logAuditAction('equipos', 'Update', "Inicio de edición del equipo $tag_number", $_POST);

        $id_ubicacion = $_POST['id_ubicacion'];
        $instrument_type = $_POST['instrument_type'];
        $cantidad = $_POST['cantidad'];
        $f_location = $_POST['f_location'];
        $service_upper = $_POST['service_upper'];
        $p_id_no = $_POST['p_id_no'];
        $sys_tag = $_POST['sys_tag'];
        $line_size = $_POST['line_size'];
        $rating = $_POST['rating'];
        $facing = $_POST['facing'];
        $lineclass = $_POST['lineclass'];
        $system_in = $_POST['system_in'];
        $system_out = $_POST['system_out'];
        $io_type_out = $_POST['io_type_out'];
        $signal_cond = $_POST['signal_cond'];
        $crtl_act = $_POST['crtl_act'];
        $state_0 = $_POST['state_0'];
        $state_1 = $_POST['state_1'];
        $po_number = $_POST['po_number'];
        $junction_box_no = $_POST['junction_box_no'];
        $herramientas = $_POST['herramientas'] ?? null;
$empacadura = $_POST['empacadura'] ?? null;
$esparragos = $_POST['esparragos'] ?? null;
        
        // Inicializar la variable para la foto
        $foto_perfil = null;
        
        // Obtener la foto actual del equipo
        $stmt_foto = $pdo->prepare("SELECT foto_perfil FROM equipos WHERE Tag_Number = ?");
        $stmt_foto->execute([$tag_number]);
        $equipo_actual = $stmt_foto->fetch(PDO::FETCH_ASSOC);
        
        // Manejar la eliminación de la foto si está marcado el checkbox
        if (isset($_POST['eliminar_foto'])) {
            // Eliminar el archivo físico si existe
            if (!empty($equipo_actual['foto_perfil'])) {
                $ruta_foto = __DIR__ . '/../assets/img/equipos/' . basename($equipo_actual['foto_perfil']);
                if (file_exists($ruta_foto)) {
                    unlink($ruta_foto);
                }
            }
            $foto_perfil = null;
        } else {
            // Mantener la foto actual si no se sube una nueva
            $foto_perfil = $equipo_actual['foto_perfil'];
        }
        
        // Manejar la subida de nueva imagen
        if (isset($_FILES['foto_perfil']) && $_FILES['foto_perfil']['error'] === UPLOAD_ERR_OK) {
            $directorio = __DIR__ . '/../assets/img/equipos/';
            
            // Crear directorio si no existe
            if (!file_exists($directorio)) {
                mkdir($directorio, 0777, true);
            }
            
            // Eliminar la foto anterior si existe
            if (!empty($equipo_actual['foto_perfil'])) {
                $ruta_foto_anterior = __DIR__ . '/../assets/img/equipos/' . basename($equipo_actual['foto_perfil']);
                if (file_exists($ruta_foto_anterior)) {
                    unlink($ruta_foto_anterior);
                }
            }
            
            // Validar el tipo de archivo
            $extensiones_permitidas = ['jpg', 'jpeg', 'png', 'gif'];
            $nombre_archivo = $_FILES['foto_perfil']['name'];
            $extension = strtolower(pathinfo($nombre_archivo, PATHINFO_EXTENSION));
            
            if (!in_array($extension, $extensiones_permitidas)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Solo se permiten archivos JPG, JPEG, PNG o GIF.'
                ]);
                exit();
            }
            
            // Validar tamaño del archivo (máximo 2MB)
            if ($_FILES['foto_perfil']['size'] > 2097152) {
                echo json_encode([
                    'success' => false,
                    'message' => 'El archivo es demasiado grande. El tamaño máximo permitido es 2MB.'
                ]);
                exit();
            }
            
            // Generar un nombre único para el archivo
            $nuevo_nombre = 'equipo_' . $tag_number . '_' . time() . '.' . $extension;
            $ruta_destino = $directorio . $nuevo_nombre;
            
            // Mover el archivo subido al directorio de destino
            if (move_uploaded_file($_FILES['foto_perfil']['tmp_name'], $ruta_destino)) {
                $foto_perfil = '../assets/img/equipos/' . $nuevo_nombre;
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error al subir el archivo.'
                ]);
                exit();
            }
        }
        
        // Preparar la consulta SQL para actualizar el equipo
        $sql = "UPDATE equipos SET 
    id_ubicacion = ?, 
    Instrument_Type_Desc = ?, 
    Cantidad = ?, 
    F_location = ?, 
    Service_Upper = ?, 
    P_ID_No = ?, 
    SYS_TAG = ?, 
    Line_size = ?, 
    Rating = ?, 
    Facing = ?, 
    Lineclass = ?, 
    SYSTEM_IN = ?, 
    SYSTEM_OUT = ?, 
    IO_TYPE_OUT = ?, 
    SIGNAL_COND = ?, 
    CRTL_ACT = ?, 
    STATE_0 = ?, 
    STATE_1 = ?, 
    Po_Number = ?, 
    Junction_box_no = ?,
    Herramientas = ?,
    Empacadura = ?,
    Esparragos = ?";
        
        // Agregar la foto a la consulta si es necesario
        if ($foto_perfil !== null) {
            $sql .= ", foto_perfil = ?";
        }
        
        $sql .= " WHERE Tag_Number = ?";
        
        $stmt = $pdo->prepare($sql);
        
        // Crear el array de parámetros
        $params = [
            $id_ubicacion, $instrument_type, $cantidad, $f_location, $service_upper, 
            $p_id_no, $sys_tag, $line_size, $rating, $facing, $lineclass, $system_in, 
            $system_out, $io_type_out, $signal_cond, $crtl_act, $state_0, $state_1, 
            $po_number, $junction_box_no, $herramientas, $empacadura, $esparragos
        ];
        
        // Agregar la foto a los parámetros si es necesario
        if ($foto_perfil !== null) {
            $params[] = $foto_perfil;
        }
        
        // Agregar el tag_number al final
        $params[] = $tag_number;
        
        // Ejecutar la consulta
        $stmt->execute($params);

        logAuditAction('equipos', 'Update', "Edición completada del equipo $tag_number", $_POST);

        echo json_encode([
            'success' => true,
            'message' => 'Equipo actualizado correctamente',
            'tag_number' => $tag_number,
            'foto_perfil' => $foto_perfil

        ]

    )

    ;

        exit();
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error en la base de datos: ' . $e->getMessage()
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
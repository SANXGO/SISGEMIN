<?php
// Iniciar sesión primero
session_start();

require('../../FPDF_master/fpdf.php');
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/../../includes/audit.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['id_usuario'])) {
    die(json_encode(['error' => 'Usuario no autenticado']));
}

$id_usuario = $_SESSION['id_usuario'];

// Consulta para obtener el id_planta del usuario
try {
    $stmt = $pdo->prepare("SELECT id_planta FROM usuario WHERE id_usuario = ?");
    $stmt->execute([$id_usuario]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        die(json_encode(['error' => 'Usuario no encontrado en la base de datos']));
    }

    $id_planta_usuario = $usuario['id_planta'];
} catch (PDOException $e) {
    die(json_encode(['error' => 'Error al consultar la base de datos: ' . $e->getMessage()]));
}


logModuleAccess('Reporte de registros de Mantenimiento Correctivo');

class PDF extends FPDF {
    private $showFooter = false;
    
    // Cabecera de página
    function Header() {
        $this->Image('../../Pequiven.png', 10, 10, 30);
        
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'MANTENIMIENTO CORRECTIVO', 0, 1, 'C');
        $this->Ln(10);
        
        $this->SetFont('Arial', 'B', 8);
        // Encabezados de la tabla con tamaños ajustados para coincidir con el preventivo
        $this->Cell(10, 8, '#', 1, 0, 'C');
        $this->Cell(25, 8, 'TAG', 1, 0, 'C');
        $this->Cell(28, 8, 'MANTENIMIENTO', 1, 0, 'C');
        $this->Cell(22, 8, 'FECHA', 1, 0, 'C');
        $this->Cell(50, 8, 'REPUESTOS', 1, 0, 'C');
        $this->Cell(145, 8, 'DESCRIPCION', 1, 1, 'C');
    }
    
    // Pie de página personalizado (idéntico al preventivo)
    function CustomFooter() {
        $this->Ln(5); // Espacio antes de las firmas
        
        // Ajustar el ancho total a ~280 (ancho aproximado en horizontal)
        $anchoTotal = 280;
        $anchoCelda = $anchoTotal / 3;
        
        $this->SetFont('Arial', 'B', 8);
        $this->Cell($anchoCelda, 8, 'ELABORADO', 1, 0, 'C');
        $this->Cell($anchoCelda, 8, 'REVISADO', 1, 0, 'C');
        $this->Cell($anchoCelda, 8, 'APROBADO POR', 1, 1, 'C');
        
        // Texto debajo de las firmas - ajustar proporciones
        $anchoNombre = 60;
        $anchoFirma = $anchoCelda - $anchoNombre;
        
        $this->SetFont('Arial', '', 8);
        $this->Cell($anchoNombre, 8, 'NOMBRES', 1, 0, 'C');
        $this->Cell($anchoFirma, 8, '', 1, 0, 'C');
        $this->Cell($anchoNombre, 8, 'NOMBRES', 1, 0, 'C');
        $this->Cell($anchoFirma, 8, '', 1, 0, 'C');
        $this->Cell($anchoNombre, 8, 'NOMBRES', 1, 0, 'C');
        $this->Cell($anchoFirma, 8, '', 1, 1, 'C');

        $this->Cell($anchoNombre, 8, 'FECHA', 1, 0, 'C');
        $this->Cell($anchoFirma, 8, '', 1, 0, 'C');
        $this->Cell($anchoNombre, 8, 'FECHA', 1, 0, 'C');
        $this->Cell($anchoFirma, 8, '', 1, 0, 'C');
        $this->Cell($anchoNombre, 8, 'FECHA', 1, 0, 'C');
        $this->Cell($anchoFirma, 8, '', 1, 1, 'C');
    }
    
    function Footer() {
        // Footer tradicional desactivado
    }

    // Función para escribir una fila de la tabla (ajustada para coincidir con el preventivo)
    function WriteRow($data, $numero) {
        $height = 8; // Altura fija igual que en preventivo
        $border = 1; // Borde visible
        
        // Guardar posición inicial
        $startX = $this->GetX();
        $startY = $this->GetY();
        
        // Verificar si hay espacio suficiente en la página
        if($startY + $height > 170) { // 170 es el límite antes del footer (igual que preventivo)
            $this->AddPage();
            $startY = $this->GetY(); // Actualizar posición Y
        }
        
        // N°
        $this->Cell(10, $height, $numero, $border, 0, 'C');
        
        // TAG
        $this->Cell(25, $height, $data['Tag_Number'], $border, 0, 'L');
        
        // TIPO
        $this->Cell(28, $height, $data['mantenimiento_correctivo'], $border, 0, 'L');
        
        // FECHA
        $fecha = !empty($data['fecha']) ? date('d/m/Y', strtotime($data['fecha'])) : '';
        $this->Cell(22, $height, $fecha, $border, 0, 'C');
        
        // REPUESTOS
        $this->Cell(50, $height, $data['repuestos'], $border, 0, 'L');
        
        // DESCRIPCIÓN (mismo tamaño que observaciones en preventivo)
        $this->Cell(145, $height, substr($data['descripcion'], 0, 100), $border, 1, 'L');
    }
}

// Cambiar la obtención de parámetros al inicio del archivo:
$searchTerm = $_GET['searchTerm'] ?? '';
$searchType = $_GET['searchType'] ?? 'tag'; // 'tag' o 'ubicacion'
$searchFechaInicio = $_GET['searchFechaInicio'] ?? '';
$searchFechaFin = $_GET['searchFechaFin'] ?? '';

// Modificar la construcción de la consulta SQL:
$query = "SELECT i.* FROM intervencion i
          JOIN equipos e ON i.Tag_Number = e.Tag_Number
          JOIN ubicacion u ON e.id_ubicacion = u.id_ubicacion
          WHERE u.id_planta = :id_planta";
$params = [':id_planta' => $id_planta_usuario];

// Aplicar filtros adicionales si existen
if (!empty($searchTerm)) {
    if ($searchType === 'tag') {
        $query .= " AND i.Tag_Number LIKE :searchTerm";
        $params[':searchTerm'] = "%$searchTerm%";
    } elseif ($searchType === 'ubicacion') {
        $query .= " AND u.descripcion LIKE :searchTerm";
        $params[':searchTerm'] = "%$searchTerm%";
    }
}

if (!empty($searchFechaInicio)) {
    if (empty($searchFechaFin)) {
        // Solo fecha inicio - buscar ese día específico
        $query .= " AND i.fecha = :fecha_inicio";
        $params[':fecha_inicio'] = $searchFechaInicio;
    } else {
        // Rango de fechas
        $query .= " AND i.fecha BETWEEN :fecha_inicio AND :fecha_fin";
        $params[':fecha_inicio'] = $searchFechaInicio;
        $params[':fecha_fin'] = $searchFechaFin;
    }
}

// Ordenar por fecha descendente
$query .= " ORDER BY i.fecha DESC";

// Ejecutar consulta
$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $paramType = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue($key, $value, $paramType);
}
$stmt->execute();
$intervenciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Crear PDF en orientación horizontal
$pdf = new PDF('L'); // 'L' para landscape (horizontal)
$pdf->AddPage();
$pdf->SetFont('Arial', '', 8);



// Agregar datos de las intervenciones
// Agregar datos de las intervenciones
$numeroRegistro = 1;
foreach ($intervenciones as $row) {
    $rowData = [
        'Tag_Number' => $row['Tag_Number'] ?? '',
        'mantenimiento_correctivo' => 'CORRECTIVO', // Texto fijo
        'fecha' => $row['fecha'] ?? '',
        'descripcion' => $row['descripcion'] ?? '',
        'repuestos' => $row['repuestos'] ?? ''
    ];
    
    $pdf->WriteRow($rowData, $numeroRegistro);
    $numeroRegistro++;
}

// Si no hay intervenciones, mostrar mensaje
if (empty($intervenciones)) {
    $pdf->SetFont('Arial', 'I', 12);
    $pdf->Cell(0, 10, 'No se encontraron registros de mantenimiento correctivo', 0, 1, 'C');
    
  
}

// Mostrar footer después de los registros (idéntico al preventivo)
$pdf->CustomFooter();

$pdf->Output('Reporte_Mantenimiento_Correctivo.pdf', 'I');
?>
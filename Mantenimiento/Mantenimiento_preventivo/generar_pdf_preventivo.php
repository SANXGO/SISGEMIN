<?php
require('../../FPDF_master/fpdf.php');
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/../../includes/audit.php';
// Iniciar sesión para obtener el id_usuario
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['id_usuario'])) {
    die('Usuario no autenticado');
}

// Obtener el id_planta del usuario
$stmt = $pdo->prepare("SELECT id_planta FROM usuario WHERE id_usuario = ?");
$stmt->execute([$_SESSION['id_usuario']]);
$usuario = $stmt->fetch();

if (!$usuario) {
    die('Usuario no encontrado');
}

$id_planta_usuario = $usuario['id_planta'];



logModuleAccess('Reporte de registros de Mantenimiento preventivo');

class PDF extends FPDF {
    private $showFooter = false;
    
    // Cabecera de página
    function Header() {
        $this->Image('../../Pequiven.png', 10, 10, 30);
        
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'MANTENIMIENTO PREVENTIVO', 0, 1, 'C');
        $this->Ln(10);
        
        $this->SetFont('Arial', 'B', 8);
        // Encabezados de la tabla con tamaños ajustados
        $this->Cell(15, 8, '#', 1, 0, 'C'); // Columna para el número de registro
        $this->Cell(25, 8, 'TAG', 1, 0, 'C');
        $this->Cell(25, 8, 'FECHA', 1, 0, 'C');
        $this->Cell(40, 8, 'MANTENIMIENTO', 1, 0, 'C');
        $this->Cell(35, 8, 'ORDEN', 1, 0, 'C');
        $this->Cell(140, 8, 'OBSERVACIONES', 1, 1, 'C');
    }
    
    // Pie de página personalizado
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

    // Función para escribir una fila de la tabla
    function WriteRow($data, $numero) {
        $height = 8; // Altura fija para todas las filas
        $border = 1; // Borde visible
        
        // Guardar posición inicial
        $startX = $this->GetX();
        $startY = $this->GetY();
        
        // Número de registro
        $this->Cell(15, $height, $numero, $border, 0, 'C');
        
        // TAG
        $this->Cell(25, $height, $data['Tag_Number'], $border, 0, 'L');
        
        // FECHA
        $fecha = !empty($data['fecha']) ? date('d/m/Y', strtotime($data['fecha'])) : '';
        $this->Cell(25, $height, $fecha, $border, 0, 'C');
        
        // MANTENIMIENTO
        $this->Cell(40, $height, $data['mantenimiento'], $border, 0, 'L');
        
        // ORDEN
        $this->Cell(35, $height, $data['orden'], $border, 0, 'C');
        
        // OBSERVACIONES (ajustar texto para que quede en la celda)
        $this->Cell(140, $height, substr($data['observaciones'], 0, 100), $border, 1, 'L');
        
        // Actualizar posición Y para la siguiente fila
        $this->SetY($startY + $height);
    }
}

$searchTerm = $_GET['searchTerm'] ?? '';
$searchType = $_GET['searchType'] ?? 'tag'; // Valor por defecto
$searchFechaInicio = $_GET['searchFechaInicio'] ?? '';
$searchFechaFin = $_GET['searchFechaFin'] ?? '';

// Construir consulta SQL base con filtro de planta del usuario
$query = "SELECT m.Tag_Number, m.fecha, m.mantenimiento, m.orden, m.observaciones, 
                 u.descripcion as ubicacion
          FROM mantenimiento m
          JOIN equipos e ON m.Tag_Number = e.Tag_Number
          JOIN ubicacion u ON e.id_ubicacion = u.id_ubicacion
          WHERE m.mantenimiento = 'Preventivo'
          AND u.id_planta = :id_planta";
$params = [':id_planta' => $id_planta_usuario];

// Aplicar filtros adicionales
if (!empty($searchTerm)) {
    if ($searchType === 'tag') {
        $query .= " AND m.Tag_Number LIKE :searchTerm";
        $params[':searchTerm'] = "%$searchTerm%";
    } elseif ($searchType === 'ubicacion') {
        $query .= " AND u.descripcion LIKE :searchTerm";
        $params[':searchTerm'] = "%$searchTerm%";
    }
}
if (!empty($searchFechaInicio)) {
    if (!empty($searchFechaFin)) {
        // Rango de fechas
        $query .= " AND m.fecha BETWEEN :fechaInicio AND :fechaFin";
        $params[':fechaInicio'] = $searchFechaInicio;
        $params[':fechaFin'] = $searchFechaFin;
    } else {
        // Fecha única
        $query .= " AND m.fecha = :fechaInicio";
        $params[':fechaInicio'] = $searchFechaInicio;
    }
} elseif (!empty($searchFechaFin)) {
    // Solo fecha fin
    $query .= " AND m.fecha = :fechaFin";
    $params[':fechaFin'] = $searchFechaFin;
}

// Ordenar por fecha descendente (igual que en la tabla)
$query .= " ORDER BY m.fecha DESC";

// Ejecutar consulta
$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$intervenciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Crear PDF en orientación horizontal
$pdf = new PDF('L'); // 'L' para landscape (horizontal)
$pdf->AddPage();
$pdf->SetFont('Arial', '', 8);





// Agregar datos de las intervenciones
$numeroRegistro = 1;
foreach ($intervenciones as $row) {
    // Verificar espacio para el footer (ajustado para horizontal)
    if($pdf->GetY() > 170) { // Menor altura en horizontal
        $pdf->AddPage();
    }
    
    $pdf->WriteRow($row, $numeroRegistro);
    $numeroRegistro++;
}

// Si no hay intervenciones, mostrar mensaje
if (empty($intervenciones)) {
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'No se encontraron registros con los filtros aplicados', 0, 1, 'C');
}

// Mostrar footer después de los registros
$pdf->CustomFooter();

$pdf->Output('Reporte_Mantenimiento_Preventivo.pdf', 'I');
?>
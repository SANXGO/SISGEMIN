<?php
require('../../FPDF_master/fpdf.php');
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/../../includes/audit.php';

// Iniciar sesión primero
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['id_usuario'])) {
    die('Usuario no autenticado');
}

$id_usuario = $_SESSION['id_usuario'];

// Consulta para obtener el id_planta del usuario
try {
    $stmt = $pdo->prepare("SELECT id_planta FROM usuario WHERE id_usuario = ?");
    $stmt->execute([$id_usuario]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        die('Usuario no encontrado');
    }

    $id_planta_usuario = $usuario['id_planta'];
} catch (PDOException $e) {
    die('Error al consultar la base de datos: ' . $e->getMessage());
}

logModuleAccess('Reporte de registros de Mantenimientos Maestros');

class PDF extends FPDF {
    private $showFooter = false;
    
    // Cabecera de página
    function Header() {
        $this->Image('../../Pequiven.png', 10, 10, 30);
        
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'REPORTE MAESTRO DE MANTENIMIENTOS', 0, 1, 'C');
        $this->Ln(10);
        
        // Mostrar criterios de búsqueda si existen
        $searchTerm = $_GET['searchTerm'] ?? '';
        $searchType = $_GET['searchType'] ?? '';
        $searchFechaInicio = $_GET['searchFechaInicio'] ?? '';
        $searchFechaFin = $_GET['searchFechaFin'] ?? '';
       
        $this->Ln(5);

        $this->SetFont('Arial', 'B', 8);
        // Encabezados de la tabla
        $this->Cell(10, 8, '#', 1, 0, 'C');
        $this->Cell(25, 8, 'TAG', 1, 0, 'C');
        $this->Cell(22, 8, 'FECHA', 1, 0, 'C');
        $this->Cell(78, 8, 'TIPO / MANTENIMIENTO', 1, 0, 'C');
        $this->Cell(145, 8, 'OBSERVACIONES / DESCRIPCION', 1, 1, 'C');
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
        $height = 8; // Altura fija
        $border = 1; // Borde visible
        
        // Guardar posición inicial
        $startX = $this->GetX();
        $startY = $this->GetY();
        
        // Verificar si hay espacio suficiente en la página
        if($startY + $height > 170) {
            $this->AddPage();
            $startY = $this->GetY();
        }
        
        // N°
        $this->Cell(10, $height, $numero, $border, 0, 'C');
        
        // TAG
        $this->Cell(25, $height, $data['Tag_Number'], $border, 0, 'L');
        
        // FECHA
        $fecha = !empty($data['fecha']) ? date('d/m/Y', strtotime($data['fecha'])) : '';
        $this->Cell(22, $height, $fecha, $border, 0, 'C');
        
        // TIPO / MANTENIMIENTO (unificado)
        $tipoMantenimiento = $data['tipo_mantenimiento'] ;
        $this->Cell(78, $height, substr($tipoMantenimiento, 0, 60), $border, 0, 'L');
        
        // OBSERVACIONES/DESCRIPCIÓN
        $obsText = ($data['tipo_mantenimiento'] === 'Preventivo') ? $data['observaciones'] : $data['descripcion'];
        $this->Cell(145, $height, substr($obsText, 0, 150), $border, 1, 'L');
    }
}

// Obtener parámetros de búsqueda
$searchTerm = $_GET['searchTerm'] ?? '';
$searchType = $_GET['searchType'] ?? 'tag';
$searchFechaInicio = $_GET['searchFechaInicio'] ?? '';
$searchFechaFin = $_GET['searchFechaFin'] ?? '';

// Construir consultas SQL con named parameters
$query1 = "SELECT 
            m.id_mantenimiento as id,
            m.Tag_Number,
            m.fecha,
            'Preventivo' as tipo_mantenimiento,
            m.mantenimiento,
            m.observaciones,
            NULL as descripcion,
            u.descripcion as ubicacion
          FROM mantenimiento m
          JOIN equipos e ON m.Tag_Number = e.Tag_Number
          JOIN ubicacion u ON e.id_ubicacion = u.id_ubicacion
          WHERE u.id_planta = :id_planta";
          
$query2 = "SELECT 
            i.id_inter as id,
            i.Tag_Number,
            i.fecha,
            'Correctivo' as tipo_mantenimiento,
            i.mantenimiento_correctivo as mantenimiento,
            NULL as observaciones,
            i.descripcion,
            u.descripcion as ubicacion
          FROM intervencion i
          JOIN equipos e ON i.Tag_Number = e.Tag_Number
          JOIN ubicacion u ON e.id_ubicacion = u.id_ubicacion
          WHERE u.id_planta = :id_planta2";

// Parámetros base
$params = [
    ':id_planta' => $id_planta_usuario,
    ':id_planta2' => $id_planta_usuario
];

// Aplicar filtros adicionales
if (!empty($searchTerm)) {
    if ($searchType === 'tag') {
        $query1 .= " AND m.Tag_Number LIKE :searchTerm";
        $query2 .= " AND i.Tag_Number LIKE :searchTerm2";
        $params[':searchTerm'] = "%$searchTerm%";
        $params[':searchTerm2'] = "%$searchTerm%";
    } else {
        $query1 .= " AND u.descripcion LIKE :searchTerm";
        $query2 .= " AND u.descripcion LIKE :searchTerm2";
        $params[':searchTerm'] = "%$searchTerm%";
        $params[':searchTerm2'] = "%$searchTerm%";
    }
}

if (!empty($searchFechaInicio)) {
    $query1 .= " AND m.fecha >= :fechaInicio";
    $query2 .= " AND i.fecha >= :fechaInicio2";
    $params[':fechaInicio'] = $searchFechaInicio;
    $params[':fechaInicio2'] = $searchFechaInicio;
}

if (!empty($searchFechaFin)) {
    $query1 .= " AND m.fecha <= :fechaFin";
    $query2 .= " AND i.fecha <= :fechaFin2";
    $params[':fechaFin'] = $searchFechaFin;
    $params[':fechaFin2'] = $searchFechaFin;
}

// Combinar consultas
$finalQuery = "($query1) UNION ALL ($query2) ORDER BY fecha DESC";

// Ejecutar consulta combinada
try {
    $stmt = $pdo->prepare($finalQuery);
    
    // Vincular parámetros
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $registros = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Error al ejecutar la consulta: ' . $e->getMessage());
}

// Crear PDF
$pdf = new PDF('L');
$pdf->AddPage();
$pdf->SetFont('Arial', '', 8);

// Agregar datos de los registros
$numeroRegistro = 1;
foreach ($registros as $row) {
    $pdf->WriteRow($row, $numeroRegistro);
    $numeroRegistro++;
}

// Si no hay registros, mostrar mensaje
if (empty($registros)) {
    $pdf->Cell(0, 10, 'No se encontraron registros con los criterios especificados', 0, 1, 'C');
}

// Mostrar footer
$pdf->CustomFooter();

$pdf->Output('Reporte_Maestro_Mantenimientos.pdf', 'I');
?>
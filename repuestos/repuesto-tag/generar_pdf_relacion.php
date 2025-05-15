<?php
require('../../FPDF_master/fpdf.php');
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/../../includes/audit.php';

// Obtener el id_usuario de la sesión
session_start();
$id_usuario = $_SESSION['id_usuario'] ?? null;

if (!$id_usuario) {
    die('Usuario no autenticado');
}

// Consulta para obtener el id_planta del usuario
$stmt = $pdo->prepare("SELECT id_planta FROM usuario WHERE id_usuario = ?");
$stmt->execute([$id_usuario]);
$usuario = $stmt->fetch();

if (!$usuario) {
    die('Usuario no encontrado');
}

$id_planta_usuario = $usuario['id_planta'];


logModuleAccess('Reporte de registros de Repuestos-tag');

class PDF extends FPDF {
    private $title = 'RELACIONES REPUESTOS-EQUIPOS';
    private $planta_nombre = '';
    
    function setPlantaNombre($nombre) {
        $this->planta_nombre = $nombre;
    }
    
    // Cabecera de página
    function Header() {
        // Logo
        $this->Image('../../Pequiven.png', 10, 10, 30);
        
        // Título
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, $this->title, 0, 1, 'C');
        
        // Nombre de la planta
        if (!empty($this->planta_nombre)) {
            $this->SetFont('Arial', 'B', 12);
            $this->Cell(0, 8, 'Planta: ' . $this->planta_nombre, 0, 1, 'C');
        }
        
        // Fecha de generación
        $this->SetFont('Arial', '', 10);
        $this->Cell(0, 8, 'Generado: ' . date('d/m/Y H:i'), 0, 1, 'R');
        $this->Ln(2);
        
        // Encabezados de la tabla
        $this->SetFont('Arial', 'B', 9);
        $this->Cell(15, 8, 'ID', 1, 0, 'C');
        $this->Cell(60, 8, 'DESCRIPCION REPUESTO', 1, 0, 'C');
        $this->Cell(40, 8, 'PARTE REAL', 1, 0, 'C');
        $this->Cell(40, 8, 'TAG EQUIPO', 1, 0, 'C');
        $this->Cell(80, 8, 'TIPO INSTRUMENTO', 1, 1, 'C');
    }
    
    // Pie de página
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

// Obtener parámetros de búsqueda si existen
$searchDescripcion = $_GET['searchDescripcion'] ?? '';
$searchTag = $_GET['searchTag'] ?? '';

// Obtener el nombre de la planta para mostrarlo en el PDF
$stmtPlanta = $pdo->prepare("SELECT nombres FROM planta WHERE id_planta = ?");
$stmtPlanta->execute([$id_planta_usuario]);
$planta = $stmtPlanta->fetch();
$planta_nombre = $planta['nombre'] ?? '';

// Construir consulta SQL base con filtro por planta
$query = "
    SELECT 
        er.id_puente,
        r.id_repuestos,
        r.descripcion,
        r.real_part,
        e.Tag_Number,
        e.Instrument_Type_Desc
    FROM 
        equipo_repuesto er
    JOIN 
        repuestos r ON er.id_repuesto = r.id_repuestos
    JOIN 
        equipos e ON er.id_equipo = e.Tag_Number
    JOIN
        ubicacion u ON e.id_ubicacion = u.id_ubicacion
    WHERE
        u.id_planta = :id_planta
";

$params = [':id_planta' => $id_planta_usuario];

// Agregar condiciones de búsqueda si existen
if (!empty($searchDescripcion)) {
    $query .= " AND r.descripcion LIKE :searchDescripcion";
    $params[':searchDescripcion'] = "%$searchDescripcion%";
} elseif (!empty($searchTag)) {
    $query .= " AND e.Tag_Number LIKE :searchTag";
    $params[':searchTag'] = "%$searchTag%";
}

// Ordenar por descripción y tag
$query .= " ORDER BY r.descripcion, e.Tag_Number";

// Ejecutar consulta
$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$relaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Crear PDF en orientación horizontal
$pdf = new PDF('L'); // 'L' para landscape (horizontal)
$pdf->AliasNbPages();
$pdf->setPlantaNombre($planta_nombre);
$pdf->AddPage();
$pdf->SetFont('Arial', '', 8);

// Agregar datos de las relaciones
foreach ($relaciones as $relacion) {
    // Verificar espacio para el footer
    if($pdf->GetY() > 170) {
        $pdf->AddPage();
    }
    
    $pdf->Cell(15, 8, $relacion['id_puente'], 1, 0, 'C');
    $pdf->Cell(60, 8, $relacion['descripcion'], 1, 0, 'L');
    $pdf->Cell(40, 8, $relacion['real_part'], 1, 0, 'L');
    $pdf->Cell(40, 8, $relacion['Tag_Number'], 1, 0, 'L');
    $pdf->Cell(80, 8, $relacion['Instrument_Type_Desc'], 1, 1, 'L');
}

// Si no hay relaciones, mostrar mensaje
if (empty($relaciones)) {
    $pdf->Cell(0, 10, 'No se encontraron relaciones repuestos-equipos para esta planta', 0, 1, 'C');
}

// Salida del PDF
$pdf->Output('Relaciones_Repuestos_Equipos_' . $planta_nombre . '.pdf', 'I');
?>
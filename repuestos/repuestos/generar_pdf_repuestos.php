<?php
require('../../FPDF_master/fpdf.php');
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/../../includes/audit.php';



logModuleAccess('Reporte de registros de Repuestos');

class PDF extends FPDF {
    private $showFooter = false;
    
    // Cabecera de página
    function Header() {
        $this->Image('../../Pequiven.png', 10, 10, 30);
        
        $this->SetFont('Arial', 'B', 16);
        $this->Cell(0, 10, 'INVENTARIO DE REPUESTOS', 0, 1, 'C');
        $this->Ln(10);
        
        $this->SetFont('Arial', 'B', 8);
        // Encabezados de la tabla con tamaños ajustados
        $this->Cell(15, 8, '#', 1, 0, 'C');
        $this->Cell(60, 8, 'FABRICANTE', 1, 0, 'C');
        $this->Cell(80, 8, 'DESCRIPCION', 1, 0, 'C');
        $this->Cell(60, 8, 'REAL PART', 1, 0, 'C');
        $this->Cell(60, 8, 'SECTIONAL DRAWING', 1, 1, 'C');
    }
    
    // Pie de página personalizado
    
    

}

// Obtener parámetros de búsqueda si existen
$searchDescripcion = $_GET['search'] ?? '';

// Construir consulta SQL con JOIN a fabricantes
$query = "
    SELECT r.id_repuestos, f.nombres as fabricante, r.descripcion, r.real_part, r.sectional_drawing 
    FROM repuestos r
    JOIN fabricantes f ON r.id_fabricante = f.id_fabricante
";

// Agregar condición de búsqueda si existe
if (!empty($searchDescripcion)) {
    $query .= " WHERE r.descripcion LIKE :searchDescripcion";
    $params[':searchDescripcion'] = "%$searchDescripcion%";
}

// Ordenar por ID de repuesto
$query .= " ORDER BY r.id_repuestos";

// Ejecutar consulta
$stmt = $pdo->prepare($query);
if (!empty($searchDescripcion)) {
    $stmt->bindValue(':searchDescripcion', "%$searchDescripcion%");
}
$stmt->execute();
$repuestos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Crear PDF en orientación horizontal
$pdf = new PDF('L'); // 'L' para landscape (horizontal)
$pdf->AddPage();
$pdf->SetFont('Arial', '', 8);

// Función para escribir una fila de la tabla
function WriteRow($pdf, $data, $isHeader = false) {
    $height = 8; // Altura base
    $border = 1; // Borde visible
    
    // Calcular altura necesaria para los campos multilínea
    $pdf->SetFont('Arial', '', 8);
    $descHeight = max($height, ceil($pdf->GetStringWidth($data['descripcion']) / 80) * $height);
    $realPartHeight = max($height, ceil($pdf->GetStringWidth($data['real_part']) / 60) * $height);
    $sectionalHeight = max($height, ceil($pdf->GetStringWidth($data['sectional_drawing']) / 60) * $height);
    
    $rowHeight = max($height, $descHeight, $realPartHeight, $sectionalHeight);
    
    // Guardar posición inicial
    $startX = $pdf->GetX();
    $startY = $pdf->GetY();
    
    // ID
    $pdf->Cell(15, $rowHeight, $data['id_repuestos'], $border, 0, 'C');
    
    // FABRICANTE
    $pdf->Cell(60, $rowHeight, $data['fabricante'], $border, 0, 'L');
    
    // DESCRIPCIÓN (multilínea)
    $pdf->MultiCell(80, $height, $data['descripcion'], $border, 'L', false);
    $pdf->SetXY($startX + 15 + 60 + 80, $startY);
    
    // REAL PART (multilínea)
    $pdf->MultiCell(60, $height, $data['real_part'], $border, 'L', false);
    $pdf->SetXY($startX + 15 + 60 + 80 + 60, $startY);
    
    // SECTIONAL DRAWING (multilínea)
    $pdf->MultiCell(60, $height, $data['sectional_drawing'], $border, 'L', false);
    $pdf->SetXY($startX + 15 + 60 + 80 + 60 + 60, $startY);
    
    // Actualizar posición Y para la siguiente fila
    $pdf->SetY($startY + $rowHeight);
}

// Agregar datos de los repuestos
foreach ($repuestos as $row) {
    // Verificar espacio para el footer (ajustado para horizontal)
    if($pdf->GetY() > 170) { // Menor altura en horizontal
        $pdf->AddPage();
    }
    
    $rowData = [
        'id_repuestos' => $row['id_repuestos'],
        'fabricante' => $row['fabricante'],
        'descripcion' => $row['descripcion'],
        'real_part' => $row['real_part'],
        'sectional_drawing' => $row['sectional_drawing']
    ];
    
    WriteRow($pdf, $rowData);
}

// Si no hay repuestos, mostrar tabla vacía con 10 filas
if (empty($repuestos)) {
    for ($i = 1; $i <= 10; $i++) {
        $emptyData = [
            'id_repuestos' => '',
            'fabricante' => '',
            'descripcion' => '',
            'real_part' => '',
            'sectional_drawing' => ''
        ];
        WriteRow($pdf, $emptyData);
    }
}

// Mostrar footer después de los registros


$pdf->Output('Reporte_Repuestos.pdf', 'I');
?>
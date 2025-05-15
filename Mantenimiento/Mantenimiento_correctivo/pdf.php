<?php
require('../../FPDF_master/fpdf.php'); // Asegúrate de incluir la ruta correcta a FPDF
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/../../includes/audit.php'; 

session_start();

header('Content-Type: application/json');

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'error' => 'no_autenticado']);
    exit;
}

// Obtener el ID del registro desde la URL
if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Obtener el registro de mantenimiento específico
    $query = "SELECT * FROM intervencion WHERE id_inter = :id  ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $id]);
    $intervencion = $stmt->fetch(PDO::FETCH_ASSOC);


    logViewRecord('Reporte de ficha Mantenimiento Correctivo', $id, $intervencion);



    if ($intervencion) {
        // Crear el PDF en orientación horizontal
        $pdf = new FPDF('L'); // 'L' para orientación horizontal
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);

        // Agregar el logo
        $logo = '../../Pequiven.png'; // Cambia esto por la ruta de tu imagen
        if (file_exists($logo)) {
            $pdf->Image($logo, 15, 15, 40); // (x, y, ancho)
        } else {
            $pdf->Cell(0, 10, 'Logo no encontrado', 0, 1, 'C');
        }

        // Título del reporte
        $pdf->Cell(0, 10, 'Reporte de Mantenimiento Correctivo', 0, 1, 'C');
        $pdf->Ln(10); // Espacio después del título

        // Encabezado del reporte
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Ln(5); // Espacio después del encabezado

        // Datos del mantenimiento
        $pdf->SetFont('Arial', '', 10);

        // Calcular el margen izquierdo para centrar el contenido
        $ancho_total = 230; // Ancho total de la tabla
        $margen_izquierdo = (297 - $ancho_total) / 2; // 297 es el ancho de una página A4 en horizontal
        $pdf->SetX($margen_izquierdo); // Establecer la posición horizontal

        // Color de sombreado (gris claro)
        $pdf->SetFillColor(230, 230, 230); // RGB: Gris claro

        // Fecha
        $pdf->Cell(40, 10, 'FECHA', 1, 0, 'C', true); // 'true' para activar el relleno
        $pdf->Cell(25, 10, 'DD', 1, 0, 'C', true);
        $pdf->Cell(25, 10, 'MM', 1, 0, 'C', true);
        $pdf->Cell(25, 10, 'AA', 1, 0, 'C', true);
        $pdf->Cell(110, 10, 'TAG', 1, 1, 'C', true);



       
        


        $fecha = explode('-', $intervencion['fecha']);
        $pdf->SetX($margen_izquierdo); // Establecer la posición horizontal
        $pdf->Cell(40, 10, '', 1, 0, 'C');
        $pdf->Cell(25, 10, $fecha[2], 1, 0, 'C');
        $pdf->Cell(25, 10, $fecha[1], 1, 0, 'C');
        $pdf->Cell(25, 10, $fecha[0], 1, 0, 'C');
        $pdf->Cell(110, 10, $intervencion['Tag_Number'], 1, 1, 'C');

        $pdf->SetX($margen_izquierdo);
        $pdf->Cell(225, 10, 'DETALLES', 1, 1, 'C', true);

        $pdf->SetX($margen_izquierdo);
        $pdf->Cell(40, 10, 'MANTENIMIENTO', 1, 0, 'C', true);
        $pdf->Cell(50, 10, $intervencion['mantenimiento_correctivo'], 1, 0, 'C');
        
        $pdf->Cell(25, 10, 'REPUESTO', 1, 0, 'C', true);
        $pdf->Cell(110, 10, $intervencion['repuestos'], 1, 1, 'C');




        // PLANTA, INSTALACION y ORDEN
        $pdf->SetX($margen_izquierdo); // Establecer la posición horizontal
        
        $pdf->Cell(40, 10, 'MATERIALES', 1, 0, 'C', true);
        $pdf->Cell(50, 10, $intervencion['materiales'], 1, 0, 'C');
        $pdf->Cell(25, 10, 'TIEMPO', 1, 0, 'C', true);
        $pdf->Cell(25, 10, $intervencion['tiempo'], 1, 0, 'C');
        $pdf->Cell(35, 10, 'RESPONSABLE', 1, 0, 'C', true);
        $pdf->Cell(50, 10, $intervencion['responsable'], 1, 1, 'C');





// ... (código anterior permanece igual)

$pdf->SetX($margen_izquierdo); 
$pdf->Cell(225, 10, 'DESCRIPCION', 1, 1, 'C', true);


$pdf->SetX($margen_izquierdo); 
// Usamos MultiCell para texto largo (ancho 175, altura dinámica)
$pdf->MultiCell(225, 10, $intervencion['descripcion'], 1, 'C');

// ... (resto del código)

        // Espacio entre registros
        $pdf->Ln(10);

        // Pie de página
        $pdf->Ln(20); // Espacio antes del pie de página
        $pdf->SetFont('Arial', 'I', 10);
        $pdf->Cell(0, 10, 'Generado el: ' . date('Y-m-d H:i:s'), 0, 1, 'C');

        // Salida del PDF
        $pdf->Output(); // 'D' para descargar directamente
    } else {
        echo "No se encontró el registro de mantenimiento.";
    }
} else {
    echo "ID de registro no proporcionado.";
}
?>
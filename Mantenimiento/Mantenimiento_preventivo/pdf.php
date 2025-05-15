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
    $query = "SELECT * FROM mantenimiento WHERE id_mantenimiento = :id  ";
    $stmt = $pdo->prepare($query);
    $stmt->execute(['id' => $id]);
    $mantenimiento = $stmt->fetch(PDO::FETCH_ASSOC);



    logViewRecord('Reporte de ficha Mantenimiento Preventivo', $id, $mantenimiento);

    if ($mantenimiento) {
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
        $pdf->Cell(0, 10, 'Reporte de Mantenimiento', 0, 1, 'C');
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
        $pdf->Cell(35, 10, 'FECHA', 1, 0, 'C', true); // 'true' para activar el relleno
        $pdf->Cell(20, 10, 'DD', 1, 0, 'C', true);
        $pdf->Cell(20, 10, 'MM', 1, 0, 'C', true);
        $pdf->Cell(20, 10, 'AA', 1, 0, 'C', true);
        $pdf->Cell(70, 10, 'TAG', 1, 0, 'C', true);
        $pdf->Cell(70, 10, 'ORDEN', 1, 1, 'C', true);

        $fecha = explode('-', $mantenimiento['fecha']);
        $pdf->SetX($margen_izquierdo); // Establecer la posición horizontal
        $pdf->Cell(35, 10, '', 1, 0, 'C');
        $pdf->Cell(20, 10, $fecha[2], 1, 0, 'C');
        $pdf->Cell(20, 10, $fecha[1], 1, 0, 'C');
        $pdf->Cell(20, 10, $fecha[0], 1, 0, 'C');
        $pdf->Cell(70, 10, $mantenimiento['Tag_Number'], 1, 0, 'C');
        $pdf->Cell(70, 10, $mantenimiento['orden'], 1, 1, 'C');

        $pdf->SetX($margen_izquierdo);
        $pdf->Cell(235, 10, 'DETALLES', 1,1, 'C', true);
        

        // PLANTA, INSTALACION y ORDEN
        $pdf->SetX($margen_izquierdo); // Establecer la posición horizontal
        $pdf->Cell(35, 10, 'PLANTA', 1, 0, 'C', true);
        $pdf->Cell(60, 10, $mantenimiento['planta'], 1, 0, 'C');
        $pdf->Cell(45, 10, 'INSTRUMENTO', 1, 0, 'C', true);
        $pdf->Cell(95, 10, $mantenimiento['id_tipo'], 1, 1, 'C');


        $pdf->SetX($margen_izquierdo); // Establecer la posición horizontal
        $pdf->Cell(35, 10, 'INSTALACION', 1, 0, 'C', true);
        $pdf->Cell(60, 10, $mantenimiento['instalacion'], 1, 0, 'C');


        $pdf->Cell(45, 10, 'MANTENIMIENTO', 1, 0, 'C', true);
        $pdf->Cell(95, 10, $mantenimiento['mantenimiento'], 1, 1, 'C');





        // MODELO y SERIAL
        $pdf->SetX($margen_izquierdo); // Establecer la posición horizontal
        $pdf->Cell(35, 10, 'MODELO', 1, 0, 'C', true);
        $pdf->Cell(60, 10, $mantenimiento['modelo'], 1, 0, 'C');
        $pdf->Cell(45, 10, 'MEDICION ', 1, 0, 'C', true);
        $pdf->Cell(95, 10, '', 1, 1, 'C');





        
        
        
        

        // MEDICION ELECTRICA, 24Vdc y FASE TIERRA
        $pdf->SetX($margen_izquierdo); // Establecer la posición horizontal
        $pdf->Cell(35, 10, 'SERIAL', 1, 0, 'C', true);
        $pdf->Cell(60, 10, $mantenimiento['serial'], 1, 0, 'C');
        $pdf->Cell(45, 10, '24Vdc', 1, 0, 'C', true);
        $pdf->Cell(95, 10, $mantenimiento['24vdc'], 1, 1, 'C');

        // OBSERVACIONES
        $pdf->SetX($margen_izquierdo); // Establecer la posición horizontal
        $pdf->Cell(235, 10, 'OBSERVACIONES', 1,1, 'C', true);
        $pdf->SetX($margen_izquierdo);
        $pdf->Cell(235, 10, $mantenimiento['observaciones'], 1, 1, 'C');

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
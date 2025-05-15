<?php
require('../FPDF_master/fpdf.php');
require_once __DIR__ . '/../includes/conexion.php';
require_once __DIR__ . '/../includes/audit.php';


// Iniciar sesión para obtener información del usuario
session_start();

header('Content-Type: application/json');

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['success' => false, 'error' => 'no_autenticado']);
    exit;
}



class PDF extends FPDF {
    private $showFooter = false;
    private $generatedBy = '';
    
    // Cabecera de página
    function Header() {
        $this->Image('../Pequiven.png', 10, 10, 30);
        
        $this->SetFont('Arial', 'B', 10);
        $this->Cell(0, 3, 'GERENCIA DE MANTENIMIENTO', 0, 1, 'C');
        $this->Cell(0, 10, 'LIBRO DE ACTIVIDADES DIARIAS', 0, 1, 'C');
        $this->Cell(0, 10, 'PQV-CM-MO-R-1H05', 0, 1, 'C');
        $this->Ln(4);
        
        $this->SetFont('Arial', 'B', 6);
        // Encabezados de la tabla con tamaños ajustados
        $this->Cell(5, 6, '#.', 1, 0, 'C');
        $this->Cell(18, 6, 'ORDEN', 1, 0, 'C');
        $this->Cell(18, 6, 'PERMISO', 1, 0, 'C');
        $this->Cell(15, 6, 'PLANTA', 1, 0, 'C');
        $this->Cell(20, 6, 'UBICACION', 1, 0, 'C');
        $this->Cell(20, 6, 'TAG', 1, 0, 'C');
        $this->Cell(60, 6, 'ACTIVIDAD', 1, 0, 'C');
        $this->Cell(22, 6, 'ESPECIALISTAS', 1, 0, 'C');
        $this->Cell(12, 6, 'TIEMPO', 1, 0, 'C');
        $this->Cell(30, 6, 'RECURSO', 1, 0, 'C');
        $this->Cell(16, 6, 'FECHA', 1, 0, 'C');
        $this->Cell(12, 6, 'AVANCE', 1, 0, 'C');
        $this->Cell(35, 6, 'OBSERVACION', 1, 1, 'C');
    }
    
    // Pie de página
    function Footer() {
        $this->SetY(-40); // Posición a 4 cm del final para dejar espacio para la información
        
        // Línea horizontal
        $this->Line(10, $this->GetY(), $this->GetPageWidth()-10, $this->GetY());
        $this->Ln(5);
        
// Guardar la posición Y actual para mantener el mismo nivel
$currentY = $this->GetY();

// Firma izquierda (Elaborado por)
$this->SetXY(20, $currentY); // Establecer posición X=20, Y=actual
$this->Cell(60, 5, 'FIRMA', 0, 0, 'C');
$this->SetXY(20, $currentY + 5); // Misma X, Y+5 para la línea siguiente
$this->Cell(60, 5, 'Elaborado Ejecutor Especialidad:_________________', 0, 0, 'C');

// Firma derecha (Revisado por)
$this->SetXY($this->GetPageWidth() - 80, $currentY); // Posición derecha, misma Y
$this->Cell(60, 5, 'FIRMA', 0, 0, 'C');
$this->SetXY($this->GetPageWidth() - 80, $currentY + 5); // Misma X derecha, Y+5
$this->Cell(60, 5, 'Revisado Supervisor/Especialidad:_________________', 0, 0, 'C');

// Avanzar el puntero después de ambas firmas
$this->SetY($currentY + 10);
        
        // Información del generador (siempre al final)
        $this->SetY(-20);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 5, $this->generatedBy, 0, 1, 'L');
    }
    
    // Método para establecer la información del generador
    function SetGeneratedBy($info) {
        $this->generatedBy = $info;
    }
    
    function MultiLineCell($width, $height, $text, $border = 0, $align = 'L') {
        $text = utf8_decode($text);
        $lines = $this->WordWrap($text, $width);
        
        // Limitar a 3 líneas máximo
        $lines = array_slice($lines, 0, 3);
        
        $x = $this->GetX();
        $y = $this->GetY();
        
        // Dibujar borde si es necesario
        if($border) {
            $this->Rect($x, $y, $width, $height);
        }
        
        // Calcular altura por línea con interlineado reducido
        $lineHeight = $height / 3; // Dividir la altura total entre 3 líneas
        $textHeight = $lineHeight * 0.8; // Reducir la altura del texto (80% del espacio)
        $padding = ($lineHeight - $textHeight) / 2; // Espacio arriba y abajo del texto
        
        // Escribir cada línea con menos espacio entre ellas
        foreach($lines as $i => $line) {
            $this->SetXY($x, $y + ($i * $lineHeight) + $padding);
            $this->Cell($width, $textHeight, trim($line), 0, 0, $align);
        }
        
        // Restaurar posición
        $this->SetXY($x + $width, $y);
    }
    
    // Función mejorada para ajustar texto en varias líneas
    function WordWrap($text, $maxWidth) {
        $lines = array();
        $text = str_replace("\r", "", $text);
        $paragraphs = explode("\n", $text);
        
        foreach($paragraphs as $paragraph) {
            $words = explode(' ', $paragraph);
            $currentLine = '';
            
            foreach($words as $word) {
                $testLine = ($currentLine == '') ? $word : $currentLine.' '.$word;
                
                if($this->GetStringWidth($testLine) <= $maxWidth) {
                    $currentLine = $testLine;
                } else {
                    if(!empty($currentLine)) {
                        $lines[] = $currentLine;
                    }
                    
                    // Manejar palabras muy largas que superan el ancho
                    while($this->GetStringWidth($word) > $maxWidth) {
                        // Cortar la palabra hasta que quepa
                        $part = '';
                        for($i = 0; $i < strlen($word); $i++) {
                            if($this->GetStringWidth($part.$word[$i]) < $maxWidth) {
                                $part .= $word[$i];
                            } else {
                                break;
                            }
                        }
                        $lines[] = $part;
                        $word = substr($word, strlen($part));
                    }
                    
                    $currentLine = $word;
                }
            }
            
            if(!empty($currentLine)) {
                $lines[] = $currentLine;
            }
        }
        
        return $lines;
    }
}

// Obtener información de la planta del usuario actual
$id_usuario = $_SESSION['id_usuario'];
$stmt = $pdo->prepare("
    SELECT u.nombre, u.id_planta, p.nombres as nombre_plana 
    FROM usuario u
    JOIN planta p ON u.id_planta = p.id_planta
    WHERE u.id_usuario = ?
");
$stmt->execute([$id_usuario]);
$usuario_actual = $stmt->fetch();

if (!$usuario_actual) {
    die('Usuario no encontrado');
}

// Obtener parámetros de búsqueda
$orden = $_GET['orden'] ?? '';
$fecha = $_GET['fecha'] ?? '';
$general = $_GET['general'] ?? '';

// Construir consulta SQL base con filtro por planta del usuario
$query = "SELECT * FROM actividades WHERE planta = :planta";
$params = [':planta' => $usuario_actual['nombre_plana']];

// Agregar filtros adicionales según los parámetros de búsqueda
if (!empty($orden)) {
    $query .= " AND orden LIKE :orden";
    $params[':orden'] = "%$orden%";
}

if (!empty($fecha)) {
    $query .= " AND fecha = :fecha";
    $params[':fecha'] = $fecha;
}

if (!empty($general)) {
    $query .= " AND (
        orden LIKE :general OR 
        num_permiso LIKE :general OR 
        planta LIKE :general OR 
        ubicacion LIKE :general OR 
        tag_number LIKE :general OR 
        actividad LIKE :general OR 
        especialistas LIKE :general OR 
        recurso_apoyo LIKE :general
    )";
    $params[':general'] = "%$general%";
}

// Ordenar por ID de actividad descendente
$query .= " ORDER BY id_actividad DESC";

// Ejecutar consulta
$stmt = $pdo->prepare($query);

foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

$stmt->execute();
$actividades = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Crear PDF en orientación horizontal
$pdf = new PDF('L');
$pdf->AddPage();
$pdf->SetFont('Arial', '', 7);

// Establecer información del generador
$pdf->SetGeneratedBy('Generado por: ' . $usuario_actual['nombre'] . ' | Planta: ' . $usuario_actual['nombre_plana'] . ' | Fecha: ' . date('d/m/Y H:i:s'));

// Altura fija para cada registro (3 filas)
$fixedCellHeight = 15;
$contador = 1;

// Agregar datos de las actividades
if (!empty($actividades)) {
    foreach ($actividades as $row) {
        // Verificar espacio para el footer
        if($pdf->GetY() + $fixedCellHeight > 190) {
            $pdf->AddPage('L');
        }
        
        // Guardar posición Y inicial
        $startY = $pdf->GetY();
        
        // No. de registro
        $pdf->Cell(5, $fixedCellHeight, $contador++, 1, 0, 'C');
        
        // ORDEN
        $pdf->MultiLineCell(18, $fixedCellHeight, $row['orden'], 1, 'L');
        
        // PERMISO
        $pdf->MultiLineCell(18, $fixedCellHeight, $row['num_permiso'], 1, 'L');
        
        // PLANTA
        $pdf->MultiLineCell(15, $fixedCellHeight, $row['planta'], 1, 'L');
        
        // UBICACION
        $pdf->MultiLineCell(20, $fixedCellHeight, $row['ubicacion'], 1, 'L');
        
        // TAG
        $pdf->MultiLineCell(20, $fixedCellHeight, $row['tag_number'], 1, 'L');
        
        // ACTIVIDAD (texto largo)
        $pdf->MultiLineCell(60, $fixedCellHeight, $row['actividad'], 1, 'L');
        
        // ESPECIALISTAS
        $pdf->MultiLineCell(22, $fixedCellHeight, $row['especialistas'], 1, 'L');
        
        // TIEMPO
        $pdf->MultiLineCell(12, $fixedCellHeight, $row['tiempo'], 1, 'L');
        
        // RECURSO
        $pdf->MultiLineCell(30, $fixedCellHeight, $row['recurso_apoyo'], 1, 'L');
        
        // FECHA
        $pdf->MultiLineCell(16, $fixedCellHeight, $row['fecha'], 1, 'L');
        
        // AVANCE
        $pdf->MultiLineCell(12, $fixedCellHeight, $row['avance'], 1, 'L');
        
        // OBSERVACION (texto largo)
        $pdf->MultiLineCell(35, $fixedCellHeight, $row['observacion'], 1, 'L');
        
        // Mover a la siguiente línea
        $pdf->SetXY(10, $startY + $fixedCellHeight);
    }
} else {
    // Mostrar mensaje cuando no hay registros
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, 'No se encontraron actividades registradas', 0, 1, 'C');
    $pdf->SetFont('Arial', '', 7);
}

logModuleAccess('reporte de actividades');

$pdf->Output('Reporte_Actividades_' . $usuario_actual['nombre_plana'] . '_' . date('Ymd_His') . '.pdf', 'I');
?>
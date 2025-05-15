<?php
require_once __DIR__ . '/../includes/conexion.php';

if (isset($_GET['tag_number'])) {
    $tagNumber = $_GET['tag_number'];
    $stmt = $pdo->prepare("SELECT * FROM archivos_pdf WHERE Tag_Number = ? ORDER BY id DESC");
    $stmt->execute([$tagNumber]);
    $pdfs = $stmt->fetchAll();

    if (count($pdfs) > 0) {
        foreach ($pdfs as $pdf) {
            $fileName = basename($pdf['ruta_archivo']);
            echo "<div class='pdf-item'>
                    <a href='{$pdf['ruta_archivo']}' target='_blank'><i class='bi bi-file-earmark-pdf me-2'></i>{$fileName}</a>
                    <button class='btn btn-danger btn-sm' onclick='eliminarPdf({$pdf['id']})'><i class='bi bi-trash'></i></button>
                  </div>";
        }
    } else {
        echo "<div class='alert alert-info'>No hay archivos PDF asociados a este equipo.</div>";
    }
} else {
    echo "<div class='alert alert-danger'>Tag Number no proporcionado.</div>";
}
?>
<?php
session_start();
require_once __DIR__ . '/../includes/conexion.php';
require_once __DIR__ . '/../includes/audit.php';







// Obtener parámetros de la URL
$id_manual = isset($_GET['id_manual']) ? intval($_GET['id_manual']) : 0;
$categoria = isset($_GET['categoria']) ? urldecode($_GET['categoria']) : '';




// Validar que el manual existe
$stmt_manual = $pdo->prepare("SELECT descripcion FROM manual WHERE id_manual = ?");
$stmt_manual->execute([$id_manual]);
$manual = $stmt_manual->fetch();

if (!$manual) {
    header("Location: index.php?tabla=manual");
    exit();
}

// Obtener documentos PDF asociados a este manual
$stmt_pdfs = $pdo->prepare("SELECT * FROM pdf_manual WHERE id_manual = ? ORDER BY titulo");
$stmt_pdfs->execute([$id_manual]);
$pdfs = $stmt_pdfs->fetchAll();



// Procesar envío del formulario para subir nuevo PDF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_pdf'])) {
    $titulo = trim($_POST['titulo']);
    logAuditAction('manual', 'Update', "inicio de subida de pdf $titulo", $_POST);

    $archivo = $_FILES['archivo_pdf'];
    
    // Validaciones
    if (empty($titulo)) {
        $error = "El título es requerido";
    } elseif (strlen($titulo) > 60) {
        $error = "El título no puede exceder los 60 caracteres";
    } elseif ($archivo['error'] !== UPLOAD_ERR_OK) {
        $error = "Error al subir el archivo: " . $archivo['error'];
    } elseif ($archivo['type'] !== 'application/pdf') {
        $error = "Solo se permiten archivos PDF";
    } else {
        // Crear directorio si no existe (usando el nombre de la categoría)
        $directorio = __DIR__ . '/../manual/' . preg_replace('/[^a-zA-Z0-9-_]/', '', $categoria);
        if (!file_exists($directorio)) {
            mkdir($directorio, 0755, true);
        }
        
        // Usar el nombre original del archivo (sin generar HASH único)
        $nombre_archivo = preg_replace('/[^a-zA-Z0-9-_\.]/', '', $archivo['name']);
        $ruta_archivo = $directorio . '/' . $nombre_archivo;
        
        // Verificar si el archivo ya existe
        if (file_exists($ruta_archivo)) {
            $error = "Ya existe un archivo con ese nombre. Por favor, renombre el archivo antes de subirlo.";
        } else {
            // Mover archivo subido
            if (move_uploaded_file($archivo['tmp_name'], $ruta_archivo)) {
                // Guardar en base de datos
                $ruta_relativa = 'manual/' . preg_replace('/[^a-zA-Z0-9-_]/', '', $categoria) . '/' . $nombre_archivo;
                $stmt_insert = $pdo->prepare("INSERT INTO pdf_manual (id_manual, archivo, titulo) VALUES (?, ?, ?)");


                if ($stmt_insert->execute([$id_manual, $ruta_relativa, $titulo])) {
                    // Mostrar mensaje de éxito en la URL para que se muestre la alerta al recargar

                    logAuditAction('manual', 'Update', "subido exitosamente $titulo", $_POST);

                    header("Location: ver_pdf.php?id_manual=$id_manual&categoria=" . urlencode($categoria) . "&subido=1");
                    exit();
                } else {
                    $error = "Error al guardar en la base de datos";
                    // Intentar eliminar el archivo subido
                    @unlink($ruta_archivo);
                }
            } else {
                $error = "Error al mover el archivo subido";
            }
        }
    }
}

// Procesar eliminación de PDF
if (isset($_GET['eliminar_pdf'])) {
    $id_pdf = intval($_GET['eliminar_pdf']);
    $directorio_base = __DIR__ . '/../';
    
    // Obtener información del PDF a eliminar
    $stmt_pdf = $pdo->prepare("SELECT archivo FROM pdf_manual WHERE id_pdfmanual = ? AND id_manual = ?");
    $stmt_pdf->execute([$id_pdf, $id_manual]);
    $pdf = $stmt_pdf->fetch();
    

    logAuditAction('manual', 'Update', "inicio de eliminacion de pdf $titulo", $_POST);

    if ($pdf) {
        try {
            $pdo->beginTransaction();
            
            // Eliminar registro de la base de datos
            $stmt_delete = $pdo->prepare("DELETE FROM pdf_manual WHERE id_pdfmanual = ?");
            $stmt_delete->execute([$id_pdf]);
            
            // Eliminar archivo físico
            $ruta_archivo = $directorio_base . $pdf['archivo'];
            $directorio_pdf = dirname($ruta_archivo);
            
            if (file_exists($ruta_archivo)) {
                unlink($ruta_archivo);
                
                // Verificar si el directorio está vacío para eliminarlo
                $archivos_en_directorio = glob($directorio_pdf . '/*');
                if (is_array($archivos_en_directorio) && count($archivos_en_directorio) === 0) {
                    rmdir($directorio_pdf);
                }
            }
            
            $pdo->commit();
            
            // Redirigir para evitar reenvío del formulario con mensaje de éxito
            logAuditAction('manual', 'Update', "fin de eliminacion de pdf $titulo", $_POST);

            header("Location: ver_pdf.php?id_manual=$id_manual&categoria=" . urlencode($categoria) . "&eliminado=1");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error al eliminar el PDF: " . $e->getMessage();
        }
    }
}

// Obtener documentos PDF asociados a este manual (actualizado)
$stmt_pdfs = $pdo->prepare("SELECT * FROM pdf_manual WHERE id_manual = ? ORDER BY titulo");
$stmt_pdfs->execute([$id_manual]);
$pdfs = $stmt_pdfs->fetchAll();
logViewRecord('manual', $id_manual, $categoria);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentos PDF - <?= htmlspecialchars($manual['descripcion']) ?></title>
    <link rel="icon" href="../favicon.png">

    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/bootstrap-icons.css" rel="stylesheet">
    <style>
        .bg-gradient-primary { background: linear-gradient( #b71513 100%); }
        
        .pdf-card {
            transition: transform 0.2s;
        }
        .pdf-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        #alertContainer {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1100;
        }
        .alert-auto-close {
            min-width: 300px;
            margin-bottom: 10px;
        }
        .was-validated .form-control:invalid, .was-validated .form-control:valid {
            background-image: none;
            padding-right: 0.75rem;
        }
    </style>
</head>
<body>
    <!-- Contenedor para alertas flotantes -->
    <div id="alertContainer"></div>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Documentos PDF: <?= htmlspecialchars($manual['descripcion']) ?></h2>
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#subirPdfModal">
                <i class="bi bi-upload"></i> Subir Manual
            </button>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-auto-close"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if (!empty($_GET['subido']) && $_GET['subido'] == '1'): ?>
            <div class="alert alert-success alert-auto-close">PDF subido exitosamente!</div>
        <?php endif; ?>
        
        <?php if (!empty($_GET['eliminado']) && $_GET['eliminado'] == '1'): ?>
            <div class="alert alert-success alert-auto-close">PDF eliminado exitosamente!</div>
        <?php endif; ?>
        
        <?php if (empty($pdfs)): ?>
            <div class="alert alert-info">No hay documentos PDF asociados a esta categoría.</div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($pdfs as $pdf): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card pdf-card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-file-earmark-pdf fs-1 text-danger"></i>
                                <h5 class="card-title mt-2"><?= htmlspecialchars($pdf['titulo']) ?></h5>
                            </div>
                            <div class="card-footer bg-transparent">
                                <div class="d-flex justify-content-between">
                                    <a href="../<?= htmlspecialchars($pdf['archivo']) ?>" 
                                       class="btn btn-sm btn-danger" >
                                        </i> Ver
                                    </a>
                                    <button onclick="confirmarEliminacion(<?= $pdf['id_pdfmanual'] ?>)" 
                                            class="btn btn-sm btn-danger">
                                        </i> Eliminar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <div class="mt-3">
            <a href="../index.php?tabla=manual" class="btn btn-secondary">
                 Volver a Categorías
            </a>
        </div>
    </div>

   <!-- Modal para Subir PDF -->
<div class="modal fade" id="subirPdfModal" tabindex="-1" aria-labelledby="subirPdfModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <!-- Encabezado del Modal -->
            <div class="modal-header bg-gradient-primary text-white">
                <h3 class="modal-title fw-bold" id="subirPdfModalLabel">
                    <i class="bi bi-file-earmark-pdf me-2"></i>
                    Subir nuevo PDF
                </h3>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Cuerpo del Modal -->
            <div class="modal-body p-4">
                <form id="formSubirPdf" method="POST" enctype="multipart/form-data" novalidate>
                    <input type="hidden" name="id_manual" value="<?= $id_manual ?>">
                    
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="titulo" name="titulo" 
                               maxlength="60" required
                               oninput="updateCharCounter(this.value, 'tituloCounter', 60)">
                        <label for="titulo">Título del documento <span class="text-danger">*</span></label>
                        <div class="text-end small text-muted">
                            <span id="tituloCounter">0</span>/60 caracteres
                        </div>
                        <div class="invalid-feedback">
                            El título es requerido y no puede exceder 60 caracteres
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="archivo_pdf" class="form-label fw-semibold mb-2">
                            <i class="bi bi-paperclip me-1"></i> Seleccionar archivo PDF <span class="text-danger">*</span>
                        </label>
                        <input type="file" class="form-control p-3 border-2" id="archivo_pdf" name="archivo_pdf" 
                               accept=".pdf" required>
                        <div class="invalid-feedback">
                            Por favor seleccione un archivo PDF válido
                        </div>
                        <div class="form-text mt-1">
                            <i class="bi bi-info-circle"></i> Tamaño máximo: 10MB | Solo archivos PDF
                        </div>
                    </div>
                    
                    <!-- Pie del Modal -->
                    <div class="modal-footer bg-light">
                        <button type="submit" class="btn btn-danger">
                             Subir PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
    // Auto-cierre de alertas después de 5 segundos
    const alerts = document.querySelectorAll('.alert-auto-close');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
    // Configurar validación del formulario
    const formSubirPdf = document.getElementById('formSubirPdf');
    if (formSubirPdf) {
        formSubirPdf.addEventListener('submit', function(event) {
            event.preventDefault();
            
            if (!this.checkValidity()) {
                event.stopPropagation();
                this.classList.add('was-validated');
                return;
            }
            
            // Enviar el formulario con AJAX
            const formData = new FormData(this);
            
            fetch('', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.text();
            })
            .then(() => {
                // Recargar la página para mostrar el nuevo PDF y la alerta de éxito
                window.location.href = `ver_pdf.php?id_manual=<?= $id_manual ?>&categoria=<?= urlencode($categoria) ?>&subido=1`;
            })
            .catch(error => {
                console.error('Error:', error);
                showFloatingAlert('Error en el servidor. Por favor verifica la consola para más detalles.', 'danger');
            });
        });
        
        // Validación en tiempo real
        formSubirPdf.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', function() {
                if (this.checkValidity()) {
                    this.classList.remove('is-invalid');
                } else {
                    this.classList.add('is-invalid');
                }
            });
        });
    }
    
    // Función para mostrar alertas flotantes
    function showFloatingAlert(message, type) {
        const alertContainer = document.getElementById('alertContainer');
        const alertId = 'alert-' + Date.now();
        
        const alertDiv = document.createElement('div');
        alertDiv.id = alertId;
        alertDiv.className = `alert alert-${type} alert-dismissible fade show alert-auto-close`;
        alertDiv.role = 'alert';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        alertContainer.appendChild(alertDiv);
        
        // Cerrar automáticamente después de 5 segundos
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alertDiv);
            bsAlert.close();
        }, 5000);
        
        // Eliminar el elemento del DOM después de cerrar
        alertDiv.addEventListener('closed.bs.alert', function() {
            alertDiv.remove();
        });
    }
    
    function confirmarEliminacion(id_pdf) {
        if (confirm('¿Estás seguro de que deseas eliminar este PDF? Esta acción no se puede deshacer.')) {
            // Mostrar alerta de eliminación exitosa después de confirmar
            fetch(`ver_pdf.php?id_manual=<?= $id_manual ?>&categoria=<?= urlencode($categoria) ?>&eliminar_pdf=${id_pdf}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(() => {
                window.location.href = `ver_pdf.php?id_manual=<?= $id_manual ?>&categoria=<?= urlencode($categoria) ?>&eliminado=1`;
            })
            .catch(error => {
                console.error('Error:', error);
                showFloatingAlert('Error al eliminar el PDF', 'danger');
            });
        }
    }
    </script>
</body>
</html>
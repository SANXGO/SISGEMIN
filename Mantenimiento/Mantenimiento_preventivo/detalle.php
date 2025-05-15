<?php
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/../../includes/audit.php';

// Verificar si se proporcionó un ID de mantenimiento
if (!isset($_GET['id'])) {
    header('Location: ../../index.php?tabla=preventivo');
    exit;
}

$id_mantenimiento = $_GET['id'];

// Obtener los detalles del mantenimiento
$stmt = $pdo->prepare("SELECT * FROM mantenimiento WHERE id_mantenimiento = ?");
$stmt->execute([$id_mantenimiento]);
$mantenimiento = $stmt->fetch();

// Si no se encuentra el mantenimiento, redirigir
if (!$mantenimiento) {
    header('Location: ../../index.php?tabla=preventivo');
    exit;
}

logViewRecord('Mantenimiento Preventivo', $id_mantenimiento, $mantenimiento);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de Mantenimiento #<?= htmlspecialchars($mantenimiento['id_mantenimiento']) ?></title>
    <link rel="icon" href="../../favicon.png">
    <link href="../../assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #b71513;
            --secondary-color: #540212;
            --accent-color: #b71513;
        }
        
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: none;
            overflow: hidden;
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            padding: 1.5rem;
        }
        
        .btn-custom {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            transition: all 0.3s;
        }
        

        
        .btn-danger {
            background-color: var(--accent-color);
            border: none;
        }
        
        .detail-item {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            transition: background-color 0.2s;
        }
        
        .detail-item:hover {
            background-color: #f8f9fa;
        }
        
        .detail-item strong {
            color: var(--primary-color);
            min-width: 150px;
            display: inline-block;
        }
        
        @media (max-width: 768px) {
            .detail-item strong {
                min-width: 120px;
                display: block;
                margin-bottom: 5px;
            }
        }

        /* Breadcrumb */
        .breadcrumb {
            background-color: #e9ecef;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
        }

        /* Modal styles */
        .bg-gradient-primary {
            background: linear-gradient(135deg, #b71513 100%);
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: #495057;
            font-weight: 500;
            padding: 0.75rem 1.25rem;
            transition: all 0.3s;
        }
        
        .nav-tabs .nav-link:hover {
            color: #2c3e50;
            border-bottom: 3px solid #dee2e6;
        }
        
        .nav-tabs .nav-link.active {
            color: #2c3e50;
            border-bottom: 3px solid #b71513;
            background-color: transparent;
        }
        
        .form-floating label {
            color: #6c757d;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #b71513;
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }
        
        .modal-content {
            border-radius: 0.5rem;
            overflow: hidden;
        }
        
        .btn-primary {
            background-color: #b71513;
            border-color: #b71513;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: #b71513;
            border-color: #b71513;
            transform: translateY(-2px);
        }

        a {
              color: #b71513;
             text-decoration: underline
        }

        .alert {
            border-radius: 0.5rem;
        }
    </style>
</head>
<body>

<div id="alertContainer" style="position: fixed; top: 20px; right: 20px; z-index: 1100;"></div>

    <div class="container py-4">
        <!-- Breadcrumb para mejor navegación -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../../index.php">Inicio</a></li>
                <li class="breadcrumb-item"><a href="../../index.php?tabla=preventivo">Mantenimientos Preventivos</a></li>
                <li class="breadcrumb-item active" aria-current="page">Mantenimiento #<?= htmlspecialchars($mantenimiento['id_mantenimiento']) ?></li>
            </ol>
        </nav>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle me-2"></i>
                <?= htmlspecialchars($_GET['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($_GET['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0">
                        <i class="bi bi-tools me-2"></i>
                        Mantenimiento Preventivo #<?= htmlspecialchars($mantenimiento['id_mantenimiento']) ?>
                        <span class="badge bg-secondary ms-2"><?= htmlspecialchars($mantenimiento['Tag_Number']) ?></span>
                    </h2>
                    <small class="text-white-50">Fecha: <?= htmlspecialchars($mantenimiento['fecha']) ?></small>
                </div>
                <div class="btn-group">
                    <button class="btn btn-custom me-2" data-bs-toggle="modal" data-bs-target="#editarModal" data-id="<?= $mantenimiento['id_mantenimiento'] ?>">
                        <i class="bi bi-pencil-square me-1"></i> Editar
                    </button>
                    <button class="btn btn-custom me-2" id="btnImprimir" onclick="imprimirRegistro(<?= $mantenimiento['id_mantenimiento'] ?>)">
                        <i class="bi bi-printer me-1"></i> Imprimir
                    </button>
                    <a href="../../index.php?tabla=preventivo" class="btn btn-custom">
                        <i class="bi bi-arrow-left me-1"></i> Volver
                    </a>
                </div>
            </div>
            
            <div class="card-body">
                <div class="row">
                    <!-- Columna 1: Información Básica -->
                    <div class="col-md-6">
                        <div class="detail-item">
                            <strong><i class="bi bi-tag me-2"></i>Tag Number:</strong>
                            <span><?= htmlspecialchars($mantenimiento['Tag_Number']) ?></span>
                        </div>
                        <div class="detail-item">
                            <strong><i class="bi bi-calendar me-2"></i>Fecha:</strong>
                            <span><?= htmlspecialchars($mantenimiento['fecha']) ?></span>
                        </div>
                        <div class="detail-item">
                            <strong><i class="bi bi-wrench me-2"></i>Tipo de Mantenimiento:</strong>
                            <span><?= htmlspecialchars($mantenimiento['mantenimiento']) ?></span>
                        </div>
                        <div class="detail-item">
                            <strong><i class="bi bi-building me-2"></i>Planta:</strong>
                            <span><?= htmlspecialchars($mantenimiento['planta']) ?></span>
                        </div>
                    </div>

                    <!-- Columna 2: Datos Técnicos -->
                    <div class="col-md-6">
                        <div class="detail-item">
                            <strong><i class="bi bi-file-text me-2"></i>Orden:</strong>
                            <span><?= htmlspecialchars($mantenimiento['orden']) ?></span>
                        </div>
                        <div class="detail-item">
                            <strong><i class="bi bi-gear me-2"></i>Instalación:</strong>
                            <span><?= htmlspecialchars($mantenimiento['instalacion']) ?></span>
                        </div>
                        <div class="detail-item">
                            <strong><i class="bi bi-upc-scan me-2"></i>Serial:</strong>
                            <span><?= htmlspecialchars($mantenimiento['serial']) ?></span>
                        </div>
                        <div class="detail-item">
                            <strong><i class="bi bi-box-seam me-2"></i>Modelo:</strong>
                            <span><?= htmlspecialchars($mantenimiento['modelo']) ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Columna 3: Mediciones -->
                    <div class="col-md-6">
                        <div class="detail-item">
                            <strong><i class="bi bi-speedometer2 me-2"></i>Medición Métrica:</strong>
                            <span><?= htmlspecialchars($mantenimiento['medicion_metrica']) ?></span>
                        </div>
                        <div class="detail-item">
                            <strong><i class="bi bi-lightning-charge me-2"></i>24VDC:</strong>
                            <span><?= htmlspecialchars($mantenimiento['24vdc']) ?></span>
                        </div>
                    </div>
                    
                    <!-- Columna 4: Observaciones -->
                    <div class="col-md-6">
                        <div class="detail-item">
                            <strong><i class="bi bi-eye me-2"></i>Observaciones:</strong>
                            <span><?= htmlspecialchars($mantenimiento['observaciones']) ?></span>
                        </div>
                        <div class="detail-item">
                            <strong><i class="bi bi-exclamation-triangle me-2"></i>Síntomas:</strong>
                            <span><?= htmlspecialchars($mantenimiento['sintomas']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                <small class="text-muted">Última actualización: <?= date('d/m/Y H:i') ?></small>
                <small class="text-muted">ID: <?= htmlspecialchars($mantenimiento['id_mantenimiento']) ?></small>
            </div>
        </div>
    </div>

    <!-- Modal para Editar Mantenimiento -->
    <div class="modal fade" id="editarModal" tabindex="-1" aria-labelledby="editarModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <!-- Encabezado del Modal -->
                <div class="modal-header bg-gradient-primary text-white">
                    <h3 class="modal-title fw-bold" id="editarModalLabel">
                        <i class="bi bi-pencil-square me-2"></i>
                        Editar Mantenimiento: #<?= htmlspecialchars($mantenimiento['id_mantenimiento']) ?>
                    </h3>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <!-- Cuerpo del Modal -->
                <div class="modal-body p-4">
                    <form id="formEditarMantenimiento" action="editar_preventivo.php" method="POST" novalidate>
                        <input type="hidden" id="edit_id_mantenimiento" name="id" value="<?= $mantenimiento['id_mantenimiento'] ?>">
                        
                        <!-- Sección de pestañas para mejor organización -->
                        <ul class="nav nav-tabs mb-4" id="mantenimientoTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info-tab-pane" type="button" role="tab">
                                    <i class="bi bi-info-circle me-2"></i>Información Básica
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="tecnico-tab" data-bs-toggle="tab" data-bs-target="#tecnico-tab-pane" type="button" role="tab">
                                    <i class="bi bi-gear me-2"></i>Datos Técnicos
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="mediciones-tab" data-bs-toggle="tab" data-bs-target="#mediciones-tab-pane" type="button" role="tab">
                                    <i class="bi bi-speedometer2 me-2"></i>Mediciones
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="mantenimientoTabsContent">
                          <!-- Pestaña 1: Información Básica -->
<div class="tab-pane fade show active" id="info-tab-pane" role="tabpanel" tabindex="0">
    <div class="row g-3">
        <!-- Tag Number -->
        <div class="col-md-6">
            <div class="form-floating">
                <input type="text" class="form-control" id="edit_tag_number" name="tag_number" 
                       readonly style="background-color: #f8f9fa; cursor: not-allowed;">
                <label for="edit_tag_number">Tag Number</label>
            </div>
        </div>
        
        <!-- Fecha -->
        <div class="col-md-6">
            <div class="form-floating">
                <input type="date" class="form-control" id="edit_fecha" name="fecha" 
                       min="2000-01-01" max="<?= date('Y-m-d') ?>" required>
                <label for="edit_fecha">Fecha</label>
                <div class="invalid-feedback">La fecha debe estar entre el año 2000 y hoy</div>
            </div>
        </div>
        
        <!-- Mantenimiento -->
        <div class="col-md-6">
            <div class="form-floating">
                <input type="text" class="form-control" id="edit_mantenimiento" name="mantenimiento" 
                       value="Preventivo" readonly>
                <label for="edit_mantenimiento">Tipo de Mantenimiento</label>
            </div>
        </div>
        
        <!-- Planta -->
        <div class="col-md-6">
            <div class="form-floating">
                <input type="text" class="form-control" id="edit_planta" name="planta"
                value="Preventivo" readonly>
                <label for="edit_planta">Planta</label>
            </div>
        </div>
    </div>
</div>

<!-- Pestaña 2: Datos Técnicos -->
<div class="tab-pane fade" id="tecnico-tab-pane" role="tabpanel" tabindex="0">
    <div class="row g-3">
        <!-- Orden -->
        <div class="col-md-6">
            <div class="form-floating">
                <input type="text" class="form-control" id="edit_orden" name="orden" 
                       maxlength="15" pattern=".{1,15}" required>
                <label for="edit_orden">Orden</label>
                <div class="invalid-feedback">Máximo 15 caracteres</div>
            </div>
        </div>
        
        <!-- Instalación -->
        <div class="col-md-6">
            <div class="form-floating">
                <input type="text" class="form-control" id="edit_instalacion" name="instalacion"
                       maxlength="20" pattern=".{1,20}" required>
                <label for="edit_instalacion">Instalación</label>
                <div class="invalid-feedback">Máximo 20 caracteres</div>
            </div>
        </div>
        
        <!-- Serial -->
        <div class="col-md-6">
            <div class="form-floating">
                <input type="text" class="form-control" id="edit_serial" name="serial"
                       maxlength="20" pattern=".{1,20}" required>
                <label for="edit_serial">Serial</label>
                <div class="invalid-feedback">Máximo 20 caracteres</div>
            </div>
        </div>
        
        <!-- Modelo -->
        <div class="col-md-6">
            <div class="form-floating">
                <input type="text" class="form-control" id="edit_modelo" name="modelo"
                       maxlength="20" pattern=".{1,20}" required>
                <label for="edit_modelo">Modelo</label>
                <div class="invalid-feedback">Máximo 20 caracteres</div>
            </div>
        </div>
    </div>
</div>

<!-- Pestaña 3: Mediciones -->
<div class="tab-pane fade" id="mediciones-tab-pane" role="tabpanel" tabindex="0">
    <div class="row g-3">
        <!-- Medicion Métrica -->
        <div class="col-md-6">
            <div class="form-floating">
                <input type="text" class="form-control" id="edit_medicion_metrica" name="medicion_metrica"
                       maxlength="20" pattern=".{1,20}" required>
                <label for="edit_medicion_metrica">Medición Métrica</label>
                <div class="invalid-feedback">Máximo 20 caracteres</div>
            </div>
        </div>
        
        <!-- 24VDC -->
        <div class="col-md-6">
            <div class="form-floating">
                <input type="text" class="form-control" id="edit_24vdc" name="24vdc"
                       maxlength="20" pattern=".{1,20}" required>
                <label for="edit_24vdc">24VDC</label>
                <div class="invalid-feedback">Máximo 20 caracteres</div>
            </div>
        </div>
        
        <!-- Observaciones -->
        <div class="col-md-12">
            <div class="form-floating">
                <textarea class="form-control" id="edit_observaciones" name="observaciones"
                          maxlength="70" style="height: 100px" required></textarea>
                <label for="edit_observaciones">Observaciones</label>
                <div class="invalid-feedback">Máximo 70 caracteres</div>
            </div>
        </div>
        
        <!-- Síntomas -->
        <div class="col-md-12">
            <div class="form-floating">
                <input type="text" class="form-control" id="edit_sintomas" name="sintomas"
                       maxlength="20" pattern=".{1,20}" required>
                <label for="edit_sintomas">Síntomas</label>
                <div class="invalid-feedback">Máximo 20 caracteres</div>
            </div>
        </div>
    </div>
</div>
                        <!-- Pie del Modal -->
                        <div class="modal-footer bg-light mt-4">

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="../../assets/js/bootstrap.bundle.min.js"></script>
    <script>


function showFloatingAlert(message, type) {
    const alertContainer = document.getElementById('alertContainer');
    const alertId = 'alert-' + Date.now();
    
    const alertDiv = document.createElement('div');
    alertDiv.id = alertId;
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.style.minWidth = '300px';
    alertDiv.style.marginBottom = '10px';
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


    document.addEventListener('DOMContentLoaded', function() {


        // Cargar datos del mantenimiento en el modal
        const editarModal = document.getElementById('editarModal');
        
        editarModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id_mantenimiento = button.getAttribute('data-id');
            
            fetch(`obtener_preventivo.php?id=${id_mantenimiento}`)
                .then(response => {
                    if (!response.ok) throw new Error('Error al obtener los datos');
                    return response.json();
                })
                .then(data => {
                    // Llenar el formulario de edición
                    document.getElementById('edit_id_mantenimiento').value = data.id_mantenimiento;
                    document.getElementById('edit_tag_number').value = data.Tag_Number || data.tag_number;
                    document.getElementById('edit_fecha').value = data.fecha;
                    document.getElementById('edit_planta').value = data.planta;
                    document.getElementById('edit_orden').value = data.orden;
                    document.getElementById('edit_instalacion').value = data.instalacion;
                    document.getElementById('edit_serial').value = data.serial;
                    document.getElementById('edit_modelo').value = data.modelo;
                    document.getElementById('edit_mantenimiento').value = "Preventivo";
                    document.getElementById('edit_medicion_metrica').value = data.medicion_metrica;
                    document.getElementById('edit_24vdc').value = data['24vdc'];
                    document.getElementById('edit_observaciones').value = data.observaciones;
                    document.getElementById('edit_sintomas').value = data.sintomas;
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar los datos del mantenimiento');
                });
        });

        // Validación del formulario
        const form = document.getElementById('formEditarMantenimiento');
        
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);

        // Validación en tiempo real para campos
        form.querySelectorAll('input, select').forEach(input => {
            input.addEventListener('input', function() {
                if (input.checkValidity()) {
                    input.classList.remove('is-invalid');
                    input.classList.add('is-valid');
                } else {
                    input.classList.remove('is-valid');
                    input.classList.add('is-invalid');
                }
            });
        });
    });

    function imprimirRegistro(id) {
        window.open(`pdf.php?id=${id}`, '_blank');
    }



    const form = document.getElementById('formEditarMantenimiento');
    
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        
        if (!form.checkValidity()) {
            event.stopPropagation();
            form.classList.add('was-validated');
            return;
        }
        
        // Enviar el formulario con AJAX
        const formData = new FormData(form);
        
        fetch(form.action, {
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
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showFloatingAlert(data.message, 'success');
                // Cerrar modal después de 1 segundo
                setTimeout(() => {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editarModal'));
                    modal.hide();
                    // Recargar la página después de cerrar el modal
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                }, 1000);
            } else {
                showFloatingAlert(data.error || 'Error al actualizar el mantenimiento', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showFloatingAlert('Error en el servidor. Por favor verifica la consola para más detalles.', 'danger');
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
    // Validación del formulario
    const form = document.getElementById('formEditarMantenimiento');
    
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            
            // Mostrar mensajes de error para todos los campos inválidos
            form.querySelectorAll(':invalid').forEach(input => {
                input.classList.add('is-invalid');
                
                // Si el campo está en una pestaña no visible, activar esa pestaña
                const tabPane = input.closest('.tab-pane');
                if (tabPane && !tabPane.classList.contains('show')) {
                    const tabId = tabPane.id.replace('-tab-pane', '-tab');
                    const tab = document.getElementById(tabId);
                    if (tab) {
                        new bootstrap.Tab(tab).show();
                    }
                }
            });
        }
        
        form.classList.add('was-validated');
    }, false);

    // Validación en tiempo real para campos
    form.querySelectorAll('input, select, textarea').forEach(input => {
        input.addEventListener('input', function() {
            if (input.checkValidity()) {
                input.classList.remove('is-invalid');
                input.classList.add('is-valid');
            } else {
                input.classList.remove('is-valid');
                input.classList.add('is-invalid');
            }
            
            // Validación especial para campos de texto con maxlength
            if (input.hasAttribute('maxlength')) {
                const maxLength = parseInt(input.getAttribute('maxlength'));
                const currentLength = input.value.length;
                
                if (currentLength > maxLength) {
                    input.value = input.value.substring(0, maxLength);
                }
            }
        });
    });

    // Validación especial para la fecha
    const fechaInput = document.getElementById('edit_fecha');
    if (fechaInput) {
        fechaInput.addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const minDate = new Date('2000-01-01');
            const maxDate = new Date();
            
            if (selectedDate < minDate || selectedDate > maxDate) {
                this.setCustomValidity('La fecha debe estar entre el año 2000 y hoy');
            } else {
                this.setCustomValidity('');
            }
        });
    }
});

    </script>
</body>
</html>
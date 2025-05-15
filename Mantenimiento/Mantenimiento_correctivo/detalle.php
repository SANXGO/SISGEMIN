<?php
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/../../includes/audit.php';


// Verificar si se proporcionó un ID de intervención
if (!isset($_GET['id'])) {
    header('Location: ../../index.php?tabla=correctivo');
    exit;
}

$id_intervencion = $_GET['id'];

// Obtener los detalles de la intervención
$stmt = $pdo->prepare("SELECT * FROM intervencion WHERE id_inter = ?");
$stmt->execute([$id_intervencion]);
$intervencion = $stmt->fetch();

// Si no se encuentra la intervención, redirigir
if (!$intervencion) {
    header('Location: ../../index.php?tabla=correctivo');
    exit;
}

logViewRecord('Mantenimiento Correctivo', $id_intervencion, $intervencion);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de Intervención #<?= htmlspecialchars($intervencion['id_inter']) ?></title>
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
  
        }
        
        #alertContainer {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1100;
            max-width: 350px;
        }
        
        .floating-alert {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }
        
        .floating-alert.show {
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <!-- Breadcrumb para mejor navegación -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="../../index.php">Inicio</a></li>
                <li class="breadcrumb-item"><a href="../../index.php?tabla=correctivo">Mantenimiento Correctivo</a></li>
                <li class="breadcrumb-item active" aria-current="page">Mantenimiento Correctivo #<?= htmlspecialchars($intervencion['id_inter']) ?></li>
            </ol>
        </nav>





        <div id="alertContainer"></div>

        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="mb-0">
                        <i class="bi bi-tools me-2"></i>
                        Mantenimiento Correctivo #<?= htmlspecialchars($intervencion['id_inter']) ?>
                        <span class="badge bg-secondary ms-2"><?= htmlspecialchars($intervencion['Tag_Number']) ?></span>
                    </h2>
                    <small class="text-white-50">Fecha: <?= htmlspecialchars($intervencion['fecha']) ?></small>
                </div>
                <div class="btn-group">
                    <button class="btn btn-custom me-2" data-bs-toggle="modal" data-bs-target="#editarIntervencionModal" data-id="<?= $intervencion['id_inter'] ?>">
                        <i class="bi bi-pencil-square me-1"></i> Editar
                    </button>
                    <button class="btn btn-custom me-2" id="btnImprimir" onclick="imprimirRegistro(<?= $intervencion['id_inter'] ?>)">
                        <i class="bi bi-printer me-1"></i> Imprimir
                    </button>
                    <a href="../../index.php?tabla=correctivo" class="btn btn-custom">
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
                            <span><?= htmlspecialchars($intervencion['Tag_Number']) ?></span>
                        </div>
                        <div class="detail-item">
                            <strong><i class="bi bi-calendar me-2"></i>Fecha:</strong>
                            <span><?= htmlspecialchars($intervencion['fecha']) ?></span>
                        </div>
                        <div class="detail-item">
                            <strong><i class="bi bi-wrench me-2"></i>Tipo de Mantenimiento:</strong>
                            <span><?= htmlspecialchars($intervencion['mantenimiento_correctivo']) ?></span>
                        </div>
                    </div>

                    <!-- Columna 2: Responsables -->
                    <div class="col-md-6">
                        <div class="detail-item">
                            <strong><i class="bi bi-person-badge me-2"></i>Responsable:</strong>
                            <span><?= htmlspecialchars($intervencion['responsable']) ?></span>
                        </div>
                        <div class="detail-item">
                            <strong><i class="bi bi-clock me-2"></i>Tiempo empleado:</strong>
                            <span><?= htmlspecialchars($intervencion['tiempo']) ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Descripción -->
                <div class="detail-item">
                    <strong><i class="bi bi-card-text me-2"></i>Descripción:</strong>
                    <p class="mt-2"><?= htmlspecialchars($intervencion['descripcion']) ?></p>
                </div>
                
                <div class="row">
                    <!-- Materiales -->
                    <div class="col-md-6">
                        <div class="detail-item">
                            <strong><i class="bi bi-box-seam me-2"></i>Materiales:</strong>
                            <p class="mt-2"><?= htmlspecialchars($intervencion['materiales']) ?></p>
                        </div>
                    </div>
                    
                    <!-- Repuestos -->
                    <div class="col-md-6">
                        <div class="detail-item">
                            <strong><i class="bi bi-gear me-2"></i>Repuestos:</strong>
                            <p class="mt-2"><?= htmlspecialchars($intervencion['repuestos']) ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                <small class="text-muted">Última actualización: <?= date('d/m/Y H:i') ?></small>
                <small class="text-muted">ID: <?= htmlspecialchars($intervencion['id_inter']) ?></small>
            </div>
        </div>
    </div>

    <!-- Modal para Editar Intervención -->
    <div class="modal fade" id="editarIntervencionModal" tabindex="-1" aria-labelledby="editarIntervencionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg">
                <!-- Encabezado del Modal -->
                <div class="modal-header bg-gradient-primary text-white">
                    <h3 class="modal-title fw-bold" id="editarIntervencionModalLabel">
                        <i class="bi bi-pencil-square me-2"></i>
                        Editar Mantenimiento: #<?= htmlspecialchars($intervencion['id_inter']) ?>
                    </h3>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <!-- Cuerpo del Modal -->
                <div class="modal-body p-4">
                    <form action="editar_correctivo.php" method="POST" id="formEditarIntervencion" novalidate>
                        <input type="hidden" id="edit_id_intervencion" name="id_intervencion">
                        
                        <div class="row g-3">
                            <!-- Tag Number -->
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="edit_tag_number" name="tag_number" readonly style="background-color: #f8f9fa; cursor: not-allowed;">
                                    <label for="edit_tag_number">Tag Number</label>
                                </div>
                            </div>
                            
                            <!-- Fecha -->
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="date" class="form-control" id="edit_fecha" name="fecha" required>
                                    <label for="edit_fecha">Fecha</label>
                                    <div class="invalid-feedback">La fecha debe ser válida (entre 2000 y hoy)</div>
                                </div>
                            </div>
                            
                            <!-- Mantenimiento -->
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="edit_mantenimiento" name="mantenimiento_correctivo" value="Correctivo" readonly>
                                    <label for="edit_mantenimiento">Tipo de Mantenimiento</label>
                                </div>
                            </div>
                            
                            <!-- Descripción -->
                            <div class="col-md-12">
                                <div class="form-floating">
                                    <textarea class="form-control" id="edit_descripcion" name="descripcion" maxlength="90" required style="height: 100px"></textarea>
                                    <label for="edit_descripcion">Descripción</label>
                                    <small class="text-muted">Máximo 90 caracteres</small>
                                    <div class="invalid-feedback">La descripción debe tener máximo 90 caracteres</div>
                                </div>
                            </div>
                            
                            <!-- Repuestos -->
                            <div class="col-md-6">
                                <div class="form-floating">
                                <input type="text" class="form-control" id="edit_repuestos" name="repuestos" maxlength="30" readonly>                                    <label for="edit_repuestos">Repuestos</label>
                                    <small class="text-muted">Máximo 30 caracteres</small>
                                    <div class="invalid-feedback">Los repuestos deben tener máximo 30 caracteres</div>
                                </div>
                            </div>
                            
                            <!-- Materiales -->
                            <div class="col-md-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="edit_materiales" name="materiales" maxlength="30">
                                    <label for="edit_materiales">Materiales</label>
                                    <small class="text-muted">Máximo 30 caracteres</small>
                                    <div class="invalid-feedback">Los materiales deben tener máximo 30 caracteres</div>
                                </div>
                            </div>
                            
                            <!-- Tiempo -->
                            <div class="col-md-4">
                                <div class="form-floating">
                                    <input type="time" class="form-control" id="edit_tiempo" name="tiempo">
                                    <label for="edit_tiempo">Tiempo empleado</label>
                                </div>
                            </div>
                            
                            <!-- Responsable -->
                            <div class="col-md-8">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="edit_responsable" name="responsable" maxlength="15" pattern="[A-Za-zÁ-Úá-ú\s]+">
                                    <label for="edit_responsable">Responsable</label>
                                    <small class="text-muted">Máximo 15 letras (solo letras y espacios)</small>
                                    <div class="invalid-feedback">El responsable debe contener solo letras y tener máximo 15 caracteres</div>
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
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-cierre de alertas después de 5 segundos
        const alerts = document.querySelectorAll('.alert-auto-close');
        alerts.forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
        
        // Evento para cargar los datos de la intervención en el modal de edición
        const editarIntervencionModal = document.getElementById('editarIntervencionModal');
        editarIntervencionModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id_intervencion = button.getAttribute('data-id');

            fetch(`obtener_correctivo.php?id=${id_intervencion}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error al obtener los datos');
                    }
                    return response.json();
                })
                .then(data => {
                    document.getElementById('edit_id_intervencion').value = data.id_inter;
                    document.getElementById('edit_tag_number').value = data.Tag_Number;
                    document.getElementById('edit_descripcion').value = data.descripcion;
                    document.getElementById('edit_fecha').value = data.fecha;
                    document.getElementById('edit_repuestos').value = data.repuestos;
                    document.getElementById('edit_materiales').value = data.materiales;
                    
                    // Formatear el tiempo correctamente
                    const tiempo = data.tiempo ? data.tiempo.substring(0, 5) : '';
                    document.getElementById('edit_tiempo').value = tiempo;
                    
                    document.getElementById('edit_responsable').value = data.responsable;
                    document.getElementById('edit_mantenimiento').value = data.mantenimiento_correctivo;
                })
                .catch(error => {
                    console.error('Error al obtener los detalles:', error);
                    showFloatingAlert('Error al cargar los datos del mantenimiento', 'danger');
                });
        });

        // Validación en tiempo real para el campo responsable (solo letras)
        document.getElementById('edit_responsable').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^A-Za-zÁ-Úá-ú\s]/g, '');
            
            // Validación en tiempo real
            validateField(this, /^[A-Za-zÁ-Úá-ú\s]{1,15}$/.test(this.value));
        });

        // Validación en tiempo real para otros campos
        document.getElementById('edit_descripcion').addEventListener('input', function() {
            validateField(this, this.value.length <= 90);
        });
        
        document.getElementById('edit_repuestos').addEventListener('input', function() {
            validateField(this, this.value.length <= 30);
        });
        
        document.getElementById('edit_materiales').addEventListener('input', function() {
            validateField(this, this.value.length <= 30);
        });
        
        document.getElementById('edit_fecha').addEventListener('change', function() {
            const fecha = new Date(this.value);
            const hoy = new Date();
            hoy.setHours(0, 0, 0, 0);
            const añoMinimo = new Date('2000-01-01');
            
            validateField(this, fecha <= hoy && fecha >= añoMinimo);
        });

        // Función para validar campos en tiempo real
        function validateField(field, isValid) {
            if (isValid) {
                field.classList.remove('is-invalid');
                field.classList.add('is-valid');
            } else {
                field.classList.remove('is-valid');
                field.classList.add('is-invalid');
            }
        }

        // Configurar validación del formulario
        const formEditarIntervencion = document.getElementById('formEditarIntervencion');
        
        formEditarIntervencion.addEventListener('submit', function(event) {
            event.preventDefault();
            event.stopPropagation();
            
            // Validar todos los campos
            let isValid = true;
            
            // Validar descripción (obligatorio, máximo 90 caracteres)
            const descripcion = document.getElementById('edit_descripcion');
            if (descripcion.value.trim() === '' || descripcion.value.length > 90) {
                descripcion.classList.add('is-invalid');
                isValid = false;
            } else {
                descripcion.classList.remove('is-invalid');
            }
            
            // Validar fecha (obligatorio, no futura, no menor a 2000)
            const fechaInput = document.getElementById('edit_fecha');
            const fecha = new Date(fechaInput.value);
            const hoy = new Date();
            hoy.setHours(0, 0, 0, 0);
            const añoMinimo = new Date('2000-01-01');
            
            if (!fechaInput.value || fecha > hoy || fecha < añoMinimo) {
                fechaInput.classList.add('is-invalid');
                isValid = false;
            } else {
                fechaInput.classList.remove('is-invalid');
            }
            
            // Validar responsable (obligatorio, solo letras, máximo 15 caracteres)
            const responsable = document.getElementById('edit_responsable');
            const letrasRegex = /^[A-Za-zÁ-Úá-ú\s]{1,15}$/;
            if (responsable.value.trim() === '' || !letrasRegex.test(responsable.value)) {
                responsable.classList.add('is-invalid');
                isValid = false;
            } else {
                responsable.classList.remove('is-invalid');
            }
            
            // Validar repuestos (obligatorio, máximo 30 caracteres)
            const repuestos = document.getElementById('edit_repuestos');
            if (repuestos.value.trim() === '' || repuestos.value.length > 30) {
                repuestos.classList.add('is-invalid');
                isValid = false;
            } else {
                repuestos.classList.remove('is-invalid');
            }
            
            // Validar materiales (obligatorio, máximo 30 caracteres)
            const materiales = document.getElementById('edit_materiales');
            if (materiales.value.trim() === '' || materiales.value.length > 30) {
                materiales.classList.add('is-invalid');
                isValid = false;
            } else {
                materiales.classList.remove('is-invalid');
            }
            
            if (!isValid) {
                this.classList.add('was-validated');
                showFloatingAlert('Por favor complete todos los campos correctamente', 'warning');
                return;
            }
            
            // Enviar el formulario con AJAX
            const formData = new FormData(this);
            
            fetch(this.action, {
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
                    showFloatingAlert('Mantenimiento actualizado exitosamente!', 'success');
                    // Recargar la página después de 1.5 segundos
                    setTimeout(() => {
                        window.location.href = `detalle.php?id=${document.getElementById('edit_id_intervencion').value}&success=Mantenimiento actualizado correctamente`;
                    }, 1500);
                } else {
                    showFloatingAlert('Error: ' + (data.error || 'No se pudo actualizar el mantenimiento'), 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showFloatingAlert('Error en el servidor. Por favor verifica la consola para más detalles.', 'danger');
            });
        });
        
        // Función para mostrar alertas flotantes
        function showFloatingAlert(message, type) {
            const alertContainer = document.getElementById('alertContainer');
            const alertId = 'alert-' + Date.now();
            
            const alertDiv = document.createElement('div');
            alertDiv.id = alertId;
            alertDiv.className = `alert alert-${type} alert-dismissible fade show floating-alert`;
            alertDiv.style.minWidth = '300px';
            alertDiv.style.marginBottom = '10px';
            alertDiv.role = 'alert';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            
            alertContainer.appendChild(alertDiv);
            
            // Forzar el reflow para activar la transición
            alertDiv.offsetHeight;
            alertDiv.classList.add('show');
            
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
    });

    function imprimirRegistro(id) {
        window.open(`pdf.php?id=${id}`, '_blank');
    }
    </script>
</body>
</html>
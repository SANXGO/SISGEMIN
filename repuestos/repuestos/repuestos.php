<?php
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/../../includes/check_permission.php';
require_once __DIR__ . '/../../includes/audit.php';
// Consulta para obtener repuestos con nombres de fabricantes
$stmt = $pdo->query("
    SELECT r.id_repuestos, f.nombres as fabricante, r.descripcion, r.real_part, r.sectional_drawing 
    FROM repuestos r
    JOIN fabricantes f ON r.id_fabricante = f.id_fabricante
    WHERE r.estado='activo'
");
$repuestos = $stmt->fetchAll();

// Consulta para obtener fabricantes para los selects
$stmtFabricantes = $pdo->query("SELECT id_fabricante, nombres FROM fabricantes ORDER BY nombres");
$fabricantes = $stmtFabricantes->fetchAll();
logModuleAccess('Repuestos');

?>

<link href="assets/css/bootstrap.min.css" rel="stylesheet">
<style>
    .sticky-top {
        position: sticky;
        top: 0;
        z-index: 1000;
        background-color: white;
        padding: 10px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }
    .table {
        margin-top: 80px;
    }
    .selected-row {
        background-color: #ffcccc !important;
    }
    .is-invalid {
        border-color: #dc3545;
    }
    .invalid-feedback {
        display: none;
        color: #dc3545;
        font-size: 0.875em;
    }
    .is-invalid ~ .invalid-feedback {
        display: block;
    }
    .pdf-btn {
        background-color: #d9534f;
        border-color: #d43f3a;
        color: white;
    }
    .pdf-btn:hover {
        background-color: #c9302c;
        border-color: #ac2925;
        color: white;
    }
    .bg-gradient-primary {
        background: linear-gradient( #b71513 100%);
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
    /* Estilos para alertas flotantes */
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
</style>

<!-- Contenedor para alertas flotantes -->
<div id="alertContainer"></div>

<div class="sticky-top bg-white p-3 shadow-sm">
    <h4 class="text-center mb-1">Repuestos</h4>

    <!-- Barra de búsqueda y botones -->
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex gap-2">
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#agregarRepuestoModal">Agregar Repuesto</button>
            <button id="btnEditar" class="btn btn-danger" disabled data-bs-toggle="modal" data-bs-target="#editarRepuestoModal">Editar</button>
            <button id="btnPDF" class="btn pdf-btn" onclick="generarPDF()"> Generar PDF</button>
        </div>
        <div style="width: 300px;">
            <input type="text" id="search" class="form-control" placeholder="Buscar por Descripción" onkeyup="filtrarRepuestos()">
        </div>
    </div>
</div>

<!-- Tabla de registros -->
<table class="table table-striped table-fixed">
    <thead class="table-dark">
        <tr>
            <th style="width: 8%;">#</th>  <!-- Changed from "ID" to "#" -->
            <th style="width: 15%;">Fabricante</th>
            <th style="width: 30%;">Descripción</th>
            <th style="width: 25%;">Real Part</th>
            <th style="width: 22%;">Sectional Drawing</th>
        </tr>
    </thead>
    <tbody id="tabla-repuestos">
        <?php foreach ($repuestos as $index => $repuesto): ?>
            <tr onclick="seleccionarRepuesto('<?= $repuesto['id_repuestos'] ?>')" style="cursor: pointer;">
                <td><?= $index + 1 ?></td>  <!-- Sequential numbering starting at 1 -->
                <td><?= htmlspecialchars($repuesto['fabricante']) ?></td>
                <td class="text-truncate" style="max-width: 300px;" 
                    data-bs-toggle="tooltip" data-bs-placement="top" 
                    title="<?= htmlspecialchars($repuesto['descripcion']) ?>">
                    <?= htmlspecialchars($repuesto['descripcion']) ?>
                </td>
                <td class="text-truncate" style="max-width: 250px;" 
                    data-bs-toggle="tooltip" data-bs-placement="top" 
                    title="<?= htmlspecialchars($repuesto['real_part']) ?>">
                    <?= htmlspecialchars($repuesto['real_part']) ?>
                </td>
                <td class="text-truncate" style="max-width: 220px;" 
                    data-bs-toggle="tooltip" data-bs-placement="top" 
                    title="<?= htmlspecialchars($repuesto['sectional_drawing']) ?>">
                    <?= htmlspecialchars($repuesto['sectional_drawing']) ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<!-- Modales -->

<!-- Modal para Agregar Repuesto -->
<div class="modal fade" id="agregarRepuestoModal" tabindex="-1" aria-labelledby="agregarRepuestoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <!-- Encabezado del Modal -->
            <div class="modal-header bg-gradient-primary text-white">
                <h3 class="modal-title fw-bold" id="agregarRepuestoModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>
                    Agregar Repuesto
                </h3>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Cuerpo del Modal -->
            <div class="modal-body p-4">
                <form id="formAgregarRepuesto" action="repuestos/repuestos/agregar_repuesto.php" method="POST" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <select class="form-select" name="id_fabricante" required>
                                    <option value="">Seleccione un fabricante</option>
                                    <?php foreach ($fabricantes as $fab): ?>
                                        <option value="<?= $fab['id_fabricante'] ?>"><?= htmlspecialchars($fab['nombres']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label>Fabricante</label>
                                <div class="invalid-feedback">Por favor seleccione un fabricante</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="descripcion" maxlength="35" required>
                                <label>Descripción</label>
                                <div class="invalid-feedback">La descripción es requerida (máx. 35 caracteres)</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="real_part" maxlength="35" required>
                                <label>Real Part</label>
                                <div class="invalid-feedback">El Real Part es requerido (máx. 35 caracteres)</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="sectional_drawing" maxlength="35" required>
                                <label>Sectional Drawing</label>
                                <div class="invalid-feedback">El Sectional Drawing es requerido (máx. 35 caracteres)</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pie del Modal -->
                    <div class="modal-footer bg-light mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Guardar Repuesto
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Editar Repuesto -->
<div class="modal fade" id="editarRepuestoModal" tabindex="-1" aria-labelledby="editarRepuestoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <!-- Encabezado del Modal -->
            <div class="modal-header bg-gradient-primary text-white">
                <h3 class="modal-title fw-bold" id="editarRepuestoModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>
                    Editar Repuesto
                </h3>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Cuerpo del Modal -->
            <div class="modal-body p-4">
                <form id="formEditarRepuesto" action="repuestos/repuestos/editar_repuesto.php" method="POST" class="needs-validation" novalidate>
                    <input type="hidden" id="edit_id_repuesto" name="id_repuestos">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <select class="form-select" id="edit_id_fabricante" name="id_fabricante" required>
                                    <?php foreach ($fabricantes as $fab): ?>
                                        <option value="<?= $fab['id_fabricante'] ?>"><?= htmlspecialchars($fab['nombres']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label>Fabricante</label>
                                <div class="invalid-feedback">Por favor seleccione un fabricante</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="edit_descripcion" name="descripcion" maxlength="35" required>
                                <label>Descripción</label>
                                <div class="invalid-feedback">La descripción es requerida (máx. 35 caracteres)</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="edit_real_part" name="real_part" maxlength="35" required>
                                <label>Real Part</label>
                                <div class="invalid-feedback">El Real Part es requerido (máx. 35 caracteres)</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="edit_sectional_drawing" name="sectional_drawing" maxlength="35" required>
                                <label>Sectional Drawing</label>
                                <div class="invalid-feedback">El Sectional Drawing es requerido (máx. 35 caracteres)</div>
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

<!-- Script's -->
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/jquery-3.6.0.min.js"></script>
<script src="assets/js/popper.min.js"></script>
<script src="assets/js/jspdf.umd.min.js"></script>
<script src="assets/js/jspdf.plugin.autotable.min.js"></script>
<script src="assets/js/all.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    vincularEventosRepuestos();
    inicializarValidaciones();
    
    // Mostrar alertas si existen en la URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success')) {
        showFloatingAlert('Operación realizada con éxito!', 'success');
    } else if (urlParams.has('error')) {
        showFloatingAlert('Ocurrió un error al procesar la solicitud', 'danger');
    }
});

let repuestoSeleccionado = null;

// Función para inicializar validaciones de formularios
function inicializarValidaciones() {
    // Validación del formulario de agregar
    const formAgregar = document.getElementById('formAgregarRepuesto');
    if (formAgregar) {
        formAgregar.addEventListener('submit', function(event) {
            event.preventDefault();
            
            if (!this.checkValidity()) {
                event.stopPropagation();
                this.classList.add('was-validated');
                return;
            }
            
            enviarFormulario(this, 'agregarRepuestoModal', 'agregado');
        });
    }
    
    // Validación del formulario de editar
    const formEditar = document.getElementById('formEditarRepuesto');
    if (formEditar) {
        formEditar.addEventListener('submit', function(event) {
            event.preventDefault();
            
            if (!this.checkValidity()) {
                event.stopPropagation();
                this.classList.add('was-validated');
                return;
            }
            
            enviarFormulario(this, 'editarRepuestoModal', 'editado');
        });
    }
}

// Función genérica para enviar formularios
function enviarFormulario(form, modalId, accion) {
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
            showFloatingAlert('Operación realizada con éxito!', 'success');
            // Cerrar modal y recargar
            setTimeout(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
                modal.hide();
                setTimeout(() => {
                    window.location.href = window.location.pathname + '?tabla=repuestos&' + accion + '=1';
                }, 500);
            }, 1000);
        } else {
            showFloatingAlert('Error: ' + (data.error || 'No se pudo completar la operación'), 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showFloatingAlert('Error en el servidor. Por favor verifica la consola para más detalles.', 'danger');
    });
}

// Función para seleccionar un repuesto
function seleccionarRepuesto(id_repuesto) {
    // Remover la clase 'selected-row' de todas las filas
    document.querySelectorAll('#tabla-repuestos tr').forEach(tr => {
        tr.classList.remove('selected-row');
    });

    // Agregar la clase 'selected-row' a la fila seleccionada
    const filaSeleccionada = document.querySelector(`#tabla-repuestos tr[onclick*="${id_repuesto}"]`);
    if (filaSeleccionada) {
        filaSeleccionada.classList.add('selected-row');
    }

    // Guardar el ID del repuesto seleccionado
    repuestoSeleccionado = id_repuesto;

    // Habilitar los botones de acciones
    document.getElementById('btnEditar').disabled = false;
}



// Función para filtrar repuestos (búsqueda)
function filtrarRepuestos() {
    const searchValue = document.getElementById('search').value.trim();
    
    if (searchValue === "") {
        // Si el campo de búsqueda está vacío, recargar la página para mostrar todos
        window.location.reload();
        return;
    }

    fetch(`repuestos/repuestos/buscar_repuestos.php?search=${encodeURIComponent(searchValue)}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('tabla-repuestos').innerHTML = data;
            repuestoSeleccionado = null; // Reiniciar la selección
            document.getElementById('btnEditar').disabled = true;
            document.getElementById('btnEliminar').disabled = true;
        })
        .catch(error => console.error('Error al buscar repuestos:', error));
}

// Evento para cargar los datos del repuesto en el modal de edición
document.addEventListener('DOMContentLoaded', function() {
    const editarRepuestoModal = document.getElementById('editarRepuestoModal');
    editarRepuestoModal.addEventListener('show.bs.modal', function(event) {
        if (!repuestoSeleccionado) return;
        
        fetch(`repuestos/repuestos/obtener_detalles_repuesto.php?id_repuesto=${repuestoSeleccionado}`, {
            headers: {
                'Accept': 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    showFloatingAlert('Error al cargar datos: ' + data.error, 'danger');
                    return;
                }
                
                document.getElementById('edit_id_repuesto').value = data.id_repuestos;
                document.getElementById('edit_id_fabricante').value = data.id_fabricante;
                document.getElementById('edit_descripcion').value = data.descripcion;
                document.getElementById('edit_real_part').value = data.real_part;
                document.getElementById('edit_sectional_drawing').value = data.sectional_drawing;
            })
            .catch(error => {
                console.error('Error al obtener los detalles:', error);
                showFloatingAlert('Error al cargar datos del repuesto', 'danger');
            });
    });
});

// Función para generar PDF
function generarPDF() {
    const searchValue = document.getElementById('search').value;
    let url = `repuestos/repuestos/generar_pdf_repuestos.php?search=${searchValue}`;
    window.open(url, '_blank');
}

// Función para vincular eventos
function vincularEventosRepuestos() {
    // Resetear modal de agregar al cerrar
    document.getElementById('agregarRepuestoModal').addEventListener('hidden.bs.modal', function () {
        this.querySelector('form').reset();
        this.querySelector('form').classList.remove('was-validated');
    });
    
    // Resetear modal de edición al cerrar
    document.getElementById('editarRepuestoModal').addEventListener('hidden.bs.modal', function () {
        this.querySelector('form').reset();
        this.querySelector('form').classList.remove('was-validated');
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

// Activar tooltips de Bootstrap para mostrar contenido completo
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
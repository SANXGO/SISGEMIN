<?php

require_once __DIR__ . '/../includes/conexion.php';
require_once __DIR__ . '/../includes/check_permission.php';
require_once __DIR__ . '/../includes/audit.php';



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

// Consulta principal sin paginación - solo ubicaciones de la planta del usuario
$stmt = $pdo->prepare("
    SELECT u.id_ubicacion, p.nombres AS nombres, u.descripcion 
    FROM ubicacion u
    JOIN planta p ON u.id_planta = p.id_planta
    WHERE p.estado = 'activo' AND u.estado = 'activo' AND u.id_planta = ?
");
$stmt->execute([$id_planta_usuario]);
$ubicaciones = $stmt->fetchAll();

// Consulta para obtener solo la planta del usuario (usada en los modales)
$stmt = $pdo->prepare("SELECT id_planta, nombres FROM planta WHERE id_planta = ?");
$stmt->execute([$id_planta_usuario]);
$planta_usuario = $stmt->fetch();

logModuleAccess('ubicacion');

?>

<link href="assets/css/bootstrap.min.css" rel="stylesheet">

<div class="sticky-top bg-white p-3 shadow-sm">
    <h4 class="text-center mb-1">Gestión de Ubicaciones</h4>

    <!-- Barra de búsqueda y botones -->
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex gap-2">
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#agregarUbicacionModal">Agregar Ubicación</button>
            <button id="btnEditar" class="btn btn-danger" disabled data-bs-toggle="modal" data-bs-target="#editarUbicacionModal">Editar</button>
        </div>
        <div style="width: 300px;">
            <input type="text" id="search" class="form-control" placeholder="Buscar por Ubicación" onkeyup="filtrarUbicacion()">
        </div>
    </div>
</div>
<div id="alertContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 1100;"></div>
<!-- Tabla de registros -->
<table class="table table-striped">
    <thead class="table-dark">
        <tr>
            <th>ID Ubicación</th>
            <th>Planta</th>
            <th>Descripción</th>
        </tr>
    </thead>
    <tbody id="tabla-ubicacion">
        <?php foreach ($ubicaciones as $ubicacion): ?>
            <tr onclick="seleccionar('<?= $ubicacion['id_ubicacion'] ?>')" style="cursor: pointer;">
                <td><?= htmlspecialchars($ubicacion['id_ubicacion']) ?></td>
                <td><?= htmlspecialchars($ubicacion['nombres']) ?></td>
                <td><?= htmlspecialchars($ubicacion['descripcion']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Modal para Agregar Ubicación -->
<div class="modal fade" id="agregarUbicacionModal" tabindex="-1" aria-labelledby="agregarUbicacionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <!-- Encabezado del Modal -->
            <div class="modal-header bg-gradient-primary text-white">
                <h3 class="modal-title fw-bold" id="agregarUbicacionModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>
                    Agregar Nueva Ubicación
                </h3>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Cuerpo del Modal -->
            <div class="modal-body p-4">
                <form id="formAgregarUbicacion" action="ubicacion/agregar_ubicacion.php" method="POST">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="id_ubicacion" id="inputIdUbicacion" required
                               oninput="validarIdUbicacion(this.value)">
                        <label for="inputIdUbicacion">ID Ubicación</label>
                        <div id="feedbackIdUbicacion" class="invalid-feedback">
                            Esta ID de ubicación ya existe en la base de datos.
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Planta</label>
                        <div class="form-control">
                            <strong><?= htmlspecialchars($planta_usuario['nombres']) ?></strong>
                            <input type="hidden" name="id_planta" value="<?= htmlspecialchars($planta_usuario['id_planta']) ?>">
                        </div>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="descripcion" id="descripcion" required
                               maxlength="40" oninput="validarLongitudDescripcion(this, 'feedbackDescripcion')">
                        <label>Descripción</label>
                        <div id="feedbackDescripcion" class="invalid-feedback">
                            La descripción no puede exceder los 40 caracteres.
                        </div>
                        <small id="contadorDescripcion" class="form-text text-muted">0/40 caracteres</small>
                    </div>
                    
                    <!-- Pie del Modal -->
                    <div class="modal-footer bg-light mt-4">
                        <button type="submit" class="btn btn-danger" id="btnGuardarUbicacion" disabled>
                             Guardar Ubicación
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Editar Ubicación -->
<div class="modal fade" id="editarUbicacionModal" tabindex="-1" aria-labelledby="editarUbicacionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <!-- Encabezado del Modal -->
            <div class="modal-header bg-gradient-primary text-white">
                <h3 class="modal-title fw-bold" id="editarUbicacionModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>
                    Editar Ubicación
                </h3>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Cuerpo del Modal -->
            <div class="modal-body p-4">
                <form id="formEditarUbicacion" action="ubicacion/editar_ubicacion.php" method="POST">
                    <input type="hidden" id="edit_id_ubicacion" name="id_ubicacion">
                    
                    <div class="mb-3">
                        <label class="form-label">Planta</label>
                        <div class="form-control">
                            <strong><?= htmlspecialchars($planta_usuario['nombres']) ?></strong>
                            <input type="hidden" id="edit_id_planta" name="id_planta" value="<?= htmlspecialchars($planta_usuario['id_planta']) ?>">
                        </div>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="edit_descripcion" name="descripcion" required
                               maxlength="40" oninput="validarLongitudDescripcion(this, 'feedbackEditDescripcion')">
                        <label>Descripción</label>
                        <div id="feedbackEditDescripcion" class="invalid-feedback">
                            La descripción no puede exceder los 40 caracteres.
                        </div>
                        <small id="contadorEditDescripcion" class="form-text text-muted">0/40 caracteres</small>
                    </div>
                    
                    <!-- Pie del Modal -->
                    <div class="modal-footer bg-light mt-4">
                        <button type="submit" class="btn btn-danger">
                             Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/jquery-3.6.0.min.js"></script>
<script src="assets/js/popper.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>

<script>
let IDSeleccionado = null;

// Función para mostrar alertas flotantes
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

// Función para validar la longitud de la descripción
function validarLongitudDescripcion(input, feedbackId) {
    const maxLength = 40;
    const currentLength = input.value.length;
    const feedbackElement = document.getElementById(feedbackId);
    const counterElement = document.getElementById(feedbackId === 'feedbackDescripcion' ? 'contadorDescripcion' : 'contadorEditDescripcion');
    
    // Actualizar contador
    counterElement.textContent = `${currentLength}/${maxLength} caracteres`;
    
    if (currentLength > maxLength) {
        input.classList.add('is-invalid');
        feedbackElement.style.display = 'block';
        // Recortar el texto si excede el límite
        input.value = input.value.substring(0, maxLength);
        counterElement.textContent = `${maxLength}/${maxLength} caracteres`;
    } else {
        input.classList.remove('is-invalid');
        feedbackElement.style.display = 'none';
    }
}

// Función para seleccionar una ubicación
function seleccionar(id_ubicacion) {
    document.querySelectorAll('#tabla-ubicacion tr').forEach(tr => {
        tr.classList.remove('selected-row');
    });

    const filaSeleccionada = document.querySelector(`#tabla-ubicacion tr[onclick*="${id_ubicacion}"]`);
    if (filaSeleccionada) {
        filaSeleccionada.classList.add('selected-row');
    }

    IDSeleccionado = id_ubicacion;
    document.getElementById('btnEditar').disabled = false;
}

// Función para filtrar ubicaciones
function filtrarUbicacion() {
    const input = document.getElementById('search');
    const filter = input.value.toUpperCase();
    const table = document.getElementById('tabla-ubicacion');
    const rows = table.getElementsByTagName('tr');

    for (let i = 0; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        if (cells.length > 1) {
            const idUbicacion = cells[0].textContent || cells[0].innerText;
            const nombrePlanta = cells[1].textContent || cells[1].innerText;
            
            if (idUbicacion.toUpperCase().indexOf(filter) > -1 || nombrePlanta.toUpperCase().indexOf(filter) > -1) {
                rows[i].style.display = '';
            } else {
                rows[i].style.display = 'none';
            }
        }
    }
}

// Evento para cargar los datos en el modal de edición
document.getElementById('editarUbicacionModal').addEventListener('shown.bs.modal', function() {
    if (!IDSeleccionado) {
        showFloatingAlert('Por favor seleccione una ubicación primero', 'warning');
        $(this).modal('hide');
        return;
    }
    
    fetch(`ubicacion/obtener_detalles_ubicacion.php?id_ubicacion=${IDSeleccionado}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.json();
        })
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            document.getElementById('edit_id_ubicacion').value = data.id_ubicacion;
            document.getElementById('edit_id_planta').value = data.id_planta;
            document.getElementById('edit_descripcion').value = data.descripcion;
            // Actualizar contador al cargar los datos
            validarLongitudDescripcion(document.getElementById('edit_descripcion'), 'feedbackEditDescripcion');
        })
        .catch(error => {
            console.error('Error al obtener los detalles:', error);
            showFloatingAlert('Error al cargar los datos: ' + error.message, 'danger');
            $(this).modal('hide');
        });
});



// Función para validar ID de ubicación en agregar
function validarIdUbicacion(idUbicacion) {
    if (idUbicacion.trim() === '') {
        document.getElementById('inputIdUbicacion').classList.remove('is-invalid');
        document.getElementById('btnGuardarUbicacion').disabled = true;
        return;
    }

    fetch(`ubicacion/validar_id_ubicacion.php?id_ubicacion=${encodeURIComponent(idUbicacion)}`)
        .then(response => response.json())
        .then(data => {
            const inputId = document.getElementById('inputIdUbicacion');
            const btnGuardar = document.getElementById('btnGuardarUbicacion');
            
            if (data.existe) {
                inputId.classList.add('is-invalid');
                btnGuardar.disabled = true;
            } else {
                inputId.classList.remove('is-invalid');
                btnGuardar.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error al validar ID de ubicación:', error);
            document.getElementById('btnGuardarUbicacion').disabled = true;
        });
}

// Configurar el formulario de agregar
const formAgregarUbicacion = document.getElementById('formAgregarUbicacion');
if (formAgregarUbicacion) {
    formAgregarUbicacion.addEventListener('submit', function(event) {
        event.preventDefault();
        
        const descripcion = document.getElementById('descripcion');
        if (descripcion.value.length > 40) {
            descripcion.classList.add('is-invalid');
            document.getElementById('feedbackDescripcion').style.display = 'block';
            return;
        }
        
        if (!this.checkValidity()) {
            event.stopPropagation();
            this.classList.add('was-validated');
            return;
        }
        
        const formData = new FormData(this);
        
        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.text();
        })
        .then(() => {
            showFloatingAlert('Ubicación agregada exitosamente!', 'success');
            setTimeout(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('agregarUbicacionModal'));
                modal.hide();
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            }, 1000);
        })
        .catch(error => {
            console.error('Error:', error);
            showFloatingAlert('Error al agregar ubicación: ' + error.message, 'danger');
        });
    });
}

// Configurar el formulario de editar
const formEditarUbicacion = document.getElementById('formEditarUbicacion');
if (formEditarUbicacion) {
    formEditarUbicacion.addEventListener('submit', function(event) {
        event.preventDefault();
        
        const descripcion = document.getElementById('edit_descripcion');
        if (descripcion.value.length > 40) {
            descripcion.classList.add('is-invalid');
            document.getElementById('feedbackEditDescripcion').style.display = 'block';
            return;
        }
        
        if (!this.checkValidity()) {
            event.stopPropagation();
            this.classList.add('was-validated');
            return;
        }
        
        const formData = new FormData(this);
        
        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.text();
        })
        .then(() => {
            showFloatingAlert('Ubicación editada exitosamente!', 'success');
            setTimeout(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('editarUbicacionModal'));
                modal.hide();
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            }, 1000);
        })
        .catch(error => {
            console.error('Error:', error);
            showFloatingAlert('Error al editar ubicación: ' + error.message, 'danger');
        });
    });
}

// Resetear modal de agregar al cerrar
document.getElementById('agregarUbicacionModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('inputIdUbicacion').value = '';
    document.getElementById('descripcion').value = '';
    document.getElementById('inputIdUbicacion').classList.remove('is-invalid');
    document.getElementById('btnGuardarUbicacion').disabled = true;
    document.getElementById('contadorDescripcion').textContent = '0/40 caracteres';
});

// Vincular eventos al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    // Mostrar alerta si hay un parámetro de éxito en la URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success')) {
        let message = '';
        switch(urlParams.get('success')) {
            case 'ubicacion_agregada':
                message = 'Ubicación agregada exitosamente!';
                break;
            case 'ubicacion_editada':
                message = 'Ubicación editada exitosamente!';
                break;
            case 'ubicacion_eliminada':
                message = 'Ubicación eliminada exitosamente!';
                break;
        }
        if (message) {
            showFloatingAlert(message, 'success');
        }
    }
    
    if (urlParams.has('error')) {
        let message = '';
        switch(urlParams.get('error')) {
            case 'id_existente':
                message = 'Error: El ID de ubicación ya existe';
                break;
            case 'error_bd':
                message = 'Error en la base de datos';
                break;
        }
        if (message) {
            showFloatingAlert(message, 'danger');
        }
    }

    // Vincular eventos de clic a las filas de la tabla
    const filas = document.querySelectorAll('#tabla-ubicacion tr');
    filas.forEach(fila => {
        fila.addEventListener('click', function() {
            seleccionar(this.getAttribute('onclick').match(/'([^']+)'/)[1]);
        });
    });
});
</script>
<?php


require_once __DIR__ . '/../includes/conexion.php';
require_once __DIR__ . '/../includes/check_permission.php';
require_once __DIR__ . '/../includes/audit.php';

// Mostrar alertas si existen
$alertType = '';
$alertMessage = '';

if (isset($_GET['success'])) {
    $alertType = 'success';
    switch ($_GET['success']) {
        case 'planta_agregada':
            $alertMessage = 'Planta agregada exitosamente!';
            break;
        case 'planta_actualizada':
            $alertMessage = 'Planta actualizada exitosamente!';
            break;
    }
} elseif (isset($_GET['error'])) {
    $alertType = 'danger';
    switch ($_GET['error']) {
        case 'planta_existente':
            $alertMessage = 'Error: La planta ya existe en la base de datos';
            break;
        case 'error_bd':
            $alertMessage = 'Error en la base de datos. Por favor intente nuevamente';
            break;
        case 'datos_invalidos':
            $alertMessage = 'Error: Datos inválidos recibidos';
            break;
        default:
            $alertMessage = 'Error desconocido';
    }
}

// Consulta principal SIN paginación
$stmt = $pdo->query("
    SELECT id_planta, nombres 
    FROM planta WHERE estado = 'activo'
    ORDER BY id_planta
");
$plantas = $stmt->fetchAll();
logModuleAccess('planta');

?>

<link href="assets/css/bootstrap.min.css" rel="stylesheet">


<!-- Container for floating alerts -->
<div id="alertContainer"></div>

<?php if ($alertMessage): ?>
<div class="alert alert-<?= $alertType ?> alert-auto-close alert-dismissible fade show" role="alert">
    <?= $alertMessage ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="sticky-top bg-white p-3 shadow-sm">
    <h4 class="text-center mb-1">Gestión de Plantas</h4>

    <!-- Barra de búsqueda y botones -->
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex gap-2">
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#agregarPlantaModal">Agregar Planta</button>
            <button id="btnEditar" class="btn btn-danger" disabled data-bs-toggle="modal" data-bs-target="#editarPlantaModal">Editar</button>
        </div>
        <div style="width: 300px;">
            <input type="text" id="search" class="form-control" placeholder="Buscar por Nombre de la Planta" onkeyup="filtrarPlantas()">
        </div>
    </div>
</div>

<!-- Alternativa usando el índice del array -->
<table class="table table-striped">
    <thead class="table-dark">
        <tr>
            <th>#</th>
            <th>Nombre de la planta</th>
        </tr>
    </thead>
    <tbody id="tabla-plantas">
        <?php foreach ($plantas as $index => $planta): ?>
            <tr onclick="seleccionarPlanta('<?= $planta['id_planta'] ?>')" style="cursor: pointer;">
                <td><?= $index + 1 ?></td>  <!-- index + 1 para que empiece en 1 -->
                <td><?= htmlspecialchars($planta['nombres']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>



<!-- Modal para Agregar Planta -->
<div class="modal fade" id="agregarPlantaModal" tabindex="-1" aria-labelledby="agregarPlantaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <!-- Encabezado del Modal -->
            <div class="modal-header bg-gradient-primary text-white">
                <h3 class="modal-title fw-bold" id="agregarPlantaModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>
                    Agregar Nueva Planta
                </h3>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Cuerpo del Modal -->
            <div class="modal-body p-4">
                <form id="formAgregarPlanta" action="planta/agregar_planta.php" method="POST">
                    <div class="form-floating mb-3">

                        <input type="text" class="form-control" name="nombres" id="nombresPlanta" required 
                               maxlength="30"
                               oninput="validarNombrePlanta(this.value)">
                        <label for="nombresPlanta">Nombre de la Planta (max 30 caracteres)</label>
                        <div id="feedbackNombre" class="invalid-feedback">
                            Esta planta ya existe en la base de datos o excede el límite de caracteres.
                        </div>
                        <div class="text-end small text-muted">
                            <span id="charCount">0</span>/30 caracteres
                        </div>
                    </div>
                    
                    <!-- Pie del Modal -->
                    <div class="modal-footer bg-light mt-4">
                        <button type="submit" class="btn btn-primary" id="btnGuardarPlanta" disabled>
                            <i class="bi bi-save me-1"></i> Guardar Planta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Editar Planta -->
<div class="modal fade" id="editarPlantaModal" tabindex="-1" aria-labelledby="editarPlantaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <!-- Encabezado del Modal -->
            <div class="modal-header bg-gradient-primary text-white">
                <h3 class="modal-title fw-bold" id="editarPlantaModalLabel">
                    <i class="bi bi-pencil-square me-2"></i>
                    Editar Planta
                </h3>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Cuerpo del Modal -->
            <div class="modal-body p-4">
                <form id="formEditarPlanta" action="planta/editar_planta.php" method="POST">
                    <input type="hidden" id="edit_id_planta" name="id_planta">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="edit_nombres" name="nombres" required
                               maxlength="30"
                               oninput="validarNombrePlantaEdicion(this.value, document.getElementById('edit_id_planta').value)">
                        <label for="edit_nombres">Nombre de la Planta (max 30 caracteres)</label>
                        <div id="feedbackNombreEdicion" class="invalid-feedback">
                            Esta planta ya existe en la base de datos o excede el límite de caracteres.
                        </div>
                        <div class="text-end small text-muted">
                            <span id="editCharCount">0</span>/30 caracteres
                        </div>
                    </div>
                    
                    <!-- Pie del Modal -->
                    <div class="modal-footer bg-light mt-4">
                        <button type="submit" class="btn btn-primary" id="btnGuardarEdicion" disabled>
                            <i class="bi bi-save me-1"></i> Guardar Cambios
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
    
    // Contador de caracteres para agregar planta
    const nombresPlantaInput = document.getElementById('nombresPlanta');
    if (nombresPlantaInput) {
        nombresPlantaInput.addEventListener('input', function() {
            document.getElementById('charCount').textContent = this.value.length;
        });
    }
    
    // Contador de caracteres para editar planta
    const editNombresInput = document.getElementById('edit_nombres');
    if (editNombresInput) {
        editNombresInput.addEventListener('input', function() {
            document.getElementById('editCharCount').textContent = this.value.length;
        });
    }
});

let plantaSeleccionada = null;

// Función para seleccionar una planta
function seleccionarPlanta(id_planta) {
    // Remover la clase 'selected-row' de todas las filas
    document.querySelectorAll('#tabla-plantas tr').forEach(tr => {
        tr.classList.remove('selected-row');
    });

    // Agregar la clase 'selected-row' a la fila seleccionada
    const filaSeleccionada = document.querySelector(`#tabla-plantas tr[onclick*="${id_planta}"]`);
    if (filaSeleccionada) {
        filaSeleccionada.classList.add('selected-row');
    }

    // Guardar toda la información de la planta seleccionada
    plantaSeleccionada = {
        id: id_planta,
        nombre: filaSeleccionada.querySelector('td:nth-child(2)').textContent
    };

    // Habilitar los botones de acciones
    document.getElementById('btnEditar').disabled = false;
}


// Función para validar si el nombre de la planta ya existe
function validarNombrePlanta(nombre) {
    const inputNombre = document.getElementById('nombresPlanta');
    const btnGuardar = document.getElementById('btnGuardarPlanta');
    
    // Validar longitud máxima
    if (nombre.length > 30) {
        inputNombre.classList.add('is-invalid');
        btnGuardar.disabled = true;
        return;
    }
    
    if (nombre.trim() === '') {
        inputNombre.classList.remove('is-invalid');
        btnGuardar.disabled = true;
        return;
    }

    fetch(`planta/validar_planta.php?nombre=${encodeURIComponent(nombre)}`)
        .then(response => response.json())
        .then(data => {            
            if (data.existe) {
                inputNombre.classList.add('is-invalid');
                btnGuardar.disabled = true;
            } else {
                inputNombre.classList.remove('is-invalid');
                btnGuardar.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error al validar planta:', error);
            btnGuardar.disabled = true;
        });
}

// Función para validar nombre en edición
function validarNombrePlantaEdicion(nombre, idActual) {
    const inputNombre = document.getElementById('edit_nombres');
    const btnGuardar = document.getElementById('btnGuardarEdicion');
    
    // Validar longitud máxima
    if (nombre.length > 30) {
        inputNombre.classList.add('is-invalid');
        btnGuardar.disabled = true;
        return;
    }
    
    if (nombre.trim() === '') {
        inputNombre.classList.remove('is-invalid');
        btnGuardar.disabled = true;
        return;
    }

    fetch(`planta/validar_planta_edicion.php?nombre=${encodeURIComponent(nombre)}&id_actual=${encodeURIComponent(idActual)}`)
        .then(response => response.json())
        .then(data => {
            if (data.existe) {
                inputNombre.classList.add('is-invalid');
                btnGuardar.disabled = true;
            } else {
                inputNombre.classList.remove('is-invalid');
                btnGuardar.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error al validar planta:', error);
            btnGuardar.disabled = true;
        });
}

// Función para filtrar plantas (búsqueda)
function filtrarPlantas() {
    const searchValue = document.getElementById('search').value.trim();
    
    if (searchValue === "") {
        window.location.reload();
        return;
    }

    fetch(`planta/buscar_plantas.php?search=${encodeURIComponent(searchValue)}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('tabla-plantas').innerHTML = data;
            plantaSeleccionada = null;
            document.getElementById('btnEditar').disabled = true;
        })
        .catch(error => {
            console.error('Error al buscar plantas:', error);
            showFloatingAlert('Error al buscar plantas', 'danger');
        });
}

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

// Configurar formulario de agregar con AJAX
const formAgregarPlanta = document.getElementById('formAgregarPlanta');
if (formAgregarPlanta) {
    formAgregarPlanta.addEventListener('submit', function(event) {
        event.preventDefault();
        
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
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error('Error en la respuesta del servidor');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showFloatingAlert('Planta agregada exitosamente!', 'success');
                setTimeout(() => {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('agregarPlantaModal'));
                    modal.hide();
                    setTimeout(() => window.location.reload(), 500);
                }, 1000);
            } else {
                showFloatingAlert('Error: ' + (data.error || 'No se pudo agregar la planta'), 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showFloatingAlert('Error en el servidor. Por favor verifica la consola para más detalles.', 'danger');
        });
    });
}

// Configurar formulario de edición con AJAX
const formEditarPlanta = document.getElementById('formEditarPlanta');
if (formEditarPlanta) {
    formEditarPlanta.addEventListener('submit', function(event) {
        event.preventDefault();
        
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
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) throw new Error('Error en la respuesta del servidor');
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showFloatingAlert('Planta actualizada exitosamente!', 'success');
                setTimeout(() => {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editarPlantaModal'));
                    modal.hide();
                    setTimeout(() => window.location.reload(), 500);
                }, 1000);
            } else {
                showFloatingAlert('Error: ' + (data.error || 'No se pudo actualizar la planta'), 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showFloatingAlert('Error en el servidor. Por favor verifica la consola para más detalles.', 'danger');
        });
    });
}

// Resetear el modal cuando se cierre
document.getElementById('agregarPlantaModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('nombresPlanta').value = '';
    document.getElementById('nombresPlanta').classList.remove('is-invalid');
    document.getElementById('btnGuardarPlanta').disabled = true;
    document.getElementById('charCount').textContent = '0';
});

// Configurar el modal de edición cuando se muestra
document.getElementById('editarPlantaModal').addEventListener('show.bs.modal', function(event) {
    if (!plantaSeleccionada) return;
    
    // Cargar los datos de la planta seleccionada en el formulario
    document.getElementById('edit_id_planta').value = plantaSeleccionada.id;
    document.getElementById('edit_nombres').value = plantaSeleccionada.nombre;
    document.getElementById('editCharCount').textContent = plantaSeleccionada.nombre.length;
    
    // Habilitar el botón de guardar (asumiendo que el nombre es válido)
    document.getElementById('btnGuardarEdicion').disabled = false;
});



</script>
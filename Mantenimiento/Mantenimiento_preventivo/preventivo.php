<?php
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/../../includes/audit.php';


// Obtener el id_usuario de la sesión
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

// Obtener parámetros con valores por defecto y sanitización
$searchTag = isset($_GET['searchTag']) ? trim($_GET['searchTag']) : '';
$searchFechaInicio = isset($_GET['searchFechaInicio']) ? trim($_GET['searchFechaInicio']) : '';
$searchFechaFin = isset($_GET['searchFechaFin']) ? trim($_GET['searchFechaFin']) : '';

// Construir consulta base con filtros
$where = "m.Tag_Number IN (SELECT e.Tag_Number FROM equipos e 
                          JOIN ubicacion u ON e.id_ubicacion = u.id_ubicacion 
                          WHERE u.id_planta = :id_planta)";
$params = [':id_planta' => $id_planta_usuario];

if (!empty($searchTag)) {
    $where .= " AND m.Tag_Number LIKE :tag";
    $params[':tag'] = "%$searchTag%";
}

if (!empty($searchFechaInicio) && !empty($searchFechaFin)) {
    // Búsqueda por rango de fechas
    $where .= " AND m.fecha BETWEEN :fechaInicio AND :fechaFin";
    $params[':fechaInicio'] = $searchFechaInicio;
    $params[':fechaFin'] = $searchFechaFin;
} elseif (!empty($searchFechaInicio)) {
    // Búsqueda por fecha única (usando solo fecha inicio)
    $where .= " AND m.fecha = :fechaInicio";
    $params[':fechaInicio'] = $searchFechaInicio;
} elseif (!empty($searchFechaFin)) {
    // Búsqueda por fecha única (usando solo fecha fin)
    $where .= " AND m.fecha = :fechaFin";
    $params[':fechaFin'] = $searchFechaFin;
}

// Consulta para los datos (sin límite ni offset)
// Consulta para los datos (sin límite ni offset)
$query = "SELECT m.*, u.descripcion as ubicacion 
          FROM mantenimiento m
          JOIN equipos e ON m.Tag_Number = e.Tag_Number
          JOIN ubicacion u ON e.id_ubicacion = u.id_ubicacion
          WHERE $where ORDER BY m.fecha DESC";
          
$stmt = $pdo->prepare($query);

// Bind parameters
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$intervenciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener los Tag_Number de la tabla equipos que pertenecen a la planta del usuario
$queryTags = "SELECT e.Tag_Number 
              FROM equipos e
              JOIN ubicacion u ON e.id_ubicacion = u.id_ubicacion
              WHERE e.estado = 'activo' AND u.id_planta = ?";
$stmtTags = $pdo->prepare($queryTags);
$stmtTags->execute([$id_planta_usuario]);
$tags = $stmtTags->fetchAll(PDO::FETCH_COLUMN);

if (isset($_GET['success'])) {
    $successMessage = '';
    if ($_GET['success'] === 'add') {
        $successMessage = 'Mantenimiento agregado exitosamente!';
    } elseif ($_GET['success'] === 'delete') {
        $successMessage = 'Registro eliminado exitosamente!';
    }
}
$queryUbicaciones = "SELECT u.id_ubicacion, u.descripcion 
                     FROM ubicacion u
                     WHERE u.id_planta = ? AND u.estado = 'activo'";
$stmtUbicaciones = $pdo->prepare($queryUbicaciones);
$stmtUbicaciones->execute([$id_planta_usuario]);
$ubicaciones = $stmtUbicaciones->fetchAll(PDO::FETCH_ASSOC);

logModuleAccess('Mantenimiento Preventivo');



?>


<link href="assets/css/bootstrap.min.css" rel="stylesheet">


<div class="sticky-top bg-white p-3 shadow-sm">
    <h4 class="text-center mb-3"> Mantenimiento Preventivo</h4>

    <!-- Barra de herramientas y búsqueda -->
    <div class="d-flex justify-content-between align-items-center">
        <!-- Botones de acción -->
        <div class="d-flex gap-2">
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#agregarModal">
                Agregar Mantenimiento
            </button>
            <button id="btnEliminar" class="btn btn-danger" disabled>
                Eliminar
            </button>
        </div>
        <button type="button" class="btn btn-danger" id="btnImprimirTabla" onclick="generarPDF()">Imprimir Registros</button>
    </div>

    <div class="row mt-3">
    <div class="col-md-4">
        <label for="searchTerm" class="form-label">Buscar por:</label>
        <div class="input-group">
            <select class="form-select" id="searchType" style="max-width: 120px;">
                <option value="tag">Tag</option>
                <option value="ubicacion">Ubicación</option>
            </select>
            <input type="text" class="form-control" id="searchTerm" placeholder="Ingrese término de búsqueda">
        </div>
    </div>

    <div class="col-md-6">
        <label for="searchFecha" class="form-label">Buscar por Fecha:</label>
        <div class="input-group">
            <input type="date" class="form-control" id="searchFechaInicio" placeholder="Fecha inicio">
            <span class="input-group-text">a</span>
            <input type="date" class="form-control" id="searchFechaFin" placeholder="Fecha fin">
        </div>
    </div>
</div>
</div>

<!-- Contenedor para alertas flotantes -->
<div id="alertContainer" style="position: fixed; top: 20px; right: 20px; z-index: 1100;"></div>

<!-- Alertas de éxito (mantén la existente para recargas de página) -->
<?php if (!empty($successMessage)): ?>
<div class="alert alert-success alert-auto-close alert-dismissible fade show" role="alert" style="position: fixed; top: 20px; right: 20px; z-index: 1100;">
    <?= $successMessage ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<table class="table table-striped" id="tabla-mantenimientos">
    <thead class="table-dark">
        <tr>
            <th>Tag Number</th>
            <th>Fecha</th>
            <th>Orden</th>
            <th>Observaciones</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($intervenciones as $row): ?>
        <tr data-id='<?= $row['id_mantenimiento'] ?>' data-ubicacion='<?= htmlspecialchars($row['ubicacion']) ?>'>
            <td>
                <a href='Mantenimiento/Mantenimiento_preventivo/detalle.php?id=<?= $row['id_mantenimiento'] ?>' class='text-danger'>
                    <?= htmlspecialchars($row['Tag_Number']) ?>
                </a>
            </td>
            <td><?= htmlspecialchars($row['fecha']) ?></td>
            <td><?= htmlspecialchars($row['orden']) ?></td>
            <td><?= htmlspecialchars($row['observaciones']) ?></td>
        </tr>
    <?php endforeach; ?>
</tbody>
</table>

<!-- Modal para Agregar Mantenimiento -->
<div class="modal fade" id="agregarModal" tabindex="-1" aria-labelledby="agregarModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <!-- Encabezado del Modal -->
            <div class="modal-header bg-gradient-primary text-white">
                <h3 class="modal-title fw-bold" id="agregarModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>
                    Agregar Mantenimiento
                </h3>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Cuerpo del Modal -->
            <div class="modal-body p-4">
                <form id="formAgregarMantenimiento" action="Mantenimiento/Mantenimiento_preventivo/agregar_preventivo.php" method="POST" class="needs-validation" novalidate>
                    <div class="row g-3">
                        <!-- Nueva fila para ubicación -->
                        <div class="col-md-6">
                            <div class="form-floating">
                                <select class="form-select" id="ubicacion" name="ubicacion" required>
                                    <option value="" selected disabled>Seleccione una ubicación</option>
                                    <?php foreach ($ubicaciones as $ubicacion): ?>
                                        <option value="<?= $ubicacion['id_ubicacion'] ?>">
                                            <?= htmlspecialchars($ubicacion['descripcion']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="ubicacion">Ubicación *</label>
                                <div class="invalid-feedback">
                                    Por favor seleccione una ubicación.
                                </div>
                            </div>
                        </div>
                        
                        <!-- Columna 1 -->
                        <div class="col-md-6">
                            <div class="form-floating">
                                <select class="form-select" id="tag_number" name="tag_number" required disabled>
                                    <option value="" selected disabled>Primero seleccione una ubicación</option>
                                </select>
                                <label for="tag_number">Tag Number *</label>
                                <div class="invalid-feedback">
                                    Por favor seleccione un Tag Number.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="id_tipo" name="id_tipo" required readonly>
                                <label for="id_tipo">Detalles Instrumento *</label>
                                <div class="invalid-feedback">
                                    Este campo es obligatorio.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="date" class="form-control" id="fecha" name="fecha" required
                                       min="2000-01-01" max="<?= date('Y-m-d') ?>">
                                <label for="fecha">Fecha *</label>
                                <div class="invalid-feedback">
                                    La fecha debe estar entre 2000-01-01 y la fecha actual.
                                </div>
                            </div>
                        </div>
                        
                        <!-- Columna 2 -->
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="planta" name="planta" required readonly>
                                <label for="planta">Planta *</label>
                                <div class="invalid-feedback">
                                    Este campo es obligatorio.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="orden" name="orden" required
                                       maxlength="15" pattern=".{1,15}">
                                <label for="orden">Orden *</label>
                                <div class="invalid-feedback">
                                    Máximo 15 caracteres permitidos.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="instalacion" name="instalacion" required
                                       maxlength="20" pattern=".{1,20}">
                                <label for="instalacion">Instalación *</label>
                                <div class="invalid-feedback">
                                    Máximo 20 caracteres permitidos.
                                </div>
                            </div>
                        </div>
                        
                        <!-- Columna 3 -->
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="serial" name="serial" required
                                       maxlength="20" pattern=".{1,20}">
                                <label for="serial">Serial *</label>
                                <div class="invalid-feedback">
                                    Máximo 20 caracteres permitidos.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="modelo" name="modelo" required
                                       maxlength="20" pattern=".{1,20}">
                                <label for="modelo">Modelo *</label>
                                <div class="invalid-feedback">
                                    Máximo 20 caracteres permitidos.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="mantenimiento" name="mantenimiento" value="Preventivo" required readonly>
                                <label for="mantenimiento">Mantenimiento *</label>
                                <div class="invalid-feedback">
                                    Este campo es obligatorio.
                                </div>
                            </div>
                        </div>
                        
                        <!-- Columna 4 -->
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="medicion_metrica" name="medicion_metrica" required
                                       maxlength="20" pattern=".{1,20}">
                                <label for="medicion_metrica">Medición Métrica *</label>
                                <div class="invalid-feedback">
                                    Máximo 20 caracteres permitidos.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="24vdc" name="24vdc" required
                                       maxlength="20" pattern=".{1,20}">
                                <label for="24vdc">24VDC *</label>
                                <div class="invalid-feedback">
                                    Máximo 20 caracteres permitidos.
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="text" class="form-control" id="sintomas" name="sintomas" required
                                       maxlength="20" pattern=".{1,20}">
                                <label for="sintomas">Síntomas *</label>
                                <div class="invalid-feedback">
                                    Máximo 20 caracteres permitidos.
                                </div>
                            </div>
                        </div>
                        
                        <!-- Columna 5 -->
                        <div class="col-12">
                            <div class="form-floating">
                                <textarea class="form-control" id="observaciones" name="observaciones" required
                                          maxlength="70" style="height: 100px"></textarea>
                                <label for="observaciones">Observaciones *</label>
                                <div class="invalid-feedback">
                                    Máximo 70 caracteres permitidos.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pie del Modal -->
                    <div class="modal-footer bg-light mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Agregar Mantenimiento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
function generarPDF() {
    const searchTerm = document.getElementById('searchTerm').value;
    const searchType = document.getElementById('searchType').value;
    const searchFechaInicio = document.getElementById('searchFechaInicio').value;
    const searchFechaFin = document.getElementById('searchFechaFin').value;
    
    let url = `Mantenimiento/Mantenimiento_preventivo/generar_pdf_preventivo.php?searchTerm=${searchTerm}&searchType=${searchType}`;
    
    if (searchFechaInicio) {
        url += `&searchFechaInicio=${searchFechaInicio}`;
    }
    
    if (searchFechaFin) {
        url += `&searchFechaFin=${searchFechaFin}`;
    }
    
    window.open(url, '_blank');
}

document.addEventListener('DOMContentLoaded', function() {
    // Variables globales
    let selectedId = null;
    let selectedRow = null;
    
    // Auto-cierre de alertas después de 5 segundos
    const alerts = document.querySelectorAll('.alert-auto-close');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
    // Configurar validación del formulario
    const formAgregarMantenimiento = document.getElementById('formAgregarMantenimiento');
    if (formAgregarMantenimiento) {
        formAgregarMantenimiento.addEventListener('submit', function(event) {
            event.preventDefault();
            
            if (!this.checkValidity()) {
                event.stopPropagation();
                this.classList.add('was-validated');
                return;
            }
            
            // Enviar el formulario con AJAX
            const formData = new FormData(this);
            
            fetch(this.action, {
    method: 'POST',
    body: formData,
    headers: {
        'X-Requested-With': 'XMLHttpRequest', // Esto identifica la petición como AJAX
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
        showFloatingAlert('Registro agregado exitosamente!', 'success');
        // Cerrar modal y recargar
        setTimeout(() => {
            const modal = bootstrap.Modal.getInstance(document.getElementById('agregarModal'));
            modal.hide();
            // Redirigir a la URL correcta
            window.location.href = 'index.php?tabla=preventivo&success=add';
                }, 1000);
    } else {
        showFloatingAlert('Error: ' + (data.error || 'No se pudo agregar el registro'), 'danger');
    }
})





.catch(error => {
    console.error('Error:', error);
    showFloatingAlert('Error en el servidor. Por favor verifica la consola para más detalles.', 'danger');
});
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
    
    // Cargar datos del equipo cuando se muestra el modal
    const agregarModal = document.getElementById('agregarModal');
    if (agregarModal) {
        agregarModal.addEventListener('shown.bs.modal', function() {
            cargarDatosEquipo();
            // Disparar el evento change manualmente para cargar datos del primer elemento
            const tagSelect = document.getElementById('tag_number');
            if (tagSelect) {
                tagSelect.dispatchEvent(new Event('change'));
            }
        });
    }
    
    function cargarTagsPorUbicacion() {
    const ubicacionSelect = document.getElementById('ubicacion');
    const tagNumberSelect = document.getElementById('tag_number');
    
    ubicacionSelect.addEventListener('change', function() {
        const idUbicacion = this.value;
        
        if (!idUbicacion) {
            tagNumberSelect.innerHTML = '<option value="" selected disabled>Primero seleccione una ubicación</option>';
            tagNumberSelect.disabled = true;
            return;
        }
        
        // Mostrar loading en el select
        tagNumberSelect.innerHTML = '<option value="" selected disabled>Cargando equipos...</option>';
        tagNumberSelect.disabled = true;
        
        // Obtener tags para la ubicación seleccionada
        fetch(`Mantenimiento/Mantenimiento_preventivo/obtener_tags.php?id_ubicacion=${idUbicacion}`)
            .then(response => {
                if (!response.ok) throw new Error('Error en la respuesta del servidor');
                return response.json();
            })
            .then(data => {
                if (data.success && data.tags.length > 0) {
                    tagNumberSelect.innerHTML = '<option value="" selected disabled>Seleccione un Tag</option>';
                    data.tags.forEach(tag => {
                        const option = document.createElement('option');
                        option.value = tag;
                        option.textContent = tag;
                        tagNumberSelect.appendChild(option);
                    });
                    tagNumberSelect.disabled = false;
                } else {
                    tagNumberSelect.innerHTML = '<option value="" selected disabled>No hay equipos en esta ubicación</option>';
                    tagNumberSelect.disabled = true;
                }
            })
            .catch(error => {
                console.error('Error al cargar tags:', error);
                tagNumberSelect.innerHTML = '<option value="" selected disabled>Error al cargar equipos</option>';
                tagNumberSelect.disabled = true;
            });
    });
}

// Modificar la función cargarDatosEquipo para que se active cuando cambia el tag
function cargarDatosEquipo() {
    const tagNumberSelect = document.getElementById('tag_number');
    const idTipoInput = document.getElementById('id_tipo');
    const plantaInput = document.getElementById('planta');
    
    tagNumberSelect.addEventListener('change', function() {
        const selectedTag = this.value;
        
        if (!selectedTag) {
            idTipoInput.value = '';
            plantaInput.value = '';
            return;
        }
        
        // Cargar tipo de instrumento
        fetch(`Mantenimiento/Mantenimiento_preventivo/obtener_instrumento.php?tag_number=${selectedTag}`)
            .then(response => {
                if (!response.ok) throw new Error('Error en la respuesta del servidor');
                return response.json();
            })
            .then(data => {
                idTipoInput.value = data.success ? data.Instrument_Type_Desc : 'Error: ' + data.error;
            })
            .catch(error => {
                idTipoInput.value = 'Error al cargar';
                console.error('Error al obtener instrumento:', error);
            });
        
        // Cargar planta
        fetch(`Mantenimiento/Mantenimiento_preventivo/obtener_planta.php?tag_number=${selectedTag}`)
            .then(response => {
                if (!response.ok) throw new Error('Error en la respuesta del servidor');
                return response.json();
            })
            .then(data => {
                plantaInput.value = data.success ? data.planta : 'Error: ' + data.error;
            })
            .catch(error => {
                plantaInput.value = 'Error al cargar';
                console.error('Error al obtener planta:', error);
            });
    });
}

    function manejarSeleccionFilas() {
        const tabla = document.getElementById('tabla-mantenimientos');
        
        tabla.addEventListener('click', function(e) {
            // Solo manejar clics que no sean en el enlace del Tag Number
            if (e.target.closest('a.text-danger')) {
                return; // Dejar que el enlace maneje la navegación
            }
            
            const row = e.target.closest('tr[data-id]');
            if (!row) return;
            
            // Deseleccionar fila anterior
            if (selectedRow) {
                selectedRow.classList.remove('selected-row');
            }
            
            // Seleccionar nueva fila
            row.classList.add('selected-row');
            selectedRow = row;
            selectedId = row.getAttribute('data-id');
            
            // Habilitar botones
            document.getElementById('btnEliminar').disabled = false;
        });
    }
    
    // Función para manejar el botón Eliminar
    function manejarBotonEliminar() {
        document.getElementById('btnEliminar').addEventListener('click', function() {
            if (!selectedId) {
                alert('Por favor seleccione un registro primero');
                return;
            }
            
            if (confirm('¿Estás seguro de que deseas eliminar esta intervención?')) {
                window.location.href = `Mantenimiento/Mantenimiento_preventivo/borrar_preventivo.php?id=${selectedId}`;
            }
        });
    }

    function manejarFiltrado() {
    const searchTerm = document.getElementById('searchTerm');
    const searchType = document.getElementById('searchType');
    const searchFechaInicio = document.getElementById('searchFechaInicio');
    const searchFechaFin = document.getElementById('searchFechaFin');
    
    function aplicarFiltros() {
        const termValue = searchTerm.value.toUpperCase();
        const typeValue = searchType.value;
        const fechaInicioValue = searchFechaInicio.value;
        const fechaFinValue = searchFechaFin.value;
        const table = document.getElementById('tabla-mantenimientos');
        const rows = table.getElementsByTagName('tr');

        for (let i = 1; i < rows.length; i++) {
            const cells = rows[i].getElementsByTagName('td');
            if (cells.length > 0) {
                const tagNumber = cells[0].textContent || cells[0].innerText;
                const fecha = cells[1].textContent || cells[1].innerText;

                // Obtener la ubicación del atributo data-ubicacion si está disponible
                const ubicacion = rows[i].getAttribute('data-ubicacion') || '';

                const termMatch = termValue === '' || 
                    (typeValue === 'tag' && tagNumber.toUpperCase().includes(termValue)) ||
                    (typeValue === 'ubicacion' && ubicacion.toUpperCase().includes(termValue));

                let fechaMatch = true;
                if (fechaInicioValue) {
                    const fechaInicio = new Date(fechaInicioValue);
                    const fechaActual = new Date(fecha);
                    
                    if (fechaFinValue) {
                        const fechaFin = new Date(fechaFinValue);
                        fechaMatch = fechaActual >= fechaInicio && fechaActual <= fechaFin;
                    } else {
                        fechaMatch = fechaActual.toISOString().split('T')[0] === fechaInicio.toISOString().split('T')[0];
                    }
                }

                if (termMatch && fechaMatch) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        }
        
        // Resetear selección al filtrar
        if (selectedRow) {
            selectedRow.classList.remove('selected-row');
            selectedRow = null;
            selectedId = null;
            resetearBotones();
        }
    }
    
    searchTerm.addEventListener('input', aplicarFiltros);
    searchType.addEventListener('change', aplicarFiltros);
    searchFechaInicio.addEventListener('change', aplicarFiltros);
    searchFechaFin.addEventListener('change', aplicarFiltros);
}
    
    // Función para resetear los botones
    function resetearBotones() {
        document.getElementById('btnEliminar').disabled = true;
    }
    
    cargarTagsPorUbicacion();  // Nueva función
    manejarSeleccionFilas();
    manejarBotonEliminar();
    manejarFiltrado();
    
    // Modificar el evento shown.bs.modal para resetear correctamente
    const agregarModalElement = document.getElementById('agregarModal');
    if (agregarModalElement) {
        agregarModalElement.addEventListener('shown.bs.modal', function() {
            const form = document.getElementById('formAgregarMantenimiento');
            if (form) {
                form.reset();
                form.classList.remove('was-validated');
                
                // Resetear selects
                const ubicacionSelect = document.getElementById('ubicacion');
                const tagSelect = document.getElementById('tag_number');
                
                if (ubicacionSelect && ubicacionSelect.options.length > 0) {
                    ubicacionSelect.selectedIndex = 0;
                }
                
                tagSelect.innerHTML = '<option value="" selected disabled>Primero seleccione una ubicación</option>';
                tagSelect.disabled = true;
            }
        });
    }
    
    // Inicializar con botones deshabilitados
    resetearBotones();
});
</script>
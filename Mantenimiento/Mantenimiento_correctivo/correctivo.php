<?php

require_once __DIR__ . '/../../includes/check_permission.php';
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/../../includes/audit.php';

// Obtener el ID del usuario actual desde la sesión
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

// Obtener parámetros de filtrado
$searchTerm = isset($_GET['searchTerm']) ? $_GET['searchTerm'] : '';
$searchType = isset($_GET['searchType']) ? $_GET['searchType'] : 'tag'; // 'tag' o 'ubicacion'
$searchFechaInicio = isset($_GET['searchFechaInicio']) ? $_GET['searchFechaInicio'] : '';
$searchFechaFin = isset($_GET['searchFechaFin']) ? $_GET['searchFechaFin'] : '';

// Consulta para obtener los registros con paginación
$query = "SELECT i.* FROM intervencion i
          JOIN equipos e ON i.Tag_Number = e.Tag_Number
          JOIN ubicacion u ON e.id_ubicacion = u.id_ubicacion
          WHERE u.id_planta = :id_planta";
$params = [':id_planta' => $id_planta_usuario];

// Aplicar filtros adicionales si existen
if (!empty($searchTerm)) {
    if ($searchType === 'tag') {
        $query .= " AND i.Tag_Number LIKE :tag";
        $params[':tag'] = "%$searchTerm%";
    } elseif ($searchType === 'ubicacion') {
        $query .= " AND u.descripcion LIKE :ubicacion";
        $params[':ubicacion'] = "%$searchTerm%";
    }
}

if (!empty($searchFechaInicio)) {
    if (empty($searchFechaFin)) {
        // Solo fecha inicio - buscar ese día específico
        $query .= " AND i.fecha = :fecha_inicio";
        $params[':fecha_inicio'] = $searchFechaInicio;
    } else {
        // Rango de fechas
        $query .= " AND i.fecha BETWEEN :fecha_inicio AND :fecha_fin";
        $params[':fecha_inicio'] = $searchFechaInicio;
        $params[':fecha_fin'] = $searchFechaFin;
    }
}

// Ordenar por fecha descendente
$query .= " ORDER BY i.fecha DESC";

// Preparar y ejecutar la consulta
$stmt = $pdo->prepare($query);
foreach ($params as $key => $value) {
    $paramType = PDO::PARAM_STR;
    $stmt->bindValue($key, $value, $paramType);
}
$stmt->execute();
$intervenciones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Consulta para obtener las ubicaciones de la planta del usuario
$ubicaciones_query = $pdo->prepare("
    SELECT id_ubicacion, descripcion 
    FROM ubicacion 
    WHERE id_planta = ? AND estado = 'activo'
    ORDER BY descripcion
");
$ubicaciones_query->execute([$id_planta_usuario]);
$ubicaciones = $ubicaciones_query->fetchAll(PDO::FETCH_ASSOC);

logModuleAccess('Mantenimiento Correctivo');

?>

<!-- Contenedor para alertas flotantes -->
<div id="alertContainer"></div>

<!-- Cabecera fija -->
<div class="sticky-top bg-white p-3 shadow-sm">
    <h4 class="text-center mb-3">Mantenimiento Correctivo</h4>
    
    <div class="d-flex justify-content-between align-items-center">
        <!-- Botones de acción -->
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#agregarModal">Agregar Mantenimiento</button>
            <button type="button" class="btn btn-danger" id="btnEliminar" disabled>Eliminar</button>
        </div>
        
        <!-- Botón de imprimir tabla (a la derecha) -->
        <button type="button" class="btn btn-danger" id="btnImprimirTabla" onclick="generarPDF()">Imprimir Registros</button>
    </div>
    
    <!-- Filtros (debajo de los botones) -->
    <div class="row mt-3">
        <div class="col-md-4">
            <label for="searchTerm" class="form-label">Buscar por:</label>
            <div class="input-group">
                <select class="form-select" id="searchType" style="max-width: 120px;">
                    <option value="tag" <?= $searchType === 'tag' ? 'selected' : '' ?>>Tag</option>
                    <option value="ubicacion" <?= $searchType === 'ubicacion' ? 'selected' : '' ?>>Ubicación</option>
                </select>
                <input type="text" class="form-control" id="searchTerm" placeholder="Ingrese término de búsqueda" value="<?= htmlspecialchars($searchTerm) ?>">
            </div>
        </div>
        <div class="col-md-6">
            <label for="searchFecha" class="form-label">Buscar por Fecha:</label>
            <div class="input-group">
                <input type="date" class="form-control" id="searchFechaInicio" placeholder="Fecha inicio" value="<?= htmlspecialchars($searchFechaInicio) ?>">
                <span class="input-group-text">a</span>
                <input type="date" class="form-control" id="searchFechaFin" placeholder="Fecha fin" value="<?= htmlspecialchars($searchFechaFin) ?>">
            </div>
        </div>
    </div>
</div>

<table class="table table-fixed" id="tabla-intervenciones">
    <thead class="table-dark">
        <tr>
            <th style="width: 15%;">Tag Number</th>
            <th style="width: 25%;">Ubicación</th>
            <th style="width: 30%;">Descripción</th>
            <th style="width: 15%;">Fecha</th>
            <th style="width: 15%;">Responsable</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($intervenciones as $row): 
        // Obtener información de ubicación para cada intervención
        $ubicacion_stmt = $pdo->prepare("
            SELECT u.descripcion 
            FROM equipos e
            JOIN ubicacion u ON e.id_ubicacion = u.id_ubicacion
            WHERE e.Tag_Number = ?
        ");
        $ubicacion_stmt->execute([$row['Tag_Number']]);
        $ubicacion = $ubicacion_stmt->fetch(PDO::FETCH_ASSOC);
    ?>
        <tr data-id="<?= $row['id_inter'] ?>">
            <td>
                <a href="Mantenimiento/Mantenimiento_correctivo/detalle.php?id=<?= $row['id_inter'] ?>" class="text-danger">
                    <?= htmlspecialchars($row['Tag_Number']) ?>
                </a>
            </td>
            <td><?= htmlspecialchars($ubicacion['descripcion'] ?? 'N/A') ?></td>
            <td class="text-truncate" style="max-width: 300px;" 
                data-bs-toggle="tooltip" data-bs-placement="top" 
                title="<?= htmlspecialchars($row['descripcion']) ?>">
                <?= htmlspecialchars($row['descripcion']) ?>
            </td>
            <td><?= htmlspecialchars($row['fecha']) ?></td>
            <td><?= htmlspecialchars($row['responsable']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>



<!-- Modal para Agregar Mantenimiento Correctivo -->
<div class="modal fade" id="agregarModal" tabindex="-1" aria-labelledby="agregarModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <!-- Encabezado del Modal -->
            <div class="modal-header bg-gradient-primary text-white">
                <h3 class="modal-title fw-bold" id="agregarModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>
                    Agregar Mantenimiento Correctivo
                </h3>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Cuerpo del Modal -->
            <div class="modal-body p-4">
                <form id="formAgregarIntervencion" class="needs-validation" novalidate action="Mantenimiento/Mantenimiento_correctivo/agregar_correctivo.php" method="POST">
                    <div class="row g-3">
                        <!-- Columna Izquierda -->
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <select class="form-select" id="ubicacion" name="ubicacion" required onchange="cargarEquiposPorUbicacion(this.value)">
                                    <option value="">Seleccione una ubicación</option>
                                    <?php foreach ($ubicaciones as $ubicacion): ?>
                                        <option value="<?= htmlspecialchars($ubicacion['id_ubicacion']) ?>">
                                            <?= htmlspecialchars($ubicacion['descripcion']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <?php if (empty($ubicaciones)): ?>
                                        <option value="" disabled>No hay ubicaciones disponibles en su planta</option>
                                    <?php endif; ?>
                                </select>
                                <label for="ubicacion">Ubicación</label>
                                <div class="invalid-feedback">Por favor seleccione una ubicación</div>
                            </div>
                            
                            <div class="form-floating mb-3">
                                <select class="form-select" id="tag_number" name="tag_number" required disabled onchange="cargarRepuestos(this.value)">
                                    <option value="">Seleccione una ubicación primero</option>
                                </select>
                                <label for="tag_number">Tag Number</label>
                                <div class="invalid-feedback">Por favor seleccione un Tag Number</div>
                            </div>
                            
                            <div class="form-floating mb-3">
                                <textarea class="form-control" id="descripcion" name="descripcion" required maxlength="90" style="height: 100px"></textarea>
                                <label for="descripcion">Descripción</label>
                                <small class="text-muted">Máximo 90 caracteres (<span id="descripcion-contador">90</span> restantes)</small>
                                <div class="invalid-feedback">La descripción es requerida y no puede exceder los 90 caracteres</div>
                            </div>
                        </div>
                        
                        <!-- Columna Derecha -->
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="date" class="form-control" id="fecha" name="fecha" required>
                                <label for="fecha">Fecha</label>
                                <div class="invalid-feedback">La fecha debe ser válida (entre 2000 y hoy)</div>
                            </div>
                            
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="materiales" name="materiales" maxlength="30">
                                <label for="materiales">Materiales</label>
                                <small class="text-muted">Máximo 30 caracteres (<span id="materiales-contador">30</span> restantes)</small>
                                <div class="invalid-feedback">Los materiales no pueden exceder los 30 caracteres</div>
                            </div>
                            
                            <div class="form-floating mb-3">
                                <select class="form-select" id="repuestos" name="repuestos" disabled>
                                    <option value="">Seleccione un Tag Number primero</option>
                                </select>
                                <label for="repuestos">Repuestos</label>
                                <small class="text-muted">Seleccione un repuesto asociado al equipo</small>
                                <div class="invalid-feedback">Por favor seleccione un repuesto</div>
                            </div>
                            
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="responsable" name="responsable" required maxlength="15" pattern="[A-Za-zÁÉÍÓÚáéíóúñÑ ]+">
                                <label for="responsable">Responsable</label>
                                <small class="text-muted">Máximo 15 letras (solo letras y espacios)</small>
                                <div class="invalid-feedback">Solo se permiten letras (máximo 15 caracteres)</div>
                            </div>
                            
                            <div class="form-floating mb-3">
                                <input type="time" class="form-control" id="tiempo" name="tiempo" required>
                                <label for="tiempo">Tiempo</label>
                                <div class="invalid-feedback">Por favor ingrese un tiempo válido (formato HH:MM)</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pie del Modal -->
                    <div class="modal-footer bg-light mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Guardar Mantenimiento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>


// Función para cargar equipos según la ubicación seleccionada
function cargarEquiposPorUbicacion(idUbicacion) {
    const tagNumberSelect = document.getElementById('tag_number');
    const repuestosSelect = document.getElementById('repuestos');
    
    if (!idUbicacion) {
        tagNumberSelect.innerHTML = '<option value="">Seleccione una ubicación primero</option>';
        tagNumberSelect.disabled = true;
        repuestosSelect.innerHTML = '<option value="">Seleccione un Tag Number primero</option>';
        repuestosSelect.disabled = true;
        return;
    }
    
    // Mostrar estado de carga
    tagNumberSelect.innerHTML = '<option value="">Cargando equipos...</option>';
    tagNumberSelect.disabled = false;
    
    // Realizar petición AJAX para obtener los equipos de la ubicación
    fetch(`Mantenimiento/Mantenimiento_correctivo/obtener_equipos.php?id_ubicacion=${encodeURIComponent(idUbicacion)}`)
        .then(response => response.json())
        .then(data => {
            if (data.length > 0) {
                let options = '<option value="">Seleccione un equipo</option>';
                data.forEach(equipo => {
                    options += `<option value="${equipo.Tag_Number}">${equipo.Tag_Number}</option>`;
                });
                tagNumberSelect.innerHTML = options;
            } else {
                tagNumberSelect.innerHTML = '<option value="">No hay equipos en esta ubicación</option>';
            }
        })
        .catch(error => {
            console.error('Error al cargar equipos:', error);
            tagNumberSelect.innerHTML = '<option value="">Error al cargar equipos</option>';
        });
    
    // Resetear repuestos
    repuestosSelect.innerHTML = '<option value="">Seleccione un Tag Number primero</option>';
    repuestosSelect.disabled = true;
}

// Función para generar PDF
function generarPDF() {
    const searchTerm = document.getElementById('searchTerm').value;
    const searchType = document.getElementById('searchType').value;
    const searchFechaInicio = document.getElementById('searchFechaInicio').value;
    const searchFechaFin = document.getElementById('searchFechaFin').value;
    
    let url = `Mantenimiento/Mantenimiento_correctivo/generar_pdf_correctivo.php?searchTerm=${searchTerm}&searchType=${searchType}`;
    
    if (searchFechaInicio) {
        url += `&searchFechaInicio=${searchFechaInicio}`;
    }
    
    if (searchFechaFin) {
        url += `&searchFechaFin=${searchFechaFin}`;
    }
    
    window.open(url, '_blank');
}

// Función para cargar repuestos según el Tag Number seleccionado
function cargarRepuestos(tagNumber) {
    const repuestosSelect = document.getElementById('repuestos');
    
    if (!tagNumber) {
        repuestosSelect.innerHTML = '<option value="">Seleccione un Tag Number primero</option>';
        repuestosSelect.disabled = true;
        return;
    }
    
    // Mostrar estado de carga
    repuestosSelect.innerHTML = '<option value="">Cargando repuestos...</option>';
    repuestosSelect.disabled = false;
    
    // Realizar petición AJAX para obtener los repuestos
    fetch(`Mantenimiento/Mantenimiento_correctivo/obtener_repuestos.php?tag_number=${encodeURIComponent(tagNumber)}`)
        .then(response => response.json())
        .then(data => {
            if (data.length > 0) {
                let options = '<option value="">Seleccione un repuesto</option>';
                data.forEach(repuesto => {
                    options += `<option value="${repuesto.id_repuestos}">${repuesto.real_part} - ${repuesto.descripcion}</option>`;
                });
                repuestosSelect.innerHTML = options;
            } else {
                repuestosSelect.innerHTML = '<option value="">No hay repuestos asociados a este equipo</option>';
            }
        })
        .catch(error => {
            console.error('Error al cargar repuestos:', error);
            repuestosSelect.innerHTML = '<option value="">Error al cargar repuestos</option>';
        });
}

// Configuración inicial cuando el DOM está cargado
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
    const formAgregarIntervencion = document.getElementById('formAgregarIntervencion');
    if (formAgregarIntervencion) {
        formAgregarIntervencion.addEventListener('submit', function(event) {
            event.preventDefault();
            
            // Validar campos adicionales
            const fechaInput = document.getElementById('fecha');
            const fecha = new Date(fechaInput.value);
            const hoy = new Date();
            hoy.setHours(0, 0, 0, 0);
            
            if (fecha > hoy || fecha.getFullYear() < 2000) {
                fechaInput.setCustomValidity('La fecha debe estar entre 2000 y hoy');
                fechaInput.classList.add('is-invalid');
            } else {
                fechaInput.setCustomValidity('');
                fechaInput.classList.remove('is-invalid');
            }
            
            const responsableInput = document.getElementById('responsable');
            const soloLetras = /^[A-Za-zÁÉÍÓÚáéíóúñÑ ]+$/;
            if (!soloLetras.test(responsableInput.value)) {
                responsableInput.setCustomValidity('Solo se permiten letras y espacios');
                responsableInput.classList.add('is-invalid');
            } else {
                responsableInput.setCustomValidity('');
                responsableInput.classList.remove('is-invalid');
            }
            
            const tiempoInput = document.getElementById('tiempo');
            const tiempoRegex = /^([01]?[0-9]|2[0-3]):[0-5][0-9]$/;
            if (!tiempoRegex.test(tiempoInput.value)) {
                tiempoInput.setCustomValidity('Formato de tiempo inválido (HH:MM)');
                tiempoInput.classList.add('is-invalid');
            } else {
                tiempoInput.setCustomValidity('');
                tiempoInput.classList.remove('is-invalid');
            }
            
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
                    showFloatingAlert(data.message || 'Registro agregado exitosamente!', 'success');
                    // Cerrar modal y recargar
                    setTimeout(() => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('agregarModal'));
                        modal.hide();
                        // Limpiar el formulario
                        this.reset();
                        this.classList.remove('was-validated');
                        // Recargar los datos de la tabla
                        location.reload();
                    }, 1000);
                } else {
                    showFloatingAlert(data.error || 'Error al procesar la solicitud', 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showFloatingAlert('Error en la comunicación con el servidor', 'danger');
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
    
    // Configurar contadores de caracteres
    function setupCharacterCounter(inputId, counterId, maxLength) {
        const input = document.getElementById(inputId);
        const counter = document.getElementById(counterId);
        
        input.addEventListener('input', function() {
            const remaining = maxLength - this.value.length;
            counter.textContent = remaining;
            
            if (remaining < 0) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    }
    
    setupCharacterCounter('descripcion', 'descripcion-contador', 90);
    setupCharacterCounter('materiales', 'materiales-contador', 30);
    
    // Establecer fecha actual por defecto
    const fechaInput = document.getElementById('fecha');
    const today = new Date().toISOString().split('T')[0];
    fechaInput.value = today;
    fechaInput.max = today;
    
    // Manejar selección de filas
    function manejarSeleccionFilas() {
        const tabla = document.getElementById('tabla-intervenciones');
        
        tabla.addEventListener('click', function(e) {
            if (e.target.closest('a.text-danger')) {
                return;
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
    
    // Manejar botón Eliminar
    function manejarBotonEliminar() {
        document.getElementById('btnEliminar').addEventListener('click', function() {
            if (!selectedId) {
                showFloatingAlert('Por favor seleccione un registro primero', 'warning');
                return;
            }
            
            if (confirm('¿Estás seguro de que deseas eliminar esta intervención?')) {
                fetch(`Mantenimiento/Mantenimiento_correctivo/borrar_correctivo.php?id=${selectedId}`, {
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
                        showFloatingAlert(data.message || 'Registro eliminado exitosamente!', 'success');
                        // Recargar los datos de la tabla después de 1 segundo
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        showFloatingAlert(data.error || 'Error al eliminar el registro', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showFloatingAlert('Error en la comunicación con el servidor', 'danger');
                });
            }
        });
    }
    
    // Manejar filtrado
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
        const table = document.getElementById('tabla-intervenciones');
        const rows = table.getElementsByTagName('tr');

        for (let i = 1; i < rows.length; i++) {
            const cells = rows[i].getElementsByTagName('td');
            if (cells.length > 0) {
                const tagNumber = cells[0].textContent || cells[0].innerText;
                const ubicacion = cells[1].textContent || cells[1].innerText;
                const fecha = cells[3].textContent || cells[3].innerText;

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
            document.getElementById('btnEliminar').disabled = true;
        }
    }
    
    searchTerm.addEventListener('input', aplicarFiltros);
    searchType.addEventListener('change', aplicarFiltros);
    searchFechaInicio.addEventListener('change', aplicarFiltros);
    searchFechaFin.addEventListener('change', aplicarFiltros);
}
    
    // Mostrar mensajes de éxito/error al cargar la página
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.has('agregado')) {
        showFloatingAlert('Mantenimiento correctivo agregado exitosamente', 'success');
        // Limpiar el parámetro de la URL
        history.replaceState({}, document.title, window.location.pathname);
    }
    
    if (urlParams.has('borrado')) {
        showFloatingAlert('Registro de mantenimiento correctivo borrado exitosamente', 'success');
        // Limpiar el parámetro de la URL
        history.replaceState({}, document.title, window.location.pathname);
    }
    
    // Inicializar todas las funciones
    manejarSeleccionFilas();
    manejarBotonEliminar();
    manejarFiltrado();
    
    // Configurar el botón de agregar para resetear el formulario al mostrarse
    document.getElementById('agregarModal').addEventListener('show.bs.modal', function() {
        const form = document.getElementById('formAgregarIntervencion');
        form.reset();
        form.classList.remove('was-validated');
        
        // Restablecer contadores
        document.getElementById('descripcion-contador').textContent = '90';
        document.getElementById('materiales-contador').textContent = '30';
    });
    
    // Activar tooltips de Bootstrap
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>
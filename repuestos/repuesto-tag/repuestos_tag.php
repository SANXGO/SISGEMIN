<?php
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/../../includes/check_permission.php';
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

// Consulta principal de relaciones repuestos-equipos con filtro por planta
$sql = "
    SELECT 
        er.id_puente,
        r.id_repuestos,
        r.descripcion,
        r.real_part,
        e.Tag_Number,
        e.Instrument_Type_Desc,
        u.descripcion as ubicacion_desc
    FROM 
        equipo_repuesto er
    JOIN 
        repuestos r ON er.id_repuesto = r.id_repuestos
    JOIN 
        equipos e ON er.id_equipo = e.Tag_Number
    JOIN
        ubicacion u ON e.id_ubicacion = u.id_ubicacion
    WHERE
        u.id_planta = ?
    ORDER BY 
        r.descripcion, e.Tag_Number
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id_planta_usuario]);
$relaciones = $stmt->fetchAll();

// Consulta de repuestos activos
$stmtRepuestos = $pdo->query("SELECT id_repuestos, descripcion, real_part FROM repuestos WHERE estado ='activo' ORDER BY real_part");
$repuestos = $stmtRepuestos->fetchAll();

// Consulta de ubicaciones activas para la planta del usuario
$sqlUbicaciones = "
    SELECT id_ubicacion, descripcion 
    FROM ubicacion 
    WHERE id_planta = ? AND estado = 'activo'
    ORDER BY descripcion
";
$stmtUbicaciones = $pdo->prepare($sqlUbicaciones);
$stmtUbicaciones->execute([$id_planta_usuario]);
$ubicaciones = $stmtUbicaciones->fetchAll();

logModuleAccess('Repuesto-tag');
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
        background-color: #f8d7da !important;
    }
    .search-container {
        display: flex;
        gap: 10px;
    }
    .search-box {
        width: 250px;
    }
    .btn-pdf {
        background-color: #dc3545;
        color: white;
    }
    .modal-body .form-group {
        margin-bottom: 1rem;
    }
    .equipo-tag {
        font-weight: bold;
    }
    .equipo-type {
        color: #6c757d;
        font-size: 0.9em;
    }
    .info-box {
        background-color: #f8f9fa;
        border-radius: 5px;
        padding: 10px;
        margin-bottom: 15px;
    }
    .info-label {
        font-weight: bold;
        color: #495057;
    }
    .info-value {
        color: #212529;
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


        .modal-subtitle {
    color: #b71513;
}


</style>

<div class="sticky-top bg-white p-3 shadow-sm">
    <h4 class="text-center mb-1">Repuestos-Equipos</h4>

    <!-- Barra de búsqueda y botones -->
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex gap-2">
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#agregarRelacionModal">Agregar Relación</button>
            <button id="btnEliminar" class="btn btn-danger" disabled onclick="confirmarBorradoRelacion()">Eliminar</button>
            <button id="btnPDF" class="btn btn-pdf" onclick="generarPDF()">Generar PDF</button>
        </div>
        <div class="search-container">
            <input type="text" id="searchDescripcion" class="form-control search-box" placeholder="Buscar por Descripción" onkeyup="filtrarRegistros('descripcion')">
            <input type="text" id="searchTag" class="form-control search-box" placeholder="Buscar por Tag de Equipo" onkeyup="filtrarRegistros('tag')">
        </div>
    </div>
</div>


<!-- Contenedor para alertas flotantes -->
<div id="alertContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 1100; margin-top: 70px;"></div>


<!-- Tabla de registros -->
<table class="table table-striped">
    <thead class="table-dark">
        <tr>
            <th>#</th> <!-- Cambiado de "ID" a "#" -->
            <th>Descripción Repuesto</th>
            <th>Parte Real</th>
            <th>Tag de Equipo</th>
            <th>Tipo de Instrumento</th>
        </tr>
    </thead>
    <tbody id="tabla-relaciones">
        <?php foreach ($relaciones as $index => $relacion): ?>
            <tr onclick="seleccionarRelacion('<?= $relacion['id_puente'] ?>')" style="cursor: pointer;">
                <td><?= $index + 1 ?></td> <!-- Numeración secuencial (1, 2, 3...) -->
                <td><?= htmlspecialchars($relacion['descripcion']) ?></td>
                <td><?= htmlspecialchars($relacion['real_part']) ?></td>
                <td><?= htmlspecialchars($relacion['Tag_Number']) ?></td>
                <td><?= htmlspecialchars($relacion['Instrument_Type_Desc']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<!-- Modal para Agregar Relación Repuesto-Equipo -->
<div class="modal fade" id="agregarRelacionModal" tabindex="-1" aria-labelledby="agregarRelacionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <!-- Encabezado del Modal -->
            <div class="modal-header bg-gradient-primary text-white">
                <h3 class="modal-title fw-bold" id="agregarRelacionModalLabel">
                    <i class="bi bi-link-45deg me-2"></i>
                    Relación Repuesto-Equipo
                </h3>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Cuerpo del Modal -->
            <div class="modal-body p-4">
                <form id="formAgregarRelacion" action="repuestos/repuesto-tag/agregar_relacion.php" method="POST">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <h5 class="fw-bold mb-3" style="color: #b71513;">
                                <i class="bi bi-box-seam me-2"></i>Información del Repuesto
                            </h5>
                            <div class="form-floating mb-3">
                                <select class="form-select" id="selectRepuesto" name="id_repuesto" required>
                                    <option value="">Seleccione un repuesto</option>
                                    <?php foreach ($repuestos as $repuesto): ?>
                                        <option 
                                            value="<?= htmlspecialchars($repuesto['id_repuestos']) ?>" 
                                            data-descripcion="<?= htmlspecialchars($repuesto['descripcion']) ?>">
                                            <?= htmlspecialchars($repuesto['real_part']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="selectRepuesto">Seleccionar Repuesto (Parte Real)</label>
                            </div>
                            <div class="card bg-light p-3">
                                <div class="mb-2">
                                    <span class="fw-bold">Descripción:</span>
                                    <span class="ms-2" id="infoDescripcion">-</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h5 class="fw-bold mb-3" style="color: #b71513;">
                                <i class="bi bi-tools me-2"></i>Información del Equipo
                            </h5>
                            
                            <!-- Selector de Ubicación -->
                            <div class="form-floating mb-3">
                                <select class="form-select" id="selectUbicacion" required>
                                    <option value="">Seleccione una ubicación</option>
                                    <?php foreach ($ubicaciones as $ubicacion): ?>
                                        <option value="<?= htmlspecialchars($ubicacion['id_ubicacion']) ?>">
                                            <?= htmlspecialchars($ubicacion['descripcion']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <label for="selectUbicacion">Seleccionar Ubicación</label>
                            </div>
                            
                            <!-- Selector de Equipo (se llenará dinámicamente) -->
                            <div class="form-floating mb-3">
                                <select class="form-select" id="selectEquipo" name="id_equipo" required disabled>
                                    <option value="">Primero seleccione una ubicación</option>
                                </select>
                                <label for="selectEquipo">Seleccionar Equipo</label>
                            </div>
                            
                            <div class="card bg-light p-3">
                                <div class="mb-2">
                                    <span class="fw-bold">Tipo de Instrumento:</span>
                                    <span class="ms-2" id="infoInstrumentType">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pie del Modal -->
                    <div class="modal-footer bg-light mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Guardar Relación
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/jspdf.umd.min.js"></script>
<script src="assets/js/jspdf.plugin.autotable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    vincularEventos();
    verificarParametrosURL();
    configurarEventos();
});


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

// Verificar parámetros de URL para mostrar alertas
function verificarParametrosURL() {
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.has('success')) {
        showFloatingAlert('Operación realizada con éxito!', 'success');
    } else if (urlParams.has('error')) {
        const errorCode = urlParams.get('error');
        let errorMessage = 'Ocurrió un error';
        
        switch(errorCode) {
            case '1':
                errorMessage = 'Error en la base de datos';
                break;
            case '2':
                errorMessage = 'La relación ya existe';
                break;
            case '3':
                errorMessage = 'Datos incompletos';
                break;
        }
        
        showFloatingAlert(errorMessage, 'danger');
    }
}

// Configurar eventos de los formularios
function configurarEventos() {
    // Configurar validación del formulario de agregar
    const formAgregarRelacion = document.getElementById('formAgregarRelacion');
    if (formAgregarRelacion) {
        formAgregarRelacion.addEventListener('submit', function(event) {
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
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showFloatingAlert('Relación agregada exitosamente!', 'success');
                    setTimeout(() => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('agregarRelacionModal'));
                        modal.hide();
                        setTimeout(() => {
                            window.location.href = window.location.pathname + '?tabla=repuestos_tag&success=1';
                        }, 500);
                    }, 1000);
                } else {
                    showFloatingAlert('Error: ' + (data.error || 'No se pudo agregar la relación'), 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showFloatingAlert('Error en el servidor. Por favor verifica la consola para más detalles.', 'danger');
            });
        });
    }
    
    // Configurar formulario de edición
    const formEditarRelacion = document.getElementById('formEditarRelacion');
    if (formEditarRelacion) {
        formEditarRelacion.addEventListener('submit', function(event) {
            event.preventDefault();
            
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
                    showFloatingAlert('Relación actualizada exitosamente!', 'success');
                    setTimeout(() => {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('editarRelacionModal'));
                        modal.hide();
                        setTimeout(() => {
                            window.location.href = window.location.pathname + '?tabla=repuestos_tag&success=1';
                        }, 500);
                    }, 1000);
                } else {
                    showFloatingAlert('Error: ' + (data.error || 'No se pudo actualizar la relación'), 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showFloatingAlert('Error en el servidor. Por favor verifica la consola para más detalles.', 'danger');
            });
        });
    }
}


function generarPDF() {
    // Obtener valores de búsqueda
    const searchDescripcion = document.getElementById('searchDescripcion').value;
    const searchTag = document.getElementById('searchTag').value;
    
    // Construir URL con parámetros
    let url = 'repuestos/repuesto-tag/generar_pdf_relacion.php?';
    
    if (searchDescripcion) {
        url += `searchDescripcion=${encodeURIComponent(searchDescripcion)}`;
    } else if (searchTag) {
        url += `searchTag=${encodeURIComponent(searchTag)}`;
    }
    
    // Abrir en nueva pestaña para descargar el PDF
    window.open(url, '_blank');
}



document.addEventListener('DOMContentLoaded', function() {
    function cargarEquiposPorUbicacion(idUbicacion) {
    const selectEquipo = document.getElementById('selectEquipo');
    
    if (!idUbicacion) {
        selectEquipo.innerHTML = '<option value="">Primero seleccione una ubicación</option>';
        selectEquipo.disabled = true;
        document.getElementById('infoInstrumentType').textContent = '-';
        return;
    }
    
    // Mostrar carga
    selectEquipo.innerHTML = '<option value="">Cargando equipos...</option>';
    selectEquipo.disabled = true;
    
    fetch(`repuestos/repuesto-tag/obtener_equipos.php?id_ubicacion=${idUbicacion}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Error al cargar equipos');
            }
            return response.json();
        })
        .then(data => {
            if (data.length > 0) {
                selectEquipo.innerHTML = '<option value="">Seleccione un equipo</option>';
                data.forEach(equipo => {
                    const option = document.createElement('option');
                    option.value = equipo.Tag_Number;
                    option.textContent = equipo.Tag_Number;
                    option.dataset.instrumentType = equipo.Instrument_Type_Desc;
                    selectEquipo.appendChild(option);
                });
                selectEquipo.disabled = false;
            } else {
                selectEquipo.innerHTML = '<option value="">No hay equipos en esta ubicación</option>';
                selectEquipo.disabled = true;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            selectEquipo.innerHTML = '<option value="">Error al cargar equipos</option>';
            selectEquipo.disabled = true;
        });
}


// Evento para el cambio de ubicación
document.getElementById('selectUbicacion').addEventListener('change', function() {
    const idUbicacion = this.value;
    cargarEquiposPorUbicacion(idUbicacion);
});

// Evento para el cambio de equipo
document.getElementById('selectEquipo').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const instrumentType = selectedOption.dataset.instrumentType || '-';
    document.getElementById('infoInstrumentType').textContent = instrumentType;
});

// Evento para el cambio de repuesto (se mantiene igual)
document.getElementById('selectRepuesto').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const descripcion = selectedOption.dataset.descripcion || '-';
    document.getElementById('infoDescripcion').textContent = descripcion;
});

});

let relacionSeleccionada = null;

function seleccionarRelacion(id_puente) {
    document.querySelectorAll('#tabla-relaciones tr').forEach(tr => {
        tr.classList.remove('selected-row');
    });

    const filaSeleccionada = document.querySelector(`#tabla-relaciones tr[onclick*="${id_puente}"]`);
    if (filaSeleccionada) {
        filaSeleccionada.classList.add('selected-row');
    }

    relacionSeleccionada = id_puente;

    // Habilitar botones
    document.getElementById('btnEliminar').disabled = false;
}

function confirmarBorradoRelacion() {
    if (relacionSeleccionada && confirm('¿Estás seguro de que deseas eliminar esta relación?')) {
        fetch(`repuestos/repuesto-tag/borrar_relacion.php?id_puente=${relacionSeleccionada}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error al eliminar');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showFloatingAlert('Relación eliminada exitosamente!', 'success');
                setTimeout(() => {
                    window.location.href = window.location.pathname + '?tabla=repuestos_tag&success=1';
                }, 1000);
            } else {
                showFloatingAlert('Error al eliminar la relación', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showFloatingAlert('Error al eliminar la relación', 'danger');
        });
    }
}

function filtrarRegistros(tipo) {
    let searchValue;
    if (tipo === 'descripcion') {
        searchValue = document.getElementById('searchDescripcion').value.trim();
        document.getElementById('searchTag').value = '';
    } else {
        searchValue = document.getElementById('searchTag').value.trim();
        document.getElementById('searchDescripcion').value = '';
    }
    
    if (searchValue === "") {
        window.location.reload();
        return;
    }

    fetch(`repuestos/repuesto-tag/buscar_relaciones.php?search=${encodeURIComponent(searchValue)}&tipo=${tipo}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('tabla-relaciones').innerHTML = data;
            relacionSeleccionada = null;
            document.getElementById('btnEliminar').disabled = true;
        })
        .catch(error => console.error('Error al buscar relaciones:', error));
}

// Evento para cargar datos en el modal de edición
document.getElementById('editarRelacionModal').addEventListener('show.bs.modal', function() {
    if (!relacionSeleccionada) return;
    
    fetch(`repuestos/repuesto-tag/obtener_relacion.php?id_puente=${relacionSeleccionada}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('edit_id_puente').value = data.id_puente;
            document.getElementById('edit_descripcion').value = data.descripcion;
            document.getElementById('edit_real_part').value = data.real_part;
            document.getElementById('edit_tag_number').value = data.Tag_Number;
            document.getElementById('edit_instrument_type').value = data.Instrument_Type_Desc;
        });
});


// Resetear modales al cerrar
document.getElementById('agregarRelacionModal').addEventListener('hidden.bs.modal', function() {
    this.querySelector('form').reset();
    document.getElementById('infoDescripcion').textContent = '-';
    document.getElementById('infoInstrumentType').textContent = '-';
});

document.getElementById('editarRelacionModal').addEventListener('hidden.bs.modal', function() {
    this.querySelector('form').reset();
});




</script>


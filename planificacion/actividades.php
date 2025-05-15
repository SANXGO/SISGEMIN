<?php
require_once __DIR__ . '/../includes/audit.php';
require_once __DIR__ . '/../includes/conexion.php';

// Obtener información del usuario actual con su planta
$id_usuario = $_SESSION['id_usuario'] ?? null;
if (!$id_usuario) {
    die('Usuario no autenticado');
}

$stmt = $pdo->prepare("
    SELECT u.nombre, u.id_planta, p.nombres as nombre_planta 
    FROM usuario u
    JOIN planta p ON u.id_planta = p.id_planta
    WHERE u.id_usuario = ?
");
$stmt->execute([$id_usuario]);
$usuario_actual = $stmt->fetch();

if (!$usuario_actual) {
    die('Usuario no encontrado');
}

// Obtener ubicaciones de la planta del usuario
$stmtUbicaciones = $pdo->prepare("
    SELECT * FROM ubicacion 
    WHERE id_planta = ?
    ORDER BY descripcion
");
$stmtUbicaciones->execute([$usuario_actual['id_planta']]);
$ubicaciones = $stmtUbicaciones->fetchAll();

// Obtener equipos de la planta del usuario
$stmtEquipos = $pdo->prepare("
    SELECT e.* 
    FROM equipos e
    JOIN ubicacion u ON e.id_ubicacion = u.id_ubicacion
    WHERE u.id_planta = ? 
    ORDER BY e.Tag_Number
");
$stmtEquipos->execute([$usuario_actual['id_planta']]);
$equipos = $stmtEquipos->fetchAll();

// Consulta para obtener actividades (modificada para filtrar por planta)
$stmt = $pdo->prepare("
    SELECT a.*, u.nombre as nombre_usuario 
    FROM actividades a
    JOIN usuario u ON a.id_usuario = u.id_usuario
    WHERE a.planta = ?
    ORDER BY a.id_actividad DESC
");
$stmt->execute([$usuario_actual['nombre_planta']]);
$actividades = $stmt->fetchAll();


logModuleAccess('actividades');

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

    .is-invalid~.invalid-feedback {
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
        background: linear-gradient(#b71513 100%);
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

    .search-container {
        display: flex;
        gap: 10px;
    }

    .search-container input {
        width: 200px;
    }

    .text-truncate-cell {
        max-width: 300px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>

<div class="sticky-top bg-white p-3 shadow-sm">
    <h4 class="text-center mb-4">Registro de Actividades</h4>

    <!-- Barra de búsqueda y botones -->
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex gap-2">
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#agregarActividadModal">Agregar
                Actividad</button>
            <button id="btnEliminar" class="btn btn-danger" disabled
                onclick="confirmarBorradoActividad()">Eliminar</button>
            <button id="btnPDF" class="btn btn-danger" onclick="generarPDF()">Generar PDF</button>
        </div>
        <div class="search-container">
            <input type="text" id="searchOrden" class="form-control" placeholder="Buscar por Orden"
                onkeyup="filtrarActividades()">
            <input type="date" id="searchFecha" class="form-control" onchange="filtrarActividades()">
            <input type="text" id="searchGeneral" class="form-control" placeholder="Busqueda General"
                onkeyup="filtrarActividades()">
        </div>
    </div>
</div>

<!-- Tabla de registros -->
<table class="table table-striped table-fixed">
    <thead class="table-dark">
        <tr>
            <th>#</th>
            <th>Orden</th>
            <th>Planta</th>
            <th>Tag</th>
            <th>Actividad</th>
            <th>Fecha</th>
        </tr>
    </thead>
    <tbody id="tabla-actividades">
        <?php foreach ($actividades as $index => $actividad): ?>
            <tr onclick="seleccionarFila(this, '<?= $actividad['id_actividad'] ?>')"
                data-id="<?= $actividad['id_actividad'] ?>" style="cursor: pointer;">
                <td>
                    <a href="planificacion/detalles_actividades.php?id=<?= $actividad['id_actividad'] ?>"
                        class="text-danger">
                        <?= $index + 1 ?>  <!-- index + 1 para que empiece en 1 -->
                    </a>
                </td>
                <td><?= htmlspecialchars($actividad['orden']) ?></td>
                <td><?= htmlspecialchars($actividad['planta']) ?></td>
                <td><?= htmlspecialchars($actividad['tag_number']) ?></td>
                <td class="text-truncate-cell" data-bs-toggle="tooltip" data-bs-placement="top"
                    title="<?= htmlspecialchars($actividad['actividad']) ?>">
                    <?= htmlspecialchars($actividad['actividad']) ?>
                </td>
                <td><?= htmlspecialchars($actividad['fecha']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Modales -->
<!-- Modal para Agregar Actividad -->
<div class="modal fade" id="agregarActividadModal" tabindex="-1" aria-labelledby="agregarActividadModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <!-- Encabezado del Modal -->
            <div class="modal-header bg-gradient-primary text-white">
                <h3 class="modal-title fw-bold" id="agregarActividadModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>
                    Agregar Actividad
                </h3>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>

            <!-- Cuerpo del Modal -->
            <div class="modal-body p-4">
                <form id="formAgregarActividad" action="planificacion/agregar_actividad.php" method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control fw-bold"
                                    value="<?= htmlspecialchars($usuario_actual['nombre']) ?>" readonly>
                                <label>Usuario</label>
                                <input type="hidden" name="id_usuario" value="<?= $id_usuario ?>">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="orden" id="orden" required
                                    pattern="^\d{1,12}$" maxlength="12">
                                <label>Orden</label>
                                <div class="invalid-feedback">Debe contener solo números (máx. 12 dígitos)</div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="num_permiso" id="num_permiso" required
                                    pattern="^\d{1,10}$" maxlength="10">
                                <label>Número de Permiso</label>
                                <div class="invalid-feedback">Debe contener solo números (máx. 10 dígitos)</div>
                            </div>
                        </div>

                        <!-- Planta (texto estático) -->
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control fw-bold"
                                    value="<?= htmlspecialchars($usuario_actual['nombre_planta']) ?>" readonly>
                                <label>Planta</label>
                                <input type="hidden" name="planta"
                                    value="<?= htmlspecialchars($usuario_actual['nombre_planta']) ?>">
                            </div>
                        </div>

                        <!-- Ubicación (selector estático) -->
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <select class="form-select" name="ubicacion" required>
                                    <option value="">Seleccione una ubicación</option>
                                    <?php foreach ($ubicaciones as $ubicacion): ?>
                                        <option value="<?= $ubicacion['id_ubicacion'] ?>">
                                            <?= htmlspecialchars($ubicacion['descripcion']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <label>Ubicación</label>
                                <div class="invalid-feedback">Por favor seleccione una ubicación</div>
                            </div>
                        </div>

                        <!-- Tag Number (selector estático) -->
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <select class="form-select" name="tag_number" required>
                                    <option value="">Seleccione un Tag Number</option>
                                    <?php foreach ($equipos as $equipo): ?>
                                        <option value="<?= $equipo['Tag_Number'] ?>">
                                            <?= htmlspecialchars($equipo['Tag_Number']) ?> -
                                            <?= htmlspecialchars($equipo['Instrument_Type_Desc']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <label>Tag Number</label>
                                <div class="invalid-feedback">Por favor seleccione un Tag Number</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-floating mb-3">
                                <textarea class="form-control" name="actividad" id="actividad" style="height: 100px"
                                    required maxlength="115"></textarea>
                                <label>Actividad</label>
                                <div class="invalid-feedback">Máximo 115 caracteres permitidos</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="especialistas" id="especialistas" required
                                    pattern="^[a-zA-Z0-9\s]{1,15}$" maxlength="15">
                                <label>Especialistas</label>
                                <div class="invalid-feedback">Solo letras y números (máx. 15 caracteres)</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="tiempo" id="tiempo" required
                                    pattern="^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$">
                                <label>Tiempo (HH:MM)</label>
                                <div class="invalid-feedback">Formato inválido (use HH:MM)</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="recurso_apoyo" id="recurso_apoyo" required
                                    pattern="^[a-zA-Z0-9\s]{1,20}$" maxlength="20">
                                <label>Recurso de Apoyo</label>
                                <div class="invalid-feedback">Solo letras y números (máx. 20 caracteres)</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="date" class="form-control" name="fecha" id="fecha" required
                                    min="2000-01-01" max="<?= date('Y-m-d') ?>">
                                <label>Fecha</label>
                                <div class="invalid-feedback">Fecha debe estar entre 2000 y hoy</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-floating mb-3">
                                <input type="number" class="form-control" name="avance" id="avance" required min="1"
                                    max="100">
                                <label>Avance (%)</label>
                                <div class="invalid-feedback">Debe ser entre 1 y 100</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-floating mb-3">
                                <textarea class="form-control" name="observacion" id="observacion" style="height: 100px"
                                    maxlength="60"></textarea>
                                <label>Observación</label>
                                <div class="invalid-feedback">Máximo 60 caracteres permitidos</div>
                            </div>
                        </div>
                    </div>

                    <!-- Pie del Modal -->
                    <div class="modal-footer bg-light mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Guardar Actividad
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
<script src="assets/js/js/all.min.js"></script>

<script>
    // Variables globales
    let actividadSeleccionada = null;

    // Función para seleccionar una fila
    function seleccionarFila(elementoFila, idActividad) {
        // Remover la clase 'selected-row' de todas las filas
        document.querySelectorAll('#tabla-actividades tr').forEach(row => {
            row.classList.remove('selected-row');
        });

        // Agregar la clase a la fila seleccionada
        elementoFila.classList.add('selected-row');

        // Actualizar la variable global
        actividadSeleccionada = idActividad;

        // Habilitar botón de eliminar
        document.getElementById('btnEliminar').disabled = false;
    }

    // Función para confirmar el borrado
    function confirmarBorradoActividad() {
        if (actividadSeleccionada && confirm('¿Estás seguro de que deseas borrar esta actividad?')) {
            window.location.href = `planificacion/borrar_actividad.php?id_actividad=${actividadSeleccionada}`;
        }
    }

    // Función para filtrar actividades (búsqueda)
    function filtrarActividades() {
        const searchOrden = document.getElementById('searchOrden').value.trim();
        const searchFecha = document.getElementById('searchFecha').value;
        const searchGeneral = document.getElementById('searchGeneral').value.trim();

        let url = `planificacion/buscar_actividades.php?`;

        if (searchOrden) url += `orden=${encodeURIComponent(searchOrden)}&`;
        if (searchFecha) url += `fecha=${encodeURIComponent(searchFecha)}&`;
        if (searchGeneral) url += `general=${encodeURIComponent(searchGeneral)}&`;

        fetch(url)
            .then(response => response.text())
            .then(data => {
                document.getElementById('tabla-actividades').innerHTML = data;
                actividadSeleccionada = null; // Reiniciar la selección
                document.getElementById('btnEliminar').disabled = true;

                // Vincular eventos a las nuevas filas
                vincularEventosActividades();
            })
            .catch(error => console.error('Error al buscar actividades:', error));
    }

    // Función para vincular eventos a las filas de la tabla
    function vincularEventosActividades() {
        document.querySelectorAll('#tabla-actividades tr').forEach(row => {
            row.addEventListener('click', function () {
                const idActividad = this.getAttribute('data-id');
                seleccionarFila(this, idActividad);
            });
        });

        // Activar tooltips de Bootstrap
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    }

    // Función para generar PDF
    function generarPDF() {
        const searchOrden = document.getElementById('searchOrden').value.trim();
        const searchFecha = document.getElementById('searchFecha').value;
        const searchGeneral = document.getElementById('searchGeneral').value.trim();

        let url = `planificacion/generar_pdf_actividades.php?`;

        if (searchOrden) url += `orden=${encodeURIComponent(searchOrden)}&`;
        if (searchFecha) url += `fecha=${encodeURIComponent(searchFecha)}&`;
        if (searchGeneral) url += `general=${encodeURIComponent(searchGeneral)}&`;

        window.open(url, '_blank');
    }

    // Función para validar si la orden ya existe (usando AJAX)
    function validarOrdenUnica(orden) {
        if (!orden) return true; // Si está vacío, no validar todavía

        // Hacer la petición AJAX para verificar si la orden existe
        return fetch(`planificacion/verificar_orden.php?orden=${encodeURIComponent(orden)}`)
            .then(response => response.json())
            .then(data => {
                return !data.existe; // Devuelve true si la orden NO existe
            })
            .catch(error => {
                console.error('Error al verificar orden:', error);
                return false; // En caso de error, asumimos que existe para prevenir duplicados
            });
    }

    // Inicialización cuando el DOM está listo
    document.addEventListener('DOMContentLoaded', function () {
        vincularEventosActividades();

        // Resetear modal de agregar al cerrar
        document.getElementById('agregarActividadModal').addEventListener('hidden.bs.modal', function () {
            this.querySelector('form').reset();
        });

        // Validación del formulario de agregar actividad
        document.getElementById('formAgregarActividad').addEventListener('submit', async function (e) {
            e.preventDefault();

            const ordenInput = document.getElementById('orden');
            const orden = ordenInput.value.trim();

            // Validar que la orden no exista ya en la base de datos
            const ordenUnica = await validarOrdenUnica(orden);

            if (!ordenUnica) {
                ordenInput.classList.add('is-invalid');
                ordenInput.nextElementSibling.textContent = 'Esta orden ya existe en la base de datos';
                return;
            }

            // Validar todos los campos requeridos
            if (!this.checkValidity()) {
                e.stopPropagation();
                this.classList.add('was-validated');
                return;
            }

            // Si todo está bien, enviar el formulario
            this.submit();
        });

        // Validación en tiempo real para el campo de tiempo (HH:MM)
        document.getElementById('tiempo').addEventListener('input', function (e) {
            const tiempoRegex = /^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/;
            if (!tiempoRegex.test(this.value)) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });

        // Validación en tiempo real para el campo de avance (1-100)
        document.getElementById('avance').addEventListener('input', function (e) {
            const avance = parseInt(this.value);
            if (isNaN(avance) || avance < 1 || avance > 100) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });

        // Validación en tiempo real para el campo de número de permiso (solo números)
        document.getElementById('num_permiso').addEventListener('input', function (e) {
            const permisoRegex = /^\d+$/;
            if (!permisoRegex.test(this.value)) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });

        // Validación en tiempo real para el campo de especialistas (letras y números)
        document.getElementById('especialistas').addEventListener('input', function (e) {
            const especialistasRegex = /^[a-zA-Z0-9\s]+$/;
            if (!especialistasRegex.test(this.value)) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });

        // Validación en tiempo real para el campo de recurso de apoyo (letras y números)
        document.getElementById('recurso_apoyo').addEventListener('input', function (e) {
            const recursoRegex = /^[a-zA-Z0-9\s]+$/;
            if (!recursoRegex.test(this.value)) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    });



    const ubicacionSelect = document.querySelector('select[name="ubicacion"]');
    const tagNumberSelect = document.querySelector('select[name="tag_number"]');
    
    // Cargar Tags cuando cambia la ubicación
    ubicacionSelect.addEventListener('change', function() {
        const idUbicacion = this.value;
        
        // Limpiar el selector de Tags
        tagNumberSelect.innerHTML = '<option value="">Seleccione un Tag Number</option>';
        
        if (!idUbicacion) return;
        
        // Hacer petición para obtener los tags de esta ubicación
        fetch(`planificacion/obtener_tag_numbers.php?id_ubicacion=${idUbicacion}`)
            .then(response => response.json())
            .then(tags => {
                tags.forEach(tag => {
                    const option = document.createElement('option');
                    option.value = tag.Tag_Number;
                    option.textContent = `${tag.Tag_Number} - ${tag.Instrument_Type_Desc}`;
                    tagNumberSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error al cargar tags:', error);
                alert('Error al cargar los equipos. Intente nuevamente.');
            });
    });

</script>
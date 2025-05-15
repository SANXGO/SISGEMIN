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

// Consulta para obtener los equipos de las ubicaciones de la planta del usuario
try {
    $stmt = $pdo->prepare("
        SELECT e.* 
        FROM equipos e
        JOIN ubicacion u ON e.id_ubicacion = u.id_ubicacion
        JOIN planta p ON u.id_planta = p.id_planta
        WHERE p.estado = 'activo' AND u.estado = 'activo' AND e.estado = 'activo' AND u.id_planta = ?
    ");
    $stmt->execute([$id_planta_usuario]);
    $equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error en la consulta: " . $e->getMessage());
}

// Obtener las ubicaciones de la planta del usuario
try {
    $stmtUbicaciones = $pdo->prepare("
        SELECT u.*, p.NOMBRES as nombre_planta 
        FROM ubicacion u 
        JOIN planta p ON u.ID_PLANTA = p.ID_PLANTA
        WHERE u.id_planta = ?
    ");
    $stmtUbicaciones->execute([$id_planta_usuario]);
    $ubicaciones = $stmtUbicaciones->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener ubicaciones: " . $e->getMessage());
}
logModuleAccess('equipos');

?>

<link href="assets/css/bootstrap.min.css" rel="stylesheet">

<!-- Contenedor para alertas flotantes -->
<div id="alertContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 1100"></div>

<!-- Contenedor para la barra de búsqueda y botones -->
<div class="sticky-top bg-white p-3 shadow-sm">
    <h4 class="text-center mb-3"> Gestion de Instrumentos</h4>
    <div class="d-flex justify-content-between align-items-center">
        <!-- Botones de acciones -->
        <div class="d-flex gap-2">
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#agregarModal">Agregar instrumento </button>
        </div>

        <!-- Campo de búsqueda -->
        <div class="search-container">
            <input type="text" id="search" class="form-control" placeholder="Buscar por Tag Number...">
            <input type="text" id="searchUbicacion" class="form-control" placeholder="Buscar por Ubicación...">
        </div>
    </div>
</div>

<!-- Tabla de registros -->
<table class="table table-striped">
    <thead class="table-dark">
        <tr>
            <th>Tag Number</th>
            <th>Ubicación</th>
            <th>Tipo de instrumento</th>
        </tr>
    </thead>
    <tbody id="tabla-equipos">
        <?php if (is_array($equipos) && !empty($equipos)): ?>
            <?php foreach ($equipos as $equipo): ?>
                <tr onclick="seleccionarEquipo('<?= $equipo['Tag_Number'] ?>')" style="cursor: pointer;">
                    <td>
                        <a href="equipo/detalle_equipo.php?tag_number=<?= htmlspecialchars($equipo['Tag_Number']) ?>" class="text-danger">
                        <?= htmlspecialchars($equipo['Tag_Number']) ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($equipo['id_ubicacion']) ?></td>
                    <td><?= htmlspecialchars($equipo['Instrument_Type_Desc']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
           
        <?php endif; ?>
    </tbody>
</table>
<style>
        :root {
            --primary-color: #b71513;
            --secondary-color: #b71513;
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
        
        .btn-custom:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
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
        
        .pdf-item {
            margin-bottom: 15px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background-color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: transform 0.2s;
        }
        
        .pdf-item:hover {
            transform: translateX(5px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .badge-custom {
            background-color: var(--secondary-color);
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
            background: linear-gradient( #b71513 100%);  
        }
        
        .nav-tabs .nav-link {
            border: none;
            color: #b71513
            font-weight: 500;
            padding: 0.75rem 1.25rem;
            transition: all 0.3s;
        }
        
        .nav-tabs .nav-link:hover {
            color: #b71513
            border-bottom: 3px solid #dee2e6;
        }
        
        .nav-tabs .nav-link.active {
           
            border-bottom: 3px solid #b71513;
            background-color: transparent;
        }
        
        .form-floating label {
            color: #6c757d;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #b71513;
            box-shadow: #b71513
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
        #planta-info {
        transition: all 0.3s ease;
        padding: 5px 10px;
        background-color: #f8f9fa;
        border-radius: 4px;
}



.is-invalid {
    border-color: #dc3545;
}

.invalid-feedback {
    display: block;
    color: #dc3545;
    font-size: 0.875em;
    margin-top: 0.25rem;
}

.btn.disable, .btn:disabled {
            background-color: #b71513;

        }


/* Agregar al final de la sección de estilos */

/* Estilos para la previsualización de imagen */
.img-thumbnail {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 5px;
    background-color: #fff;
}

#preview-container {
    display: flex;
    align-items: center;
}

#remove-image {
    height: fit-content;
}



    </style>
<!-- Estilos CSS (se mantienen igual) -->

<!-- Modal para Agregar Equipo (se mantiene igual) -->
<div class="modal fade" id="agregarModal" tabindex="-1" aria-labelledby="agregarModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <!-- Encabezado del Modal -->
            <div class="modal-header bg-gradient-primary text-white">
                <h3 class="modal-title fw-bold" id="agregarModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>
                    Agregar Nuevo Instrumento
                </h3>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Cuerpo del Modal -->
            <div class="modal-body p-4">
                <form action="equipo/agregar.php" method="POST" id="formAgregarEquipo">
                    <!-- Sección de pestañas para mejor organización -->
                    <ul class="nav nav-tabs mb-4" id="equipoTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info-tab-pane" type="button" role="tab">
                                <i class="bi bi-info-circle me-2"></i>Información Básica
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tecnicos-tab" data-bs-toggle="tab" data-bs-target="#tecnicos-tab-pane" type="button" role="tab">
                                <i class="bi bi-tools me-2"></i>Datos Técnicos
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="sistema-tab" data-bs-toggle="tab" data-bs-target="#sistema-tab-pane" type="button" role="tab">
                                <i class="bi bi-gear me-2"></i>Configuración
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="otros-tab" data-bs-toggle="tab" data-bs-target="#otros-tab-pane" type="button" role="tab">
                                <i class="bi bi-file-earmark-text me-2"></i>Otros Datos
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content" id="equipoTabsContent">
                        <!-- Pestaña 1: Información Básica -->
                        <div class="tab-pane fade show active" id="info-tab-pane" role="tabpanel" tabindex="0">
                            <div class="row g-3">
                                <!-- Tag Number -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="add_tag_number" name="tag_number" required 
                                               oninput="validarTagNumber(this.value)" maxlength="30">
                                        <label for="add_tag_number">Tag Number</label>
                                    </div>
                                </div>                       
                                <!-- Ubicación -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <select class="form-select" id="add_unit" name="id_ubicacion" required onchange="mostrarPlanta(this)">
                                            <option value="">Seleccionar...</option>
                                            <?php foreach ($ubicaciones as $ubicacion): ?>
                                                <option value="<?= htmlspecialchars($ubicacion['id_ubicacion']) ?>" data-planta="<?= htmlspecialchars($ubicacion['nombre_planta']) ?>">
                                                    <?= htmlspecialchars($ubicacion['id_ubicacion']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <label for="add_unit">Ubicación</label>
                                    </div>
                                    <div id="planta-info" class="mt-2 text-muted small" style="display: none;">
                                        Pertenece a la planta: <span id="nombre-planta" class="fw-bold"></span>
                                    </div>
                                </div>
                              
                                <!-- Instrument Type -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="add_instrument_type" name="instrument_type" required maxlength="50">
                                        <label for="add_instrument_type">Instrument Type</label>
                                    </div>
                                </div>
                                <!-- Cantidad -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="number" class="form-control" id="add_cantidad" name="cantidad">
                                        <label for="add_cantidad">Cantidad</label>
                                    </div>
                                </div>

                                <div class="col-md-12">
    <div class="mb-3">
        <label for="add_foto_perfil" class="form-label">Foto del Instrumento</label>
        <input type="file" class="form-control" id="add_foto_perfil" name="foto_perfil" accept="image/*">
        <small class="text-muted">Formatos aceptados: JPG, PNG, GIF (Máx. 2MB)</small>
        <!-- Previsualización de la imagen -->
        <div id="preview-container" class="mt-2" style="display: none;">
            <img id="preview-image" src="#" alt="Previsualización de la imagen" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
            <button type="button" class="btn btn-sm btn-danger ms-2" id="remove-image">
                <i class="bi bi-trash"></i> Eliminar
            </button>
        </div>
    </div>
</div>



                            </div>
                        </div>
                        

<!-- Dentro de la pestaña "Información Básica" (después del campo Cantidad) -->


                        <!-- Pestaña 2: Datos Técnicos -->
                        <div class="tab-pane fade" id="tecnicos-tab-pane" role="tabpanel" tabindex="0">
                            <div class="row g-3">
                                <!-- F Location -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="add_f_location" name="f_location">
                                        <label for="add_f_location">F Location</label>
                                    </div>
                                </div>
                                
                                <!-- Service Upper -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="add_service_upper" name="service_upper">
                                        <label for="add_service_upper">Service Upper</label>
                                    </div>
                                </div>
                                
                                <!-- P ID No -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="add_p_id_no" name="p_id_no">
                                        <label for="add_p_id_no">P ID No</label>
                                    </div>
                                </div>
                                
                                <!-- SYS TAG -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="add_sys_tag" name="sys_tag">
                                        <label for="add_sys_tag">SYS TAG</label>
                                    </div>
                                </div>
                                
                                <!-- Line Size -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="add_line_size" name="line_size">
                                        <label for="add_line_size">Line Size</label>
                                    </div>
                                </div>
                                
                                <!-- Rating -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="add_rating" name="rating">
                                        <label for="add_rating">Rating</label>
                                    </div>
                                </div>


                                
                            </div>
                        </div>
                        
                        <!-- Pestaña 3: Configuración -->
                        <div class="tab-pane fade" id="sistema-tab-pane" role="tabpanel" tabindex="0">
                            <div class="row g-3">
                                <!-- Facing -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="add_facing" name="facing">
                                        <label for="add_facing">Facing</label>
                                    </div>
                                </div>
                                
                                <!-- Lineclass -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="add_lineclass" name="lineclass">
                                        <label for="add_lineclass">Lineclass</label>
                                    </div>
                                </div>
                                
                                <!-- SYSTEM IN -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="add_system_in" name="system_in">
                                        <label for="add_system_in">SYSTEM IN</label>
                                    </div>
                                </div>
                                
                                <!-- SYSTEM OUT -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="add_system_out" name="system_out">
                                        <label for="add_system_out">SYSTEM OUT</label>
                                    </div>
                                </div>
                                
                                <!-- IO TYPE OUT -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="add_io_type_out" name="io_type_out">
                                        <label for="add_io_type_out">IO TYPE OUT</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Pestaña 4: Otros Datos -->
                        <div class="tab-pane fade" id="otros-tab-pane" role="tabpanel" tabindex="0">
                            <div class="row g-3">
                                <!-- SIGNAL COND -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="add_signal_cond" name="signal_cond">
                                        <label for="add_signal_cond">SIGNAL COND</label>
                                    </div>
                                </div>
                                
                                <!-- CRTL ACT -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="add_crtl_act" name="crtl_act">
                                        <label for="add_crtl_act">CRTL ACT</label>
                                    </div>
                                </div>
                                
                                <!-- STATE 0 -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="add_state_0" name="state_0">
                                        <label for="add_state_0">STATE 0</label>
                                    </div>
                                </div>
                                
                                <!-- STATE 1 -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="add_state_1" name="state_1">
                                        <label for="add_state_1">STATE 1</label>
                                    </div>
                                </div>
                                
                                <!-- Po Number -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="add_po_number" name="po_number">
                                        <label for="add_po_number">Po Number</label>
                                    </div>
                                </div>
                                
                                <!-- Junction Box No -->
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="add_junction_box_no" name="junction_box_no">
                                        <label for="add_junction_box_no">Junction Box No</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="add_herramientas" name="herramientas">
                                        <label for="add_herramientas">Herramientas</label>
                                    </div>
                                </div>
        
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="add_empacadura" name="empacadura">
                                        <label for="add_empacadura">Empacadura</label>
                                    </div>
                                </div>
        
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" class="form-control" id="add_esparragos" name="esparragos">
                                        <label for="add_esparragos">Esparragos</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pie del Modal -->
                    <div class="modal-footer bg-light mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Guardar instrumento
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
// Función para buscar ubicaciones y filtrar equipos
document.getElementById('searchUbicacion').addEventListener('input', function() {
    const searchValue = this.value.trim();
    
    if (searchValue === "") {
        // Si el campo está vacío, recargar todos los equipos
        fetch(`equipo/buscar_equipos.php?id_planta=<?= $id_planta_usuario ?>`)
            .then(response => response.text())
            .then(data => {
                document.getElementById('tabla-equipos').innerHTML = data;
                equipoSeleccionado = null;
                vincularEventosTabla();
            })
            .catch(error => {
                console.error('Error al cargar equipos:', error);
                showFloatingAlert('Error al cargar equipos', 'danger');
            });
        return;
    }

    const encodedSearch = encodeURIComponent(searchValue);
    
    // Primero buscar las ubicaciones que coincidan
    fetch(`ubicacion/buscar_ubicaciones.php?search=${encodedSearch}&id_planta=<?= $id_planta_usuario ?>`)
        .then(response => response.text())
        .then(ubicacionesData => {
            // Mostrar las ubicaciones encontradas (opcional)
            // document.getElementById('tabla-ubicaciones').innerHTML = ubicacionesData;
            
            // Ahora buscar los equipos que pertenecen a estas ubicaciones
            return fetch(`equipo/buscar_equipos_por_ubicacion.php?search=${encodedSearch}&id_planta=<?= $id_planta_usuario ?>`);
        })
        .then(response => response.text())
        .then(equiposData => {
            document.getElementById('tabla-equipos').innerHTML = equiposData;
            equipoSeleccionado = null;
            vincularEventosTabla();
        })
        .catch(error => {
            console.error('Error al buscar equipos por ubicación:', error);
            showFloatingAlert('Error al buscar equipos por ubicación', 'danger');
        });
});
    // Función para mostrar la planta correspondiente a la ubicación seleccionada
    function mostrarPlanta(select) {
        const selectedOption = select.options[select.selectedIndex];
        const plantaNombre = selectedOption.getAttribute('data-planta');
        const plantaInfo = document.getElementById('planta-info');
        const nombrePlantaSpan = document.getElementById('nombre-planta');
        
        if (plantaNombre) {
            nombrePlantaSpan.textContent = plantaNombre;
            plantaInfo.style.display = 'block';
        } else {
            plantaInfo.style.display = 'none';
        }
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        vincularEventosTabla();
        
        // Mostrar alerta si hay un mensaje en la URL
        const urlParams = new URLSearchParams(window.location.search);
        const mensaje = urlParams.get('mensaje');
        const tipo = urlParams.get('tipo');
        
        if (mensaje && tipo) {
            showFloatingAlert(mensaje, tipo);
        }
    });

    let equipoSeleccionado = null;

    // Función para seleccionar un equipo
    function seleccionarEquipo(tagNumber) {
        document.querySelectorAll('#tabla-equipos tr').forEach(tr => {
            tr.classList.remove('selected-row');
        });

        const filaSeleccionada = document.querySelector(`#tabla-equipos tr[onclick*="${tagNumber}"]`);
        if (filaSeleccionada) {
            filaSeleccionada.classList.add('selected-row');
        }

        equipoSeleccionado = tagNumber;
    }



// Función para buscar equipos
document.getElementById('search').addEventListener('input', function() {
    const searchValue = this.value.trim();
    if (searchValue === "") {
        location.reload();
        return;
    }

    const encodedSearch = encodeURIComponent(searchValue);
    // Enviamos el id_planta como parámetro
    fetch(`equipo/buscar_equipos.php?search=${encodedSearch}&id_planta=<?= $id_planta_usuario ?>`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('tabla-equipos').innerHTML = data;
            equipoSeleccionado = null;
            vincularEventosTabla();
        })
        .catch(error => {
            console.error('Error al buscar equipos:', error);
            showFloatingAlert('Error al buscar equipos', 'danger');
        });
});

    function vincularEventosTabla() {
        const filas = document.querySelectorAll('#tabla-equipos tr');
        filas.forEach(fila => {
            fila.addEventListener('click', function(e) {
                if (e.target.tagName === 'A') {
                    return;
                }

                document.querySelectorAll('#tabla-equipos tr').forEach(tr => {
                    tr.classList.remove('selected-row');
                });

                this.classList.add('selected-row');
                const tagNumber = this.querySelector('td:first-child').textContent;
                equipoSeleccionado = tagNumber;
            });
        });
    }



    document.getElementById('add_foto_perfil').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const previewContainer = document.getElementById('preview-container');
    const previewImage = document.getElementById('preview-image');
    
    if (file) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            previewContainer.style.display = 'block';
        }
        
        reader.readAsDataURL(file);
    } else {
        previewContainer.style.display = 'none';
    }
});

// Eliminar imagen seleccionada
document.getElementById('remove-image').addEventListener('click', function() {
    const fileInput = document.getElementById('add_foto_perfil');
    const previewContainer = document.getElementById('preview-container');
    
    fileInput.value = '';
    previewContainer.style.display = 'none';
});




// Función para validar si el Tag Number contiene espacios
function validarEspaciosTagNumber(tagNumber) {
    const tieneEspacios = /\s/.test(tagNumber);
    const inputTag = document.getElementById('add_tag_number');
    const submitBtn = document.querySelector('#formAgregarEquipo button[type="submit"]');
    
    if (tieneEspacios) {
        inputTag.classList.add('is-invalid');
        submitBtn.disabled = true;
        
        // Mostrar mensaje de error
        const feedback = document.createElement('div');
        feedback.id = 'espacios-feedback';
        feedback.className = 'invalid-feedback';
        feedback.textContent = 'El Tag Number no puede contener espacios';
        
        if (!document.getElementById('espacios-feedback')) {
            inputTag.parentNode.appendChild(feedback);
        }
        return false;
    } else {
        inputTag.classList.remove('is-invalid');
        const feedback = document.getElementById('espacios-feedback');
        if (feedback) {
            feedback.remove();
        }
        return true;
    }
}

// Modifica la función validarTagNumber existente
function validarTagNumber(tagNumber) {
    if (tagNumber.trim() === '') {
        document.getElementById('add_tag_number').classList.remove('is-invalid');
        return;
    }

    // Primero validar que no tenga espacios
    if (!validarEspaciosTagNumber(tagNumber)) {
        return; // Si tiene espacios, no continuar con la validación de existencia
    }

    fetch(`equipo/validar_tag.php?tag_number=${encodeURIComponent(tagNumber)}`)
        .then(response => response.json())
        .then(data => {
            const inputTag = document.getElementById('add_tag_number');
            const submitBtn = document.querySelector('#formAgregarEquipo button[type="submit"]');
            
            if (data.existe) {
                inputTag.classList.add('is-invalid');
                submitBtn.disabled = true;
                // Mostrar mensaje de error
                const feedback = document.createElement('div');
                feedback.id = 'tag-feedback';
                feedback.className = 'invalid-feedback';
                feedback.textContent = 'Este Tag Number ya existe en la base de datos';
                if (!document.getElementById('tag-feedback')) {
                    inputTag.parentNode.appendChild(feedback);
                }
            } else {
                inputTag.classList.remove('is-invalid');
                submitBtn.disabled = false;
                const feedback = document.getElementById('tag-feedback');
                if (feedback) {
                    feedback.remove();
                }
            }
        })
        .catch(error => {
            console.error('Error al validar Tag Number:', error);
            showFloatingAlert('Error al validar el Tag Number', 'danger');
        });
}

    // Manejar el envío del formulario de agregar equipo
    document.getElementById('formAgregarEquipo').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const tagNumber = document.getElementById('add_tag_number').value.trim();
        
        // Validar nuevamente antes de enviar
        fetch(`equipo/validar_tag.php?tag_number=${encodeURIComponent(tagNumber)}`)
            .then(response => response.json())
            .then(data => {
                if (data.existe) {
                    showFloatingAlert('Este Tag Number ya existe. Por favor ingrese uno diferente.', 'warning');
                    document.getElementById('add_tag_number').focus();
                } else {
                    // Si no existe, enviar el formulario via AJAX
                    const formData = new FormData(this);
                    
                    fetch(this.action, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Mostrar alerta de éxito
                            showFloatingAlert('Equipo agregado correctamente', 'success');
                            
                            // Cerrar el modal después de 1.5 segundos
                            setTimeout(() => {
                                const modal = bootstrap.Modal.getInstance(document.getElementById('agregarModal'));
                                modal.hide();
                                
                                // Recargar la página para mostrar los cambios
                                location.reload();
                            }, 1500);
                        } else {
                            showFloatingAlert('Error al agregar el equipo: ' + data.message, 'danger');
                        }
                    })
                    .catch(error => {
                        showFloatingAlert('Error en la solicitud: ' + error, 'danger');
                    });
                }
            })
            .catch(error => {
                console.error('Error en la validación:', error);
                showFloatingAlert('Error al validar el Tag Number', 'danger');
            });
    });

    // Auto-cierre de alertas después de 5 segundos
    const alerts = document.querySelectorAll('.alert-auto-close');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
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

</script>
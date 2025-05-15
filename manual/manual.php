<?php
require_once __DIR__ . '/../includes/conexion.php';
require_once __DIR__ . '/../includes/check_permission.php';
require_once __DIR__ . '/../includes/audit.php';


// Obtener id_usuario del usuario logueado
$idUsuario = $_SESSION['id_usuario'] ?? null;

if (!$idUsuario) {
    die("Usuario no autenticado.");
}

// Obtener la planta del usuario logueado
try {
    $stmtPlantaUsuario = $pdo->prepare("SELECT id_planta FROM usuario WHERE id_usuario = ?");
    $stmtPlantaUsuario->execute([$idUsuario]);
    $usuario = $stmtPlantaUsuario->fetch(PDO::FETCH_ASSOC);
    if (!$usuario) {
        die("Usuario no encontrado.");
    }
    $idPlantaUsuario = $usuario['id_planta'];
} catch (PDOException $e) {
    die("Error al obtener planta del usuario: " . $e->getMessage());
}

try {
    // Obtener lista de manuales con información de planta filtrada por planta del usuario
    $stmt = $pdo->prepare("SELECT m.id_manual, m.descripcion, p.nombres as planta_nombre 
                         FROM manual m 
                         JOIN planta p ON m.id_planta = p.id_planta
                         WHERE m.estado='activo' AND m.id_planta = ?");
    $stmt->execute([$idPlantaUsuario]);
    $manuales = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error al obtener manuales: " . $e->getMessage());
}

// Obtener solo la planta del usuario para el formulario
try {
    $stmt_plantas = $pdo->prepare("SELECT id_planta, nombres FROM planta WHERE id_planta = ?");
    $stmt_plantas->execute([$idPlantaUsuario]);
    $plantas = $stmt_plantas->fetchAll();
} catch (PDOException $e) {
    die("Error al obtener plantas: " . $e->getMessage());
}
logModuleAccess('manual');

?>










<!-- Cabecera fija -->
<div class="sticky-top bg-white p-3 shadow-sm">
    <h4 class="text-center mb-3">Gestión de Manuales</h4>
    
    <div class="d-flex justify-content-between align-items-center">
        <!-- Botones de acción -->
        <div class="d-flex gap-2">
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#agregarManualModal">Agregar Categoria</button>
        </div>
        
        <!-- Barra de búsqueda -->
        <form class="d-flex" role="search">
            <input type="text" id="search" class="form-control" placeholder="Buscar por categoria..." 
                   value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" 
                   onkeyup="filtrarManuales()">
        </form>
    </div>
</div>

<script>
function filtrarManuales() {
    const input = document.getElementById('search');
    const filter = input.value.trim();
    
    // Realizar petición al servidor
    fetch(`manual/buscar_manual.php?search=${encodeURIComponent(filter)}`)
        .then(response => response.text())
        .then(data => {
            document.getElementById('tabla-manuales').innerHTML = data;
            // Reiniciar la selección después de filtrar
            selectedManualId = null;
        })
        .catch(error => console.error('Error:', error));
}
</script>







<div id="alertContainer" class="position-fixed top-0 end-0 p-3" style="z-index: 1100"></div>

<!-- Tabla de registros -->
<div class="table-responsive mt-3">
<table class="table table-striped">
    <thead class="table-dark">
        <tr>
            <th>#</th>
            <th>Categoria</th>
            <th>Planta</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody id="tabla-manuales">
        <?php foreach ($manuales as $index => $manual): ?>
        <tr data-id="<?= $manual['id_manual'] ?>" onclick="seleccionarID(<?= $manual['id_manual'] ?>)">
            <td><?= $index + 1 ?></td> <!-- index + 1 para que empiece en 1 -->
            <td><?= htmlspecialchars($manual['descripcion']) ?></td>
            <td><?= htmlspecialchars($manual['planta_nombre']) ?></td>
            <td>
                <a href="manual/ver_pdf.php?id_manual=<?= $manual['id_manual'] ?>&categoria=<?= urlencode($manual['descripcion']) ?>" 
                   class="btn btn btn-danger">Ver Manuales</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<!-- Modal para Agregar Manual -->
<div class="modal fade" id="agregarManualModal" tabindex="-1" aria-labelledby="agregarManualModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <!-- Encabezado del Modal -->
            <div class="modal-header bg-gradient-primary text-white">
                <h3 class="modal-title fw-bold" id="agregarManualModalLabel">
                    <i class="bi bi-journal-plus me-2"></i>
                    Agregar Categoria
                </h3>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <!-- Cuerpo del Modal -->
            <div class="modal-body p-4">
                <form id="formAgregarManual" action="manual/agregar_categoria.php" method="POST">
                <div class="mb-3">
    <label class="form-label">Planta <span class="text-danger">*</span></label>
    <div class="form-control">
        <strong>
            <?php 
            // Asumiendo que $plantas es un array y queremos mostrar el primero
            // Si hay múltiples plantas y necesitas una lógica diferente, ajusta esto
            echo htmlspecialchars($plantas[0]['nombres'] ?? ''); 
            ?>
        </strong>
        <input type="hidden" name="id_planta" value="<?= htmlspecialchars($plantas[0]['id_planta'] ?? '') ?>">
    </div>
</div>
                    
                    <div class="mb-3">
                        <div class="form-floating">
                            <textarea class="form-control" id="descripcion" name="descripcion" 
                                      style="height: 120px" required maxlength="100"
                                      oninput="updateCharCounter(this.value, 'descripcionCounter', 100)"></textarea>
                            <label for="descripcion">Nombre de la categoría <span class="text-danger">*</span></label>
                        </div>
                        <div class="text-end small text-muted mt-1">
                            <span id="descripcionCounter">0</span>/100 caracteres
                        </div>
                        <div class="invalid-feedback">Ingrese una descripción válida (máx. 100 caracteres)</div>
                    </div>
                    
                    <!-- Pie del Modal -->
                    <div class="modal-footer bg-light mt-4">
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-save me-1"></i> Guardar Categoria
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>


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

let selectedManualId = null;

// Función para seleccionar un manual por su ID
function seleccionarID(id_manual) {
    // Remover la clase 'selected-row' de todas las filas
    const rows = document.querySelectorAll('#tabla-manuales tr');
    rows.forEach(row => row.classList.remove('selected-row'));

    // Agregar la clase 'selected-row' a la fila seleccionada
    const selectedRow = document.querySelector(`#tabla-manuales tr[data-id="${id_manual}"]`);
    if (selectedRow) {
        selectedRow.classList.add('selected-row');
    }

    // Habilitar el botón de eliminar
    selectedManualId = id_manual;
}


// Seleccionar un registro de la tabla utilizando delegación de eventos
document.getElementById('tabla-manuales').addEventListener('click', function(event) {
    // Verificar si el clic proviene de una fila de la tabla
    const row = event.target.closest('tr');
    if (row) {
        // Obtener el ID del manual seleccionado
        const id_manual = row.getAttribute('data-id');
        // Llamar a la función seleccionarID con el ID del manual
        seleccionarID(id_manual);
    }
});

// Configurar validación del formulario de agregar
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formAgregarManual');
    
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        event.stopPropagation();
        
        // Verificar todos los campos
        const inputs = form.querySelectorAll('select, textarea');
        let formIsValid = true;
        
        inputs.forEach(input => {
            if (!input.checkValidity()) {
                input.classList.add('is-invalid');
                formIsValid = false;
            } else {
                input.classList.remove('is-invalid');
            }
        });
        
        if (!formIsValid) {
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
                showFloatingAlert('Categoría agregada exitosamente!', 'success');
                // Cerrar modal y recargar
                setTimeout(() => {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('agregarManualModal'));
                    modal.hide();
                    setTimeout(() => {
                        window.location.href = window.location.pathname + '?tabla=manual&agregado=1';
                    }, 500);
                }, 1000);
            } else {
                showFloatingAlert('Error: ' + (data.error || 'No se pudo agregar la categoría'), 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showFloatingAlert('Error en el servidor. Por favor verifica la consola para más detalles.', 'danger');
        });
    });
    
    // Validación cuando se pierde el foco de un campo
    form.querySelectorAll('select, textarea').forEach(input => {
        input.addEventListener('blur', function() {
            if (!this.checkValidity()) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    });
});


</script>
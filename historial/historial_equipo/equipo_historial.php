<?php
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/../../includes/check_permission.php';
require_once __DIR__ . '/../../includes/audit.php';

// Capturar la URL actual sin parámetros de éxito/error
$currentUrl = strtok($_SERVER['REQUEST_URI'], '?');

// Mostrar alertas si existen
$alertType = '';
$alertMessage = '';

if (isset($_GET['success'])) {
    $alertType = 'success';
    if ($_GET['success'] === 'equipo_agregado') {
        $alertMessage = 'Equipo agregado exitosamente!';
    } elseif ($_GET['success'] === 'equipo_editado') {
        $alertMessage = 'Equipo editado exitosamente!';
    } elseif ($_GET['success'] === 'equipo_habilitado') {
        $alertMessage = 'Equipo habilitado exitosamente!';
    } elseif ($_GET['success'] === 'equipo_deshabilitado') {
        $alertMessage = 'Equipo deshabilitado exitosamente!';
    }
} elseif (isset($_GET['error'])) {
    $alertType = 'danger';
    switch ($_GET['error']) {
        case 'tag_existente':
            $alertMessage = 'Error: El Tag Number ya existe';
            break;
        case 'error_bd':
            $alertMessage = 'Error en la base de datos. Por favor intente nuevamente';
            break;
        default:
            $alertMessage = 'Error desconocido';
    }
}

// Consulta para obtener todos los equipos con información de ubicación y planta
$stmt = $pdo->prepare("
    SELECT e.Tag_Number, e.Instrument_Type_Desc, u.id_ubicacion, p.nombres AS nombre_planta, e.estado 
    FROM equipos e
    JOIN ubicacion u ON e.id_ubicacion = u.id_ubicacion
    JOIN planta p ON u.id_planta = p.id_planta
    ORDER BY e.estado, e.Tag_Number
");
$stmt->execute();
$equipos = $stmt->fetchAll();

// Consulta para obtener las ubicaciones activas (para los modales)
$stmt = $pdo->prepare("
    SELECT u.id_ubicacion, p.nombres AS nombre_planta 
    FROM ubicacion u
    JOIN planta p ON u.id_planta = p.id_planta
    WHERE u.estado = 'activo' AND p.estado = 'activo'
");
$stmt->execute();
$ubicaciones = $stmt->fetchAll();
logModuleAccess('Historial equipos');

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Instrumentos</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .selected-row {
            background-color: #e0f7fa;
            font-weight: bold;
            outline: 2px solid #b71513;
            outline-offset: -2px;
        }
        
        #alertContainer {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1100;
        }
        
        .alert-floating {
            min-width: 300px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 10px;
        }
        
        .badge-custom {
            background-color: #b71513;
        }
        
        .btn-custom {
            background-color: #b71513;
            color: white;
            border: none;
        }
        
        .btn-custom:hover {
            background-color: #9a120f;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Container for floating alerts -->
    <div id="alertContainer">
        <?php if ($alertMessage): ?>
        <div class="alert alert-<?= $alertType ?> alert-floating alert-dismissible fade show" role="alert">
            <?= $alertMessage ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
    </div>

    <div class="sticky-top bg-white p-3 shadow-sm">
        <h4 class="text-center mb-1">Historial de Equipos</h4>

        <!-- Barra de búsqueda y botones -->
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex gap-2">
                <button id="btnCambiarEstado" class="btn btn-custom" disabled onclick="cambiarEstadoEquipo()">
                    Cambiar Estado
                </button>
            </div>
            <div style="width: 300px;">
                <input type="text" id="search" class="form-control" placeholder="Buscar por Tag Number" onkeyup="filtrarEquipos()" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
            </div>
        </div>
    </div>

    <!-- Tabla de registros -->
    <table class="table table-striped">
        <thead class="table-dark">
            <tr>
                <th>Tag Number</th>
                <th>Tipo de Instrumento</th>
                <th>Ubicación</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody id="tabla-equipos">
            <?php foreach ($equipos as $equipo): ?>
            <tr id="equipo-<?= $equipo['Tag_Number'] ?>" onclick="seleccionarEquipo('<?= $equipo['Tag_Number'] ?>')" style="cursor: pointer;">
                <td><?= htmlspecialchars($equipo['Tag_Number']) ?></td>
                <td><?= htmlspecialchars($equipo['Instrument_Type_Desc']) ?></td>
                <td><?= htmlspecialchars($equipo['id_ubicacion']) ?></td>
                <td>
                    <span class="badge <?= $equipo['estado'] === 'activo' ? 'bg-success' : 'bg-secondary' ?>">
                        <?= ucfirst($equipo['estado']) ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($equipos)): ?>
            <tr>
                <td colspan="5" class="text-center">No hay equipos registrados</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <script src="assets/js/jquery-3.6.0.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>

    <script>
    let equipoSeleccionado = null;
    // Capturar los parámetros actuales de la URL
    const currentUrlParams = new URLSearchParams(window.location.search);
    // Eliminar los parámetros de éxito/error para la redirección
    currentUrlParams.delete('success');
    currentUrlParams.delete('error');
    const baseUrl = window.location.pathname;
    const cleanUrl = baseUrl + (currentUrlParams.toString() ? '?' + currentUrlParams.toString() : '');

    document.addEventListener('DOMContentLoaded', function() {
        // Auto-cierre de alertas después de 5 segundos
        const alerts = document.querySelectorAll('.alert-auto-close');
        alerts.forEach(alert => {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }, 5000);
        });
    });

    // Función para seleccionar un equipo con estilo personalizado
    function seleccionarEquipo(tagNumber) {
        // Remover selección previa de todas las filas
        document.querySelectorAll('#tabla-equipos tr').forEach(tr => {
            tr.classList.remove('selected-row');
        });
        
        // Seleccionar nueva fila
        const fila = document.getElementById(`equipo-${tagNumber}`);
        if (fila) {
            // Aplicar la clase con el estilo personalizado
            fila.classList.add('selected-row');
            equipoSeleccionado = tagNumber;
            document.getElementById('btnCambiarEstado').disabled = false;
        }
    }

    function cambiarEstadoEquipo() {
    if (!equipoSeleccionado) {
        showFloatingAlert('Por favor seleccione un equipo primero', 'warning');
        return;
    }

    const fila = document.getElementById(`equipo-${equipoSeleccionado}`);
    const estadoActual = fila.querySelector('span.badge').textContent.trim().toLowerCase();
    const accion = estadoActual === 'activo' ? 'deshabilitar' : 'habilitar';
    const nombreAccion = estadoActual === 'activo' ? 'deshabilitar' : 'habilitar';
    const endpoint = `historial/historial_equipo/${accion}_equipo.php?tag_number=${equipoSeleccionado}`;

    if (confirm(`¿Estás seguro de que deseas ${nombreAccion} este equipo?`)) {
        fetch(endpoint)
            .then(response => {
                if (!response.ok) throw new Error('Error en la respuesta del servidor');
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showFloatingAlert(`Equipo ${nombreAccion}do exitosamente!`, 'success');
                    
                    // Actualizar la fila en la tabla
                    const badge = fila.querySelector('span.badge');
                    if (estadoActual === 'activo') {
                        badge.className = 'badge bg-secondary';
                        badge.textContent = 'Inactivo';
                    } else {
                        badge.className = 'badge bg-success';
                        badge.textContent = 'Activo';
                    }
                    
                    document.getElementById('btnCambiarEstado').disabled = true;
                    equipoSeleccionado = null;
                    
                    const redirectUrl = cleanUrl + (cleanUrl.includes('?') ? '&' : '?') + `success=equipo_${accion}do`;
                    setTimeout(() => {
                        window.location.href = redirectUrl;
                    }, 1000);
                } else {
                    throw new Error(data.message || 'Error desconocido al cambiar estado');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showFloatingAlert(error.message, 'danger');
                
                // Si es un error de validación (planta/ubicación inactiva), mantener selección
                if (!error.message.includes('No se puede habilitar')) {
                    document.getElementById('btnCambiarEstado').disabled = true;
                    equipoSeleccionado = null;
                }
            });
    }
}

    // Función para filtrar equipos
    function filtrarEquipos() {
        const searchValue = document.getElementById('search').value.trim().toLowerCase();
        const rows = document.querySelectorAll('#tabla-equipos tr[id^="equipo-"]');
        
        rows.forEach(row => {
            const tagNumber = row.cells[0].textContent.toLowerCase();
            const instrumentType = row.cells[1].textContent.toLowerCase();
            const ubicacion = row.cells[2].textContent.toLowerCase();
            
            if (tagNumber.includes(searchValue) || 
                instrumentType.includes(searchValue) || 
                ubicacion.includes(searchValue)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
        
        // Actualizar la URL con el parámetro de búsqueda
        const params = new URLSearchParams(window.location.search);
        if (searchValue) {
            params.set('search', searchValue);
        } else {
            params.delete('search');
        }
        params.delete('success');
        params.delete('error');
        
        const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        window.history.replaceState(null, '', newUrl);
    }

    // Función para mostrar alertas flotantes
    function showFloatingAlert(message, type) {
        const alertContainer = document.getElementById('alertContainer');
        const alertId = 'alert-' + Date.now();
        
        const alertDiv = document.createElement('div');
        alertDiv.id = alertId;
        alertDiv.className = `alert alert-${type} alert-floating alert-dismissible fade show`;
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

    // Cargar búsqueda inicial si existe
    document.addEventListener('DOMContentLoaded', function() {
        const searchParam = new URLSearchParams(window.location.search).get('search');
        if (searchParam) {
            document.getElementById('search').value = searchParam;
            filtrarEquipos();
        }
    });
    </script>
</body>
</html>
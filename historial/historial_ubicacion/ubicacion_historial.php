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
    if ($_GET['success'] === 'ubicacion_agregada') {
        $alertMessage = 'Ubicación agregada exitosamente!';
    } elseif ($_GET['success'] === 'ubicacion_editada') {
        $alertMessage = 'Ubicación editada exitosamente!';
    } elseif ($_GET['success'] === 'ubicacion_habilitada') {
        $alertMessage = 'Ubicación habilitada exitosamente!';
    } elseif ($_GET['success'] === 'ubicacion_deshabilitada') {
        $alertMessage = 'Ubicación deshabilitada exitosamente!';
    }
} elseif (isset($_GET['error'])) {
    $alertType = 'danger';
    switch ($_GET['error']) {
        case 'id_existente':
            $alertMessage = 'Error: El ID de ubicación ya existe';
            break;
        case 'error_bd':
            $alertMessage = 'Error en la base de datos. Por favor intente nuevamente';
            break;
        default:
            $alertMessage = 'Error desconocido';
    }
}

// Consulta para obtener todas las ubicaciones con información de planta
$stmt = $pdo->prepare("
    SELECT u.id_ubicacion, p.nombres AS nombre_planta, u.descripcion, u.estado 
    FROM ubicacion u
    JOIN planta p ON u.id_planta = p.id_planta
    ORDER BY u.estado, u.id_ubicacion
");
$stmt->execute();
$ubicaciones = $stmt->fetchAll();

// Consulta para obtener las plantas activas (para los modales)
$stmt = $pdo->prepare("SELECT id_planta, nombres FROM planta WHERE estado = 'activo'");
$stmt->execute();
$plantas = $stmt->fetchAll();
logModuleAccess('Historial ubicacion');

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Ubicaciones</title>
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
        <h4 class="text-center mb-1">Historial de Ubicaciones</h4>

        <!-- Barra de búsqueda y botones -->
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex gap-2">
                <button id="btnCambiarEstado" class="btn btn-danger" disabled onclick="cambiarEstadoUbicacion()">
                    Cambiar Estado
                </button>
            </div>
            <div style="width: 300px;">
                <input type="text" id="search" class="form-control" placeholder="Buscar por ID Ubicación" onkeyup="filtrarUbicaciones()" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
            </div>
        </div>
    </div>

    <!-- Tabla de registros -->
    <table class="table table-striped">
        <thead class="table-dark">
            <tr>
                <th>ID Ubicación</th>
                <th>Planta</th>
                <th>Descripción</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody id="tabla-ubicaciones">
            <?php foreach ($ubicaciones as $ubicacion): ?>
            <tr id="ubicacion-<?= $ubicacion['id_ubicacion'] ?>" onclick="seleccionarUbicacion('<?= $ubicacion['id_ubicacion'] ?>')" style="cursor: pointer;">
                <td><?= htmlspecialchars($ubicacion['id_ubicacion']) ?></td>
                <td><?= htmlspecialchars($ubicacion['nombre_planta']) ?></td>
                <td><?= htmlspecialchars($ubicacion['descripcion']) ?></td>
                <td>
                    <span class="badge <?= $ubicacion['estado'] === 'activo' ? 'bg-success' : 'bg-secondary' ?>">
                        <?= ucfirst($ubicacion['estado']) ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($ubicaciones)): ?>
            <tr>
                <td colspan="4" class="text-center">No hay ubicaciones registradas</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <script src="assets/js/jquery-3.6.0.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>

    <script>
    let ubicacionSeleccionada = null;
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

    // Función para seleccionar una ubicación con estilo personalizado
    function seleccionarUbicacion(id_ubicacion) {
        // Remover selección previa de todas las filas
        document.querySelectorAll('#tabla-ubicaciones tr').forEach(tr => {
            tr.classList.remove('selected-row');
        });
        
        // Seleccionar nueva fila
        const fila = document.getElementById(`ubicacion-${id_ubicacion}`);
        if (fila) {
            // Aplicar la clase con el estilo personalizado
            fila.classList.add('selected-row');
            ubicacionSeleccionada = id_ubicacion;
            document.getElementById('btnCambiarEstado').disabled = false;
        }
    }

  // Función para cambiar el estado de una ubicación
  function cambiarEstadoUbicacion() {
    if (!ubicacionSeleccionada) {
        showFloatingAlert('Por favor seleccione una ubicación primero', 'warning');
        return;
    }

    // Obtener el estado actual de la ubicación seleccionada
    const fila = document.getElementById(`ubicacion-${ubicacionSeleccionada}`);
    const estadoActual = fila.querySelector('span.badge').textContent.trim().toLowerCase();
    const nombrePlanta = fila.cells[1].textContent.trim();
    
    const accion = estadoActual === 'activo' ? 'deshabilitar' : 'habilitar';
    const nombreAccion = estadoActual === 'activo' ? 'deshabilitar' : 'habilitar';
    
    // Verificar si estamos intentando habilitar
    if (accion === 'habilitar') {
        // Primero verificar el estado de la planta
        fetch(`historial/historial_ubicacion/verificar_planta.php?id_ubicacion=${ubicacionSeleccionada}`)
            .then(response => {
                if (!response.ok) throw new Error('Error al verificar planta');
                return response.json();
            })
            .then(data => {
                if (data.planta_activa) {
                    // Si la planta está activa, proceder con la habilitación
                    confirmarCambioEstado();
                } else {
                    // Si la planta está inactiva, mostrar alerta
                    showFloatingAlert(`No se puede habilitar la ubicación porque la planta "${nombrePlanta}" está inactiva. Active primero la planta.`, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showFloatingAlert(`Error al verificar estado de la planta: ${error.message}`, 'danger');
            });
    } else {
        // Para deshabilitar no necesitamos verificar la planta
        confirmarCambioEstado();
    }

    // Función para confirmar y ejecutar el cambio de estado
    function confirmarCambioEstado() {
        if (confirm(`¿Estás seguro de que deseas ${nombreAccion} esta ubicación y todos sus equipos asociados?`)) {
            fetch(`historial/historial_ubicacion/${accion}_ubicacion.php?id_ubicacion=${ubicacionSeleccionada}`)
                .then(response => {
                    if (!response.ok) throw new Error('Error en la respuesta del servidor');
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showFloatingAlert(`Ubicación y sus equipos ${nombreAccion}dos exitosamente!`, 'success');
                        
                        // Actualizar la fila en la tabla
                        const badge = fila.querySelector('span.badge');
                        if (estadoActual === 'activo') {
                            badge.className = 'badge bg-secondary';
                            badge.textContent = 'Inactivo';
                        } else {
                            badge.className = 'badge bg-success';
                            badge.textContent = 'Activo';
                        }
                        
                        // Deshabilitar el botón hasta nueva selección
                        document.getElementById('btnCambiarEstado').disabled = true;
                        ubicacionSeleccionada = null;
                        
                        // Redirigir a la URL limpia con el parámetro de éxito
                        const redirectUrl = cleanUrl + (cleanUrl.includes('?') ? '&' : '?') + `success=ubicacion_${accion}da`;
                        setTimeout(() => {
                            window.location.href = redirectUrl;
                        }, 1000);
                    } else {
                        throw new Error(data.message || 'Error desconocido al cambiar estado');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showFloatingAlert(`Error al ${nombreAccion} la ubicación: ${error.message}`, 'danger');
                });
        }
    }
}
    // Función para filtrar ubicaciones
    function filtrarUbicaciones() {
        const searchValue = document.getElementById('search').value.trim().toLowerCase();
        const rows = document.querySelectorAll('#tabla-ubicaciones tr[id^="ubicacion-"]');
        
        rows.forEach(row => {
            const idUbicacion = row.cells[0].textContent.toLowerCase();
            if (idUbicacion.includes(searchValue)) {
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
            filtrarUbicaciones();
        }
    });
    </script>
</body>
</html>
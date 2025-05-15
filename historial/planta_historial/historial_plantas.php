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
    if ($_GET['success'] === 'planta_habilitada') {
        $alertMessage = 'Planta habilitada exitosamente!';
    } elseif ($_GET['success'] === 'planta_deshabilitada') {
        $alertMessage = 'Planta deshabilitada exitosamente!';
    }
} elseif (isset($_GET['error'])) {
    $alertType = 'danger';
    switch ($_GET['error']) {
        case 'error_bd':
            $alertMessage = 'Error en la base de datos. Por favor intente nuevamente';
            break;
        default:
            $alertMessage = 'Error desconocido';
    }
}

// Consulta para obtener todas las plantas
$stmt = $pdo->query("
    SELECT id_planta, nombres, estado 
    FROM planta
    ORDER BY estado, nombres
");
$plantas = $stmt->fetchAll();
logModuleAccess('Historial Planta');

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Plantas</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
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
        <h4 class="text-center mb-1">Historial de Plantas</h4>

        <!-- Barra de búsqueda y botones -->
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex gap-2">
                <button id="btnCambiarEstado" class="btn btn-danger" disabled onclick="cambiarEstadoPlanta()">
                    Cambiar Estado
                </button>
            </div>
            <div style="width: 300px;">
                <input type="text" id="search" class="form-control" placeholder="Buscar por Nombre" onkeyup="filtrarPlantas()" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
            </div>
        </div>
    </div>

    <!-- Tabla de registros -->
    <table class="table table-striped">
        <thead class="table-dark">
            <tr>
                <th>ID Planta</th>
                <th>Nombre</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody id="tabla-plantas">
            <?php foreach ($plantas as $planta): ?>
            <tr id="planta-<?= $planta['id_planta'] ?>" onclick="seleccionarPlanta('<?= $planta['id_planta'] ?>')" style="cursor: pointer;">
                <td><?= htmlspecialchars($planta['id_planta']) ?></td>
                <td><?= htmlspecialchars($planta['nombres']) ?></td>
                <td>
                    <span class="badge <?= $planta['estado'] === 'activo' ? 'bg-success' : 'bg-secondary' ?>">
                        <?= ucfirst($planta['estado']) ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($plantas)): ?>
            <tr>
                <td colspan="3" class="text-center">No hay plantas registradas</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <script src="../assets/js/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>

    <script>
    let plantaSeleccionada = null;
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

    // Función para seleccionar una planta con estilo personalizado
    function seleccionarPlanta(id_planta) {
        // Remover selección previa de todas las filas
        document.querySelectorAll('#tabla-plantas tr').forEach(tr => {
            tr.classList.remove('selected-row');
        });
        
        // Seleccionar nueva fila
        const fila = document.getElementById(`planta-${id_planta}`);
        if (fila) {
            // Aplicar la clase con el estilo personalizado
            fila.classList.add('selected-row');
            plantaSeleccionada = id_planta;
            document.getElementById('btnCambiarEstado').disabled = false;
        }
    }

    // Función para cambiar el estado de una planta
    function cambiarEstadoPlanta() {
        if (!plantaSeleccionada) {
            showFloatingAlert('Por favor seleccione una planta primero', 'warning');
            return;
        }

        // Obtener el estado actual de la planta seleccionada
        const fila = document.getElementById(`planta-${plantaSeleccionada}`);
        const estadoActual = fila.querySelector('span.badge').textContent.trim().toLowerCase();
        const nombrePlanta = fila.cells[1].textContent.trim();
        
        const accion = estadoActual === 'activo' ? 'deshabilitar' : 'habilitar';
        const nombreAccion = estadoActual === 'activo' ? 'deshabilitar' : 'habilitar';
        const mensaje = accion === 'habilitar' 
            ? `¿Estás seguro de que deseas habilitar la planta "${nombrePlanta}"? Esto también habilitará todas sus ubicaciones y equipos asociados.`
            : `¿Estás seguro de que deseas deshabilitar la planta "${nombrePlanta}"? Esto también deshabilitará todas sus ubicaciones y equipos asociados.`;
        
        if (confirm(mensaje)) {
            fetch(`historial/planta_historial/${accion}_planta_historial.php?id_planta=${plantaSeleccionada}`)
                .then(response => {
                    if (!response.ok) throw new Error('Error en la respuesta del servidor');
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showFloatingAlert(`Planta ${nombreAccion}da exitosamente!`, 'success');
                        
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
                        plantaSeleccionada = null;
                        
                        // Redirigir a la URL limpia con el parámetro de éxito
                        const redirectUrl = cleanUrl + (cleanUrl.includes('?') ? '&' : '?') + `success=planta_${accion}da`;
                        setTimeout(() => {
                            window.location.href = redirectUrl;
                        }, 1000);
                    } else {
                        throw new Error(data.message || 'Error desconocido al cambiar estado');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showFloatingAlert(`Error al ${nombreAccion} la planta: ${error.message}`, 'danger');
                });
        }
    }

    // Función para filtrar plantas
    function filtrarPlantas() {
        const searchValue = document.getElementById('search').value.trim().toLowerCase();
        const rows = document.querySelectorAll('#tabla-plantas tr[id^="planta-"]');
        
        rows.forEach(row => {
            const nombrePlanta = row.cells[1].textContent.toLowerCase();
            if (nombrePlanta.includes(searchValue)) {
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
            filtrarPlantas();
        }
    });
    </script>
</body>
</html>
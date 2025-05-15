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
    if ($_GET['success'] === 'fabricante_habilitado') {
        $alertMessage = 'Fabricante habilitado exitosamente!';
    } elseif ($_GET['success'] === 'fabricante_deshabilitado') {
        $alertMessage = 'Fabricante deshabilitado exitosamente!';
    }
} elseif (isset($_GET['error'])) {
    $alertType = 'danger';
    switch ($_GET['error']) {
        case 'error_bd':
            $alertMessage = 'Error en la base de datos. Por favor intente nuevamente';
            break;
        case 'fabricante_con_repuestos':
            $alertMessage = 'No se puede deshabilitar el fabricante porque tiene repuestos asociados';
            break;
        default:
            $alertMessage = 'Error desconocido';
    }
}

// Consulta para obtener todos los fabricantes
$stmt = $pdo->query("
    SELECT id_fabricante, nombres, estado 
    FROM fabricantes
    ORDER BY estado, nombres
");
$fabricantes = $stmt->fetchAll();
logModuleAccess('Historial Fabricante');

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de Fabricantes</title>
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
        
        .table-responsive {
            max-height: calc(100vh - 200px);
            overflow-y: auto;
        }
        
        .fixed-header {
            position: sticky;
            top: 0;
            background-color: #343a40;
            color: white;
            z-index: 10;
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
        <h4 class="text-center mb-1">Historial de Fabricantes</h4>

        <!-- Barra de búsqueda y botones -->
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex gap-2">
                <button id="btnCambiarEstado" class="btn btn-danger" disabled onclick="cambiarEstadoFabricante()">
                    Cambiar Estado
                </button>
            </div>
            <div style="width: 300px;">
                <input type="text" id="search" class="form-control" placeholder="Buscar por Nombre" onkeyup="filtrarFabricantes()" value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
            </div>
        </div>
    </div>

    <!-- Tabla de registros -->
    <div class="table-responsive">
        <table class="table table-striped">
            <thead class="table-dark fixed-header">
                <tr>
                    <th>ID Fabricante</th>
                    <th>Nombre</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody id="tabla-fabricantes">
                <?php foreach ($fabricantes as $fabricante): ?>
                <tr id="fabricante-<?= $fabricante['id_fabricante'] ?>" onclick="seleccionarFabricante('<?= $fabricante['id_fabricante'] ?>')" style="cursor: pointer;">
                    <td><?= htmlspecialchars($fabricante['id_fabricante']) ?></td>
                    <td><?= htmlspecialchars($fabricante['nombres']) ?></td>
                    <td>
                        <span class="badge <?= $fabricante['estado'] === 'activo' ? 'bg-success' : 'bg-secondary' ?>">
                            <?= ucfirst($fabricante['estado']) ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($fabricantes)): ?>
                <tr>
                    <td colspan="3" class="text-center">No hay fabricantes registrados</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script src="../assets/js/jquery-3.6.0.min.js"></script>
    <script src="../assets/js/bootstrap.bundle.min.js"></script>

    <script>
    let fabricanteSeleccionado = null;
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

    // Función para seleccionar un fabricante con estilo personalizado
    function seleccionarFabricante(id_fabricante) {
        // Remover selección previa de todas las filas
        document.querySelectorAll('#tabla-fabricantes tr').forEach(tr => {
            tr.classList.remove('selected-row');
        });
        
        // Seleccionar nueva fila
        const fila = document.getElementById(`fabricante-${id_fabricante}`);
        if (fila) {
            // Aplicar la clase con el estilo personalizado
            fila.classList.add('selected-row');
            fabricanteSeleccionado = id_fabricante;
            document.getElementById('btnCambiarEstado').disabled = false;
        }
    }

    // Función para cambiar el estado de un fabricante
function cambiarEstadoFabricante() {
    if (!fabricanteSeleccionado) {
        showFloatingAlert('Por favor seleccione un fabricante primero', 'warning');
        return;
    }

    const fila = document.getElementById(`fabricante-${fabricanteSeleccionado}`);
    const estadoActual = fila.querySelector('span.badge').textContent.trim().toLowerCase();
    const nombreFabricante = fila.cells[1].textContent.trim();
    
    const accion = estadoActual === 'activo' ? 'deshabilitar' : 'habilitar';
    const nombreAccion = estadoActual === 'activo' ? 'deshabilitar' : 'habilitar';
    
    // Mensaje más descriptivo considerando los repuestos
    const mensaje = estadoActual === 'activo' 
        ? `¿Estás seguro de que deseas deshabilitar el fabricante "${nombreFabricante}"? Esto también deshabilitará todos sus repuestos asociados.`
        : `¿Estás seguro de que deseas habilitar el fabricante "${nombreFabricante}"? Esto también habilitará todos sus repuestos asociados.`;
    
    if (confirm(mensaje)) {
        // Mostrar carga
        const btn = document.getElementById('btnCambiarEstado');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesando...';
        
        fetch(`historial/historial_fabricante/${accion}_fabricante_historial.php?id_fabricante=${fabricanteSeleccionado}`, {
            headers: {
                'Accept': 'application/json'
            }
        })
        .then(async response => {
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                throw new Error(text || 'Respuesta no válida del servidor');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showFloatingAlert(data.message || `Fabricante ${nombreAccion}do exitosamente!`, 'success');
                
                // Actualizar la fila en la tabla
                const badge = fila.querySelector('span.badge');
                if (estadoActual === 'activo') {
                    badge.className = 'badge bg-secondary';
                    badge.textContent = 'Inactivo';
                } else {
                    badge.className = 'badge bg-success';
                    badge.textContent = 'Activo';
                }
                
                // Redirigir a la URL limpia con el parámetro de éxito
                const redirectUrl = cleanUrl + (cleanUrl.includes('?') ? '&' : '?') + `success=fabricante_${accion}do`;
                setTimeout(() => {
                    window.location.href = redirectUrl;
                }, 1000);
            } else {
                throw new Error(data.message || 'Error desconocido al cambiar estado');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showFloatingAlert(`Error al ${nombreAccion} el fabricante: ${error.message}`, 'danger');
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = 'Cambiar Estado';
            fabricanteSeleccionado = null;
        });
    }
}

    // Función para filtrar fabricantes
    function filtrarFabricantes() {
        const searchValue = document.getElementById('search').value.trim().toLowerCase();
        const rows = document.querySelectorAll('#tabla-fabricantes tr[id^="fabricante-"]');
        
        rows.forEach(row => {
            const nombreFabricante = row.cells[1].textContent.toLowerCase();
            if (nombreFabricante.includes(searchValue)) {
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
            filtrarFabricantes();
        }
    });
    </script>
</body>
</html>
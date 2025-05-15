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

// Consulta corregida - usando parámetros posicionales para evitar el problema
$query =  "SELECT 
m.id_mantenimiento as id,
m.Tag_Number,
m.fecha,
'Preventivo' as tipo_mantenimiento,
m.mantenimiento,
m.observaciones,
NULL as descripcion,
u.descripcion as ubicacion  
FROM mantenimiento m
JOIN equipos e ON m.Tag_Number = e.Tag_Number
JOIN ubicacion u ON e.id_ubicacion = u.id_ubicacion
WHERE u.id_planta = ?

UNION ALL

SELECT 
i.id_inter as id,
i.Tag_Number,
i.fecha,
'Correctivo' as tipo_mantenimiento,
i.mantenimiento_correctivo as mantenimiento,
NULL as observaciones,
i.descripcion,
u.descripcion as ubicacion  
FROM intervencion i
JOIN equipos e ON i.Tag_Number = e.Tag_Number
JOIN ubicacion u ON e.id_ubicacion = u.id_ubicacion
WHERE u.id_planta = ?

ORDER BY fecha DESC";

$stmt = $pdo->prepare($query);
// Ejecutar con el mismo valor para ambos parámetros
$stmt->execute([$id_planta_usuario, $id_planta_usuario]);
$registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

logModuleAccess('Mantenimiento Maestro');

?>

<link href="../../assets/css/bootstrap.min.css" rel="stylesheet">

<div class="sticky-top bg-white p-3 shadow-sm">
    <h4 class="text-center mb-3">Registro Maestro de Mantenimientos</h4>
    
    <!-- Barra de herramientas -->
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <button class="btn btn-danger" onclick="generarPDF()">
                Generar Reporte PDF
            </button>
        </div>
    </div>
    
<!-- Filtros -->
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
            <input type="date" class="form-control" id="searchFechaInicio">
            <span class="input-group-text">a</span>
            <input type="date" class="form-control" id="searchFechaFin">
        </div>
    </div>
</div>
</div>

<!-- Tabla de resultados -->
<table class="table table-striped table-container">
    <thead class="table-dark">
        <tr>
            <th>#</th>
            <th>Tag Number</th>
            <th>Fecha</th>
            <th>Tipo</th>
            <th>Observaciones/Descripción</th>
        </tr>
    </thead>
    <tbody id="tablaResultados">
    <?php foreach ($registros as $index => $row): ?>
    <tr data-tag="<?= htmlspecialchars($row['Tag_Number']) ?>" 
        data-fecha="<?= htmlspecialchars($row['fecha']) ?>"
        data-ubicacion="<?= htmlspecialchars($row['ubicacion']) ?>">  <!-- Cambiar de descripcion a ubicacion -->
        <td><?= $index + 1 ?></td>
        <td><?= htmlspecialchars($row['Tag_Number']) ?></td>
        <td><?= htmlspecialchars($row['fecha']) ?></td>
        <td><?= htmlspecialchars($row['tipo_mantenimiento']) ?></td>
        <td>
            <?php if ($row['tipo_mantenimiento'] === 'Preventivo'): ?>
                <?= htmlspecialchars($row['observaciones']) ?>
            <?php else: ?>
                <?= htmlspecialchars($row['descripcion']) ?>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</tbody>

</table>

<script>
        document.getElementById('searchTerm').addEventListener('input', filtrarRegistros);
    document.getElementById('searchType').addEventListener('change', filtrarRegistros);
    document.getElementById('searchFechaInicio').addEventListener('change', filtrarRegistros);
    document.getElementById('searchFechaFin').addEventListener('change', filtrarRegistros);
    
// Almacena todos los registros originales
const todosLosRegistros = Array.from(document.querySelectorAll('#tablaResultados tr'));

function filtrarRegistros() {
    const searchTerm = document.getElementById('searchTerm').value.toLowerCase();
    const searchType = document.getElementById('searchType').value;
    const searchFechaInicio = document.getElementById('searchFechaInicio').value;
    const searchFechaFin = document.getElementById('searchFechaFin').value;
    
    todosLosRegistros.forEach(row => {
        const tag = row.getAttribute('data-tag').toLowerCase();
        const ubicacion = row.getAttribute('data-ubicacion')?.toLowerCase() || '';
        const fecha = row.getAttribute('data-fecha');
        
        let coincideTerm = true;
        let coincideFecha = true;
        
        // Filtro por término de búsqueda
        if (searchTerm) {
            if (searchType === 'tag' && !tag.includes(searchTerm)) {
                coincideTerm = false;
            } else if (searchType === 'ubicacion' && !ubicacion.includes(searchTerm)) {
                coincideTerm = false;
            }
        }
        
        // Filtro por fecha
        if (searchFechaInicio || searchFechaFin) {
            const fechaRow = new Date(fecha);
            const fechaInicio = searchFechaInicio ? new Date(searchFechaInicio) : null;
            const fechaFin = searchFechaFin ? new Date(searchFechaFin) : null;
            
            if (fechaInicio && fechaRow < fechaInicio) {
                coincideFecha = false;
            }
            
            if (fechaFin && fechaRow > fechaFin) {
                coincideFecha = false;
            }
        }
        
        // Mostrar u ocultar fila según los filtros
        row.style.display = (coincideTerm && coincideFecha) ? '' : 'none';
    });
    
    // Mostrar mensaje si no hay resultados
    const filasVisibles = document.querySelectorAll('#tablaResultados tr:not([style*="display: none"])');
    const tablaResultados = document.getElementById('tablaResultados');
    
    // Eliminar mensaje anterior si existe
    const mensajeAnterior = document.getElementById('mensajeNoResultados');
    if (mensajeAnterior) {
        mensajeAnterior.remove();
    }
    
    if (filasVisibles.length === 0) {
        const mensaje = document.createElement('tr');
        mensaje.id = 'mensajeNoResultados';
        mensaje.innerHTML = `<td colspan="5" class="no-results">No se encontraron resultados con los criterios de búsqueda</td>`;
        tablaResultados.appendChild(mensaje);
    }
}

function generarPDF() {
    const searchTerm = document.getElementById('searchTerm').value;
    const searchType = document.getElementById('searchType').value;
    const searchFechaInicio = document.getElementById('searchFechaInicio').value;
    const searchFechaFin = document.getElementById('searchFechaFin').value;
    
    let url = `Mantenimiento/Mantenimientos_maestro/generar_pdf_maestro.php?searchTerm=${encodeURIComponent(searchTerm)}&searchType=${encodeURIComponent(searchType)}`;
    
    if (searchFechaInicio) {
        url += `&searchFechaInicio=${encodeURIComponent(searchFechaInicio)}`;
    }
    
    if (searchFechaFin) {
        url += `&searchFechaFin=${encodeURIComponent(searchFechaFin)}`;
    }
    
    window.open(url, '_blank');
}

// Initialize data-ubicacion attributes for each row
document.addEventListener('DOMContentLoaded', function() {
    // We'll need to add the location data to each row
    // This would be better done server-side, but for now we'll assume it's set
    const urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.has('searchTerm')) {
        document.getElementById('searchTerm').value = urlParams.get('searchTerm');
    }
    
    if (urlParams.has('searchType')) {
        document.getElementById('searchType').value = urlParams.get('searchType');
    }
    
    if (urlParams.has('searchFechaInicio')) {
        document.getElementById('searchFechaInicio').value = urlParams.get('searchFechaInicio');
    }
    
    if (urlParams.has('searchFechaFin')) {
        document.getElementById('searchFechaFin').value = urlParams.get('searchFechaFin');
    }
    
    // Apply initial filters if there are parameters
    if (urlParams.has('searchTerm') || urlParams.has('searchType') || 
        urlParams.has('searchFechaInicio') || urlParams.has('searchFechaFin')) {
        filtrarRegistros();
    }
});
</script>
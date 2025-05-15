<?php
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}
// Al iniciar sesión, guardar también el id_planta en la sesión
require_once __DIR__ . '/includes/conexion.php';

// Verificación adicional para parámetros GET
if (isset($_GET['tabla'])) {
    $modulo = $_GET['tabla'];
    $stmt = $pdo->prepare("SELECT 1 FROM permisos_cargo pc
                          JOIN modulos m ON pc.id_modulo = m.id_modulo
                          WHERE pc.id_cargo = ? 
                          AND (m.ruta_modulo = ? OR m.ruta_modulo LIKE ?)
                          AND pc.acceso = 1");
    $stmt->execute([$_SESSION['id_cargo'], $modulo . '.php', '%/' . $modulo . '.php']);

    if ($stmt->rowCount() === 0) {
        header("Location: no_permission.php");
        exit();
    }
}
// Obtener los módulos permitidos para el usuario
$modulos_permitidos = [];
if (isset($_SESSION['id_cargo'])) {
    $stmt = $pdo->prepare("
        SELECT m.nombre_modulo, m.ruta_modulo 
        FROM permisos_cargo pc
        JOIN modulos m ON pc.id_modulo = m.id_modulo
        WHERE pc.id_cargo = ? AND pc.acceso = 1
    ");
    $stmt->execute([$_SESSION['id_cargo']]);
    $modulos_permitidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener estadísticas para el dashboard
// Obtener estadísticas para el dashboard
$stats = [
    'equipos' => 0,
    'usuarios_habilitados' => 0,
    'mantenimientos' => 0,
    'manuales' => 0,
    'repuestos' => 0
];

try {
    // Primero obtenemos la planta del usuario
    // Cambiar esta consulta:
$stmt = $pdo->prepare("SELECT id_planta FROM usuario WHERE id_usuario = ?");

// Por esta:
$stmt = $pdo->prepare("
    SELECT u.id_planta, p.nombres 
    FROM usuario u
    JOIN planta p ON u.id_planta = p.id_planta
    WHERE u.id_usuario = ?
");
    $stmt->execute([$_SESSION['id_usuario']]);
    $usuario = $stmt->fetch();
    $id_planta = $usuario['id_planta'];

    // Contar equipos de la planta del usuario
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM equipos e
        JOIN ubicacion u ON e.id_ubicacion = u.id_ubicacion
        WHERE u.id_planta = ? AND e.estado = 'activo'
    ");
    $stmt->execute([$id_planta]);
    $stats['equipos'] = $stmt->fetch()['total'];

    // Contar mantenimientos (preventivos + correctivos) de la planta del usuario
    $stmt = $pdo->prepare("
        SELECT (
            SELECT COUNT(*) 
            FROM mantenimiento m
            JOIN equipos e ON m.Tag_Number = e.Tag_Number
            JOIN ubicacion u ON e.id_ubicacion = u.id_ubicacion
            WHERE u.id_planta = ?
        ) + (
            SELECT COUNT(*) 
            FROM intervencion i
            JOIN equipos e ON i.Tag_Number = e.Tag_Number
            JOIN ubicacion u ON e.id_ubicacion = u.id_ubicacion
            WHERE u.id_planta = ?
        ) as total
    ");
    $stmt->execute([$id_planta, $id_planta]);
    $stats['mantenimientos'] = $stmt->fetch()['total'];

    // Contar manuales asociados a equipos de la planta del usuario
    $stmt = $pdo->prepare("
       SELECT COUNT(pm.id_pdfmanual) as total 
FROM pdf_manual pm
JOIN manual m ON pm.id_manual = m.id_manual
WHERE m.id_planta = ? 
    ");
    $stmt->execute([$id_planta]);
    $stats['manuales'] = $stmt->fetch()['total'];

    // Contar repuestos asociados a equipos de la planta del usuario
    $stmt = $pdo->prepare("
       SELECT COUNT(er.id_puente) as total 
FROM equipo_repuesto er
JOIN equipos e ON er.id_equipo = e.Tag_Number
JOIN ubicacion u ON e.id_ubicacion = u.id_ubicacion
WHERE u.id_planta = ?;
    ");
    $stmt->execute([$id_planta]);
    $stats['repuestos'] = $stmt->fetch()['total'];

} catch (PDOException $e) {
    // Silenciar errores para no afectar la funcionalidad existente
    error_log("Error al obtener estadísticas: " . $e->getMessage());
}


?>
<!-- Inicio del index -->
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SISGEMIN-INICIO </title>
    <link rel="icon" href="favicon.png">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome para iconos -->
    <link href="assets/css/all.min.css" rel="stylesheet">
    <!-- Chart.js CDN -->
    <script src="assets/js/chart.js"></script>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Barra lateral (sin cambios) -->
            <div class="col-md-2 bg-dark text-white min-vh-100">
                <!-- Imagen del logo -->
                <div class="text-center mt-3 mb-3">
                    <img src="Pequiven.png" alt="Logo PEQUIVEN" class="img-fluid">
                </div>

                <ul class="nav flex-column">
                    <!-- Inicio siempre visible -->
                    <li class="nav-item">
                        <a class="nav-link text-white menu-item" href="index.php">Inicio</a>
                    </li>

                    <?php
                    // Separar los módulos por categorías
                    $modulos_normales = [];
                    $modulos_mantenimiento = [];
                    $modulos_repuestos = [];
                    $modulo_permisos = null; 
                    $modulos_historial = []; // Nueva categoría para Historial
                    // Variable para almacenar específicamente el módulo de permisos
                    
                    foreach ($modulos_permitidos as $modulo) {
                        $nombre = $modulo['nombre_modulo'];
                        $ruta = $modulo['ruta_modulo'];
                        
                        // Saltar el módulo de inicio ya que lo mostramos fijo
                        if ($ruta === 'index.php') continue;
                        
                        // Identificar y separar el módulo de permisos
                        if ($nombre === 'Gestión de Permisos') {
                            $modulo_permisos = $modulo;
                            continue;
                        }
                        
                     // Clasificar los módulos
                if (strpos($nombre, 'Mantenimiento') !== false) {
                    $modulos_mantenimiento[] = $modulo;
                } elseif (strpos($nombre, 'Repuestos') !== false || 
                         strpos($nombre, 'Etiquetas') !== false || 
                         strpos($nombre, 'Fabricantes') !== false) {
                    $modulos_repuestos[] = $modulo;
                } elseif (strpos($nombre, 'Historial') !== false) { // Nueva condición para Historial
                    $modulos_historial[] = $modulo;
                } else {
                    $modulos_normales[] = $modulo;
                }
            }

                    // Mostrar módulos normales (excluyendo permisos)
                    foreach ($modulos_normales as $modulo): ?>
                        <li class="nav-item">
                            <a class="nav-link text-white menu-item"
                                href="index.php?tabla=<?= basename($modulo['ruta_modulo'], '.php') ?>">
                                <?= htmlspecialchars($modulo['nombre_modulo']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>

                    <!-- Mostrar Gestión de Permisos en una posición específica -->
                    <?php if ($modulo_permisos !== null): ?>
                        <li class="nav-item">
                            <a class="nav-link text-white menu-item" href="permisos.php">
                                <?= htmlspecialchars($modulo_permisos['nombre_modulo']) ?>
                            </a>
                        </li>
                    <?php endif; ?>

                    <!-- Menú desplegable para Mantenimiento -->
                    <?php if (!empty($modulos_mantenimiento)): ?>
                        <li class="nav-item">
                            <a class="nav-link text-white menu-item" data-bs-toggle="collapse" href="#mantenimientoSubmenu"
                                role="button" aria-expanded="false" aria-controls="mantenimientoSubmenu">
                                Mantenimiento
                            </a>
                            <div class="collapse" id="mantenimientoSubmenu">
                                <ul class="nav flex-column ps-3">
                                    <?php foreach ($modulos_mantenimiento as $modulo): ?>
                                        <li class="nav-item">
                                            <a class="nav-link text-white menu-item"
                                                href="index.php?tabla=<?= basename($modulo['ruta_modulo'], '.php') ?>">
                                                <?= str_replace('Mantenimiento ', '', $modulo['nombre_modulo']) ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </li>
                    <?php endif; ?>

                    <!-- Menú desplegable para Repuestos -->
                    <?php if (!empty($modulos_repuestos)): ?>
                        <li class="nav-item">
                            <a class="nav-link text-white menu-item" data-bs-toggle="collapse" href="#repuestosSubmenu"
                                role="button" aria-expanded="false" aria-controls="repuestosSubmenu">
                                Repuestos
                            </a>
                            <div class="collapse" id="repuestosSubmenu">
                                <ul class="nav flex-column ps-3">
                                    <?php foreach ($modulos_repuestos as $modulo): ?>
                                        <li class="nav-item">
                                            <a class="nav-link text-white menu-item"
                                                href="index.php?tabla=<?= basename($modulo['ruta_modulo'], '.php') ?>">
                                                <?= htmlspecialchars($modulo['nombre_modulo']) ?>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </li>
                    <?php endif; ?>



                    <?php if (!empty($modulos_historial)): ?>
    <li class="nav-item">
        <a class="nav-link text-white menu-item" data-bs-toggle="collapse" href="#historialSubmenu" role="button" aria-expanded="false" aria-controls="historialSubmenu">
            Historial
        </a>
        <div class="collapse" id="historialSubmenu">
            <ul class="nav flex-column ps-3">
                <?php foreach ($modulos_historial as $modulo): ?>
                    <li class="nav-item">
                        <a class="nav-link text-white menu-item" href="index.php?tabla=<?= basename($modulo['ruta_modulo'], '.php') ?>">
                            <?= htmlspecialchars($modulo['nombre_modulo']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </li>
<?php endif; ?>

                    <!-- Cerrar sesión -->
                    <li class="nav-item">
                        <a class="nav-link text-white menu-item" href="logout.php">Cerrar Sesión</a>
                    </li>
                </ul>
            </div>

            <style>
                /* Estilos para el efecto hover vinotinto */
                .menu-item {
                    transition: all 0.3s ease;
                    padding: 10px 15px;
                    margin: 2px 0;
                    border-radius: 4px;
                }

                .menu-item:hover {
                    background-color: #b71513 !important;
                    /* Color vinotinto */
                    color: white !important;
                    transform: translateX(5px);
                }

                /* Efecto para el submenú */
                #mantenimientoSubmenu .menu-item:hover {
                    background-color: #b71513 !important;
                    /* Vinotinto más oscuro */
                }

                /* Estilo para el ítem activo */
                .menu-item.active {
                    background-color: #b71513 !important;
                    font-weight: bold;
                }
            </style>

            <script>
                // Resaltar el ítem de menú activo
                document.addEventListener('DOMContentLoaded', function () {
                    const currentUrl = window.location.href;
                    document.querySelectorAll('.menu-item').forEach(item => {
                        if (item.href === currentUrl ||
                            (item.href.includes('?tabla=') && currentUrl.includes(item.href.split('?tabla=')[1]))) {
                            item.classList.add('active');

                            // Si es un submenú, abrir el colapsable
                            if (item.closest('#mantenimientoSubmenu')) {
                                document.querySelector('[href="#mantenimientoSubmenu"]').classList.add('active');
                                document.getElementById('mantenimientoSubmenu').classList.add('show');
                            }
                        }
                    });
                });
            </script>
            <!-- Fin de la barra lateral -->

            <!-- Contenido principal -->
            <div class="col-md-10">
                <?php if (!isset($_GET['tabla'])): ?>
                    <!-- Dashboard solo se muestra en la página principal -->
                    <div class="dashboard mt-4">
                    <h2 class="mb-4">SISGEMIN     Panel de Control- Planta <?= htmlspecialchars($usuario['nombres'] ?? 'Planta') ?></h2>                        <div class="row">
                            <!-- Tarjeta de fecha y hora -->
                            <!-- Ejemplo: Asegúrate de que este elemento exista -->
                            <div id="datetime-display">
                                
                                    <div class="stat-card datetime-card">
                                        
                                        <div class="stat-value" id="liveDateTime"></div>
                                        <div class="stat-label">Fecha y Hora Actual</div>
                                    </div>
                                
                            </div>


                            <!-- Tarjeta de equipos -->
                            <div class="col-md-3">
                                <div class="stat-card equipos-card">
                                    <i class="fas fa-server"></i>
                                    <div class="stat-value"><?= $stats['equipos'] ?></div>
                                    <div class="stat-label">Instrumentos Registrados</div>
                                </div>
                            </div>



                            <!-- Tarjeta de mantenimientos -->
                            <div class="col-md-3">
                                <div class="stat-card mantenimientos-card">
                                    <i class="fas fa-tools"></i>
                                    <div class="stat-value"><?= $stats['mantenimientos'] ?></div>
                                    <div class="stat-label">Mantenimientos </div>
                                </div>
                            </div>

                            <!-- Tarjeta de manuales -->
                            <div class="col-md-3">
                                <div class="stat-card manuales-card">
                                    <i class="fas fa-book"></i>
                                    <div class="stat-value"><?= $stats['manuales'] ?></div>
                                    <div class="stat-label">Manuales </div>
                                </div>
                            </div>

                    <!-- Tarjeta de repuestos -->
                    <div class="col-md-3">
                        <div class="stat-card manuales-card">
                            <i class="fas fa-book"></i>
                            <div class="stat-value"><?= $stats['repuestos'] ?></div>
                            <div class="stat-label">Repuestos </div>
                        </div>
                    </div>
                </div>
                <!-- Nueva fila para gráficos de barras verticales -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <h5 class="text-center">Gráfico de Mantenimientos</h5>
                        <canvas id="mantenimientosChart" height="200"></canvas>
                    </div>
                    <div class="col-md-6">
                        <h5 class="text-center">Gráfico de Repuestos</h5>
                        <canvas id="repuestosChart" height="200"></canvas>
                    </div>
                </div>


                        </div>
                    </div>
                <?php endif; ?>

                <?php
                $tabla = isset($_GET['tabla']) ? $_GET['tabla'] : null;

                switch ($tabla) {
                    case 'inicio':
                        include 'index.php';
                        break;
                    case 'equipos':
                        include 'equipo/equipos.php';
                        break;
                    case 'planta':
                        include 'planta/planta.php';
                        break;
                    case 'correctivo':
                        include 'Mantenimiento/Mantenimiento_correctivo/correctivo.php';
                        break;
                    case 'preventivo':
                        include 'Mantenimiento/Mantenimiento_preventivo/preventivo.php';
                        break;
                    case 'mantenimientos_maestro':
                        include 'Mantenimiento/Mantenimientos_maestro/mantenimientos_maestro.php';
                        break;
                    case 'repuestos':
                        include 'repuestos/repuestos/repuestos.php';
                        break;
                    case 'repuestos_tag':
                        include 'repuestos/repuesto-tag/repuestos_tag.php';
                        break;
                    case 'fabricantes':
                        include 'repuestos/fabricantes/fabricantes.php';
                        break;
                    case 'permisos':
                        include 'permisos.php';
                        break;
                    case 'usuario':
                        include 'usuario/usuario.php';
                        break;
                    case 'ubicacion':
                        include 'ubicacion/ubicacion.php';
                        break;
                    case 'manual':
                        include 'manual/manual.php';
                        break;
                    case 'actividades':
                        include 'planificacion/actividades.php';
                        break;
                    case 'planificacion':
                        include 'planificacion/planificacion.php';


                        break;
                        case 'historial_plantas':
                            include 'historial/planta_historial/historial_plantas.php';
                        break;
                        case 'ubicacion_historial':
                            include 'historial/historial_ubicacion/ubicacion_historial.php';
                        break;
                        case 'equipo_historial':
                            include 'historial/historial_equipo/equipo_historial.php';
                              


                            break;
                            case 'fabricante_historial':
                                include 'historial/historial_fabricante/fabricante_historial.php';
                            break;

                            case 'manual_historial':
                                include 'historial/historial_manual/manual_historial.php';
                            break;
                            case 'repuesto_historial':
                                include 'historial/historial_repuesto/repuesto_historial.php';

                                break;
                            case 'usuario_historial':
                                include 'historial/historial_usuario/usuario_historial.php';



                    default:
                        // No incluir equipos.php por defecto, solo mostrar dashboard
                        break;
                }
                ?>
            </div>
        </div>
    </div>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Función para actualizar fecha y hora en tiempo real
       // Función para actualizar fecha y hora en tiempo real
    function updateDateTime() {
        const now = new Date();
        const options = {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        };
        
        // Capitalizar la primera letra del día de la semana
        let dateString = now.toLocaleDateString('es-ES', options);
        dateString = dateString.charAt(0).toUpperCase() + dateString.slice(1);
        
        document.getElementById('liveDateTime').textContent = dateString;
    }

    // Actualizar inmediatamente y luego cada segundo
    updateDateTime();
    setInterval(updateDateTime, 1000);


        // Datos PHP pasados a JavaScript
        const mantenimientosCount = <?= json_encode($stats['mantenimientos']) ?>;
        const repuestosCount = <?= json_encode($stats['repuestos']) ?>;

        const ctxMantenimientos = document.getElementById('mantenimientosChart').getContext('2d');
    const mantenimientosChart = new Chart(ctxMantenimientos, {
        type: 'bar',
        data: {
            labels: ['Mantenimientos'],
            datasets: [{
                label: 'Cantidad',
                data: [<?= $stats['mantenimientos'] ?>],
                backgroundColor: 'rgba(183, 21, 19, 0.8)',
                borderColor: 'rgba(183, 21, 19, 1)',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            scales: {
                x: {
                    beginAtZero: true,
                    precision: 0,
                    grid: {
                        display: false
                    }
                },
                y: {
                    grid: {
                        display: false
                    }
                }
            },
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    enabled: true,
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    titleFont: {
                        size: 14
                    },
                    bodyFont: {
                        size: 12
                    }
                }
            }
        }
    });

    // Configuración del gráfico de repuestos (más compacto)
    const ctxRepuestos = document.getElementById('repuestosChart').getContext('2d');
    const repuestosChart = new Chart(ctxRepuestos, {
        type: 'bar',
        data: {
            labels: ['Repuestos'],
            datasets: [{
                label: 'Cantidad',
                data: [<?= $stats['repuestos'] ?>],
                backgroundColor: 'rgba(183, 21, 19, 0.8)',
                borderColor: 'rgba(183, 21, 19, 1)',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            scales: {
                x: {
                    beginAtZero: true,
                    precision: 0,
                    grid: {
                        display: false
                    }
                },
                y: {
                    grid: {
                        display: false
                    }
                }
            },
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    enabled: true,
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    titleFont: {
                        size: 14
                    },
                    bodyFont: {
                        size: 12
                    }
                }
            }
        }
    });
    </script>
</body>

</html>


<style>
    body {
        background-color: #ffffff;
        color: #333;
    }

    .container {
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    h1 {
        color: #b71513;
    }

    h2 {
        color: #b71513;
    }

    h4 {
        color: #b71513;
    }

    /* Versión minimalista */
    h4.text-center.mb-1 {
        color: #b71513;
    }

    .btn-primary {
        background-color: #007bff;
        border-color: #007bff;
    }

    .btn-primary:hover {
        background-color: #0056b3;
        border-color: #004085;
    }

    .table {
        margin-top: 20px;
    }

    .table th,
    .table td {
        vertical-align: middle;
    }

    .modal-content {
        border-radius: 8px;
    }

    .form-select {
        width: auto;
    }

    .selected-row {
        background-color: #e0f7fa;
        font-weight: bold;
        outline: 2px solid#b71513;
        outline-offset: -2px;
    }




    .dashboard {
        margin-bottom: 30px;
        padding: 20px;
        border-radius: 10px;
        background-color: #f8f9fa;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    .stat-card {
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        color: white;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        border: none;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        background: linear-gradient(#b71513);
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
    }

    .stat-card i {
        font-size: 2rem;
        margin-bottom: 10px;
        opacity: 0.9;
    }

    .stat-card .stat-value {
        font-size: 1.8rem;
        font-weight: bold;
        margin: 5px 0;
    }

    .stat-card .stat-label {
        font-size: 0.9rem;
        opacity: 0.9;
        font-weight: 500;
    }

    .datetime-card {
        background: linear-gradient(#b71513 100%);
    }

 /* Gráficos más compactos */
 #mantenimientosChart, #repuestosChart {
        max-height: 180px;
        width: 100% !important;
    }

    .chart-container {
        position: relative;
        height: 180px;
        width: 100%;
        padding: 15px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    /* Mejoras en el layout */
    .dashboard h2 {
        color:#b71513;
        margin-bottom: 25px;
        padding-bottom: 10px;
        border-bottom: 2px solid #b71513;
        font-weight: 600;
    }

    .row {
        margin-bottom: 15px;
    }

    /* Ajustes para la tarjeta de fecha/hora */
    #datetime-display .stat-card {
        padding: 15px;
        
    }
    #datetime-display {
        padding: 15px;
        width: 100%;
    margin-bottom: 5px;
        
    }


    #liveDateTime {
        font-size: 1.1rem;
        font-weight: 500;
    .equipos-card {
        background: linear-gradient(135deg, #b71513);
    }
    
        }
    

    .usuarios-card {
        background: linear-gradient(135deg, #b71513);
    }

    .mantenimientos-card {
        background: linear-gradient(135deg, #b71513);
    }

    .manuales-card {
        background: linear-gradient(135deg, #b71513);
    }


    /* Modal styles */
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

    .btn.disable,
    .btn:disabled {
        background-color: #b71513;
    }


    #alertContainer {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 1100;
    }

    .alert-auto-close {
        min-width: 300px;
        margin-bottom: 10px;
    }

    .nav-tabs .nav-link {
        border: none;
        color: #495057;
        font-weight: 500;
        padding: 0.75rem 1.25rem;
        transition: all 0.3s;
    }

    .nav-tabs .nav-link:hover {
        color: #2c3e50;
        border-bottom: 3px solid #dee2e6;
    }

    .nav-tabs .nav-link.active {

        border-bottom: 3px solid #b71513;
        background-color: transparent;
    }

    a {
        color: #b71513;
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
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    }

    .no-results {
        text-align: center;
        padding: 20px;
        font-style: italic;
        color: rgb(0, 0, 0);
    }

    .search-container {
        display: flex;
        gap: 10px;
    }

    .search-box {
        width: 250px;
    }

    a{
        color: #b71513 !important;
    }
</style>
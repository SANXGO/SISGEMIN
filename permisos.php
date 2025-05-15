<?php
session_start();
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/audit.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}
// 1. Verificación de sesión y cargo para permitir solo administrador (cargo = 3)
if (!isset($_SESSION['usuario']) || $_SESSION['id_cargo'] != 3) {
    header("Location: no_permission.php");
    exit();
}



// Función para cambiar de planta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_planta'])) {
    $nueva_planta = (int)$_POST['nueva_planta'];
    
    // Verificar que la planta exista
    $stmt = $pdo->prepare("SELECT id_planta FROM planta WHERE id_planta = ?");
    $stmt->execute([$nueva_planta]);
    
    if ($stmt->rowCount() > 0) {
        // Actualizar la planta en la sesión
        $_SESSION['id_planta'] = $nueva_planta;
        
        // Actualizar la planta en la base de datos (opcional, depende de tus requerimientos)
        $stmt = $pdo->prepare("UPDATE usuario SET id_planta = ? WHERE id_usuario = ?");
        $stmt->execute([$nueva_planta, $_SESSION['id_usuario']]);
        
        $_SESSION['mensaje'] = "Planta cambiada exitosamente";
        logAuditAction('Permisos', 'Cambio Planta', "Usuario cambió a planta ID: $nueva_planta", [
            'usuario_id' => $_SESSION['id_usuario'],
            'planta_anterior' => $_SESSION['id_planta'],
            'planta_nueva' => $nueva_planta
        ]);
    } else {
        $_SESSION['error'] = "La planta seleccionada no existe";
    }
    
    header("Location: permisos.php");
    exit();
}



// CRUD para la tabla cargo
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Crear nuevo cargo
    if (isset($_POST['crear_cargo'])) {
        $nombre_cargo = trim($_POST['nombre_cargo']);
        logAuditAction('Permisos', 'Create', "Crear nuevo cargo", $_POST);

        if (!empty($nombre_cargo)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO cargo (nombre_cargo) VALUES (?)");
                $stmt->execute([$nombre_cargo]);

                logAuditAction('Permisos', 'Create', "Creado exitosamente nuevo cargo", $_POST);

                $_SESSION['mensaje'] = "Cargo creado exitosamente";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error al crear el cargo: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = "El nombre del cargo no puede estar vacío";
        }
        header("Location: permisos.php");
        exit();
    }
    
    // Actualizar cargo
    if (isset($_POST['actualizar_cargo'])) {
        $id_cargo = $_POST['id_cargo'];
        $nombre_cargo = trim($_POST['nombre_cargo']);
        logAuditAction('Permisos', 'Update', "editar cargo", $_POST);

        // Validar que no se esté editando el nombre del administrador
        if ($id_cargo == 3 && strtolower($nombre_cargo) != 'administrador') {
            $_SESSION['error'] = "No se puede modificar el nombre del cargo Administrador";
            header("Location: permisos.php");
            exit();
        }
        
        if (!empty($nombre_cargo)) {
            try {
                $stmt = $pdo->prepare("UPDATE cargo SET nombre_cargo = ? WHERE id_cargo = ?");
                $stmt->execute([$nombre_cargo, $id_cargo]);
                logAuditAction('Permisos', 'Update', "editado exitosamente el cargo", $_POST);

                $_SESSION['mensaje'] = "Cargo actualizado exitosamente";
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error al actualizar el cargo: " . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = "El nombre del cargo no puede estar vacío";
        }
        header("Location: permisos.php");
        exit();
    }
    
    // Eliminar cargo
    if (isset($_POST['eliminar_cargo'])) {
        $id_cargo = $_POST['id_cargo'];
        logAuditAction('Permisos', 'Delete', "eliminar cargo", $_POST);

        // Validar que no sea el cargo de administrador (ID 3)
        if ($id_cargo == 3) {
            $_SESSION['error'] = "No se puede eliminar el cargo de Administrador";
            header("Location: permisos.php");
            exit();
        }

        try {
            // Verificar si el cargo está siendo usado
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuario WHERE id_cargo = ?");
            $stmt->execute([$id_cargo]);
            $enUso = $stmt->fetchColumn();
            
            if ($enUso > 0) {
                $_SESSION['error'] = "No se puede eliminar el cargo porque está asignado a usuarios";
            } else {
                // Eliminar primero los permisos asociados
                $stmt = $pdo->prepare("DELETE FROM permisos_cargo WHERE id_cargo = ?");
                $stmt->execute([$id_cargo]);
                
                // Luego eliminar el cargo
                $stmt = $pdo->prepare("DELETE FROM cargo WHERE id_cargo = ?");
                $stmt->execute([$id_cargo]);
                logAuditAction('Permisos', 'Delete', "eliminado exitosamente el cargo", $_POST);

                $_SESSION['mensaje'] = "Cargo eliminado exitosamente";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error al eliminar el cargo: " . $e->getMessage();
        }
        header("Location: permisos.php");
        exit();
    }
    
    // Procesar el formulario de permisos
    if (isset($_POST['guardar_permisos'])) {
        $id_cargo = $_POST['id_cargo'];
        
        // Validar que no se estén modificando los permisos del administrador
        if ($id_cargo == 3) {
            $_SESSION['error'] = "No se pueden modificar los permisos del cargo Administrador";
            header("Location: permisos.php?cargo=" . $id_cargo);
            exit();
        }

        // Eliminar permisos existentes para este cargo
        $stmt = $pdo->prepare("DELETE FROM permisos_cargo WHERE id_cargo = ?");
        $stmt->execute([$id_cargo]);

        // Insertar nuevos permisos
        if (isset($_POST['modulos'])) {
            foreach ($_POST['modulos'] as $id_modulo) {
                $stmt = $pdo->prepare("INSERT INTO permisos_cargo (id_cargo, id_modulo, acceso) VALUES (?, ?, 1)");
                $stmt->execute([$id_cargo, $id_modulo]);
            }
        }
        logAuditAction('Permisos', 'Update', "Permisos actualizados", $_POST);

        $_SESSION['mensaje'] = "Permisos actualizados correctamente para el cargo seleccionado";
        header("Location: permisos.php?cargo=" . $id_cargo);
        exit();
    }
}

// Obtener todos los cargos
$stmt = $pdo->query("SELECT * FROM cargo ORDER BY nombre_cargo ASC");
$cargos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener todos los módulos ordenados alfabéticamente
$stmt = $pdo->query("SELECT * FROM modulos ORDER BY nombre_modulo ASC");
$modulos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener permisos actuales para el cargo seleccionado
$permisos_actuales = [];
if (isset($_GET['cargo'])) {
    $id_cargo = $_GET['cargo'];
    
    // Si es el cargo de administrador (3), seleccionar todos los módulos
    if ($id_cargo == 3) {
        $stmt = $pdo->query("SELECT id_modulo FROM modulos");
        $permisos_actuales = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    } else {
        $stmt = $pdo->prepare("SELECT id_modulo FROM permisos_cargo WHERE id_cargo = ?");
        $stmt->execute([$id_cargo]);
        $permisos_actuales = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    }
}

// Obtener datos del cargo para edición
$cargo_edicion = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM cargo WHERE id_cargo = ?");
    $stmt->execute([$_GET['editar']]);
    $cargo_edicion = $stmt->fetch(PDO::FETCH_ASSOC);
}

$stmt = $pdo->query("SELECT * FROM planta WHERE estado = 'activo' ORDER BY nombres ASC");
$plantas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener la planta actual del usuario desde la base de datos
$planta_actual = $_SESSION['id_planta'] ?? null;

// Si no está en la sesión, obtenerla de la base de datos
if ($planta_actual === null && isset($_SESSION['id_usuario'])) {
    $stmt = $pdo->prepare("SELECT id_planta FROM usuario WHERE id_usuario = ?");
    $stmt->execute([$_SESSION['id_usuario']]);
    $planta_actual = $stmt->fetchColumn();
    
    // Guardar en sesión para futuras consultas
    if ($planta_actual !== false) {
        $_SESSION['id_planta'] = $planta_actual;
    } else {
        // Si por algún motivo no se encuentra, usar un valor seguro (podrías redirigir o manejar el error)
        $planta_actual = 1; // Esto sería el último recurso
    }
}

// Ahora obtener el nombre de la planta
$stmt = $pdo->prepare("SELECT nombres FROM planta WHERE id_planta = ?");
$stmt->execute([$planta_actual]);
$nombre_planta_actual = $stmt->fetchColumn();


logModuleAccess('Permisos');

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PEQUIVEN - Gestión de Permisos</title>
        <link rel="icon" href="favicon.png">

    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --color-primario: #b71513;
            --color-secundario: #6c757d;
            --color-fondo: #f8f9fa;
            --color-texto: #212529;
            --color-borde: #dee2e6;
        }

        body {
            background-color: var(--color-fondo);
            color: var(--color-texto);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 30px;
            margin-top: 30px;
            margin-bottom: 30px;
        }

        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            border-bottom: 2px solid var(--color-primario);
            padding-bottom: 15px;
        }

        h2 {
            color: var(--color-primario);
            font-weight: 600;
            margin: 0;
        }

        .btn-volver {
            background-color: var(--color-secundario);
            border-color: var(--color-secundario);
            transition: all 0.3s ease;
        }

        .btn-volver:hover {
            background-color: #5a6268;
            border-color: #545b62;
            transform: translateY(-2px);
        }

        .btn-primary {
            background-color: var(--color-primario);
            border-color: var(--color-primario);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #9a120f;
            border-color: #8a110e;
            transform: translateY(-2px);
        }

        .btn-danger {
            transition: all 0.3s ease;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
        }

        .form-select {
            border-radius: 5px;
            border: 1px solid var(--color-borde);
            padding: 10px 15px;
        }

        .form-select:focus {
            border-color: var(--color-primario);
            box-shadow: 0 0 0 0.25rem rgba(183, 21, 19, 0.25);
        }

        .modulo-item {
            margin-bottom: 15px;
            padding: 15px;
            border: 1px solid var(--color-borde);
            border-radius: 8px;
            transition: all 0.3s ease;
            background-color: white;
        }

        .modulo-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-left: 4px solid var(--color-primario);
        }

        .form-check-input {
            width: 1.2em;
            height: 1.2em;
            margin-top: 0.2em;
        }

        .form-check-input:checked {
            background-color: var(--color-primario);
            border-color: var(--color-primario);
        }

        .form-check-label {
            margin-left: 8px;
            font-weight: 500;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }

        .table-cargos {
            width: 100%;
            margin-bottom: 20px;
        }

        .table-cargos th {
            background-color: var(--color-primario);
            color: white;
        }

        .table-cargos tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .table-cargos tr:hover {
            background-color: #e9ecef;
        }

        .modal-content {
            border-radius: 10px;
        }

        .modal-header {
            background-color: var(--color-primario);
            color: white;
            border-radius: 10px 10px 0 0;
        }

        .btn-close {
            filter: invert(1);
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            .modulo-item {
                padding: 12px;
            }

            .header-container {
                flex-direction: column;
                align-items: flex-start;
            }

            .btn-volver {
                margin-top: 15px;
                align-self: flex-end;
            }
        }
        .planta-selector {
            background-color: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border-left: 4px solid var(--color-primario);
        }
        
        .planta-selector label {
            font-weight: 600;
            color: var(--color-primario);
        }
        
        .planta-selector .form-select {
            border: 2px solid var(--color-borde);
        }
        
        .planta-selector .btn {
            margin-top: 10px;
        }
        
        .planta-actual {
            font-weight: bold;
            color: var(--color-primario);
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header-container">
            <h2><i class="fas fa-user-shield me-2"></i>Gestión de Permisos</h2>
            <a href="index.php" class="btn btn-volver">
                <i class="fas fa-arrow-left me-2"></i>Volver 
            </a>
        </div>

        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                <?= $_SESSION['mensaje'];
                unset($_SESSION['mensaje']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= $_SESSION['error'];
                unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>



 <!-- Selector de Planta (Nuevo) -->
 <div class="planta-selector">
            <form method="post" class="row g-3 align-items-center">
                <div class="col-md-4">
                    <label for="planta_actual" class="form-label">Planta actual:</label>
                    <input type="text" class="form-control-plaintext planta-actual" id="planta_actual" 
                           value="<?= htmlspecialchars($nombre_planta_actual) ?>" readonly>
                </div>
                <div class="col-md-4">
                    <label for="nueva_planta" class="form-label">Cambiar a planta:</label>
                    <select name="nueva_planta" id="nueva_planta" class="form-select" required>
                        <option value="">-- Seleccione una planta --</option>
                        <?php foreach ($plantas as $planta): ?>
                            <?php if ($planta['id_planta'] != $planta_actual): ?>
                                <option value="<?= $planta['id_planta'] ?>">
                                    <?= htmlspecialchars($planta['nombres']) ?>
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" name="cambiar_planta" class="btn btn-primary">
                        <i class="fas fa-exchange-alt me-2"></i>Cambiar Planta
                    </button>
                </div>
            </form>
        </div>




        <!-- Sección de CRUD para cargos -->
        <div class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3><i class="fas fa-users-cog me-2"></i>Administración de Cargos</h3>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCargo">
                    <i class="fas fa-plus me-2"></i>Nuevo Cargo
                </button>
            </div>

            <table class="table table-cargos table-striped table-hover">
    <thead>
        <tr>
            <th>#</th> <!-- Cambiado de "ID" a "#" -->
            <th>Nombre del Cargo</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($cargos as $index => $cargo): ?>
    <tr>
        <td><?= $index + 1 ?></td>
        <td><?= htmlspecialchars($cargo['nombre_cargo']) ?></td>
        <td>
            <a href="permisos.php?editar=<?= $cargo['id_cargo'] ?>" class="btn btn-sm btn-primary">
                <i class="fas fa-edit"></i>
            </a>
            <?php if ($cargo['id_cargo'] != 3): ?>
                <form method="post" style="display: inline-block;">
                    <input type="hidden" name="id_cargo" value="<?= $cargo['id_cargo'] ?>">
                    <button type="submit" name="eliminar_cargo" class="btn btn-sm btn-danger" 
                        onclick="return confirm('¿Está seguro de eliminar este cargo?')">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                </form>
            <?php endif; ?>
            <a href="permisos.php?cargo=<?= $cargo['id_cargo'] ?>" class="btn btn-sm btn-secondary">
                <i class="fas fa-key"></i> Permisos
            </a>
        </td>
    </tr>
<?php endforeach; ?>


<?php if (isset($_GET['cargo']) && $_GET['cargo'] == 3): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>El cargo Administrador tiene acceso a todos los módulos automáticamente.
    </div>
<?php endif; ?>


    </tbody>
</table>
        </div>

        <!-- Modal para crear/editar cargos -->
        <div class="modal fade" id="modalCargo" tabindex="-1" aria-labelledby="modalCargoLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalCargoLabel">
                            <?= isset($cargo_edicion) ? 'Editar Cargo' : 'Nuevo Cargo' ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="post">
                            <?php if (isset($cargo_edicion)): ?>
                                <input type="hidden" name="id_cargo" value="<?= $cargo_edicion['id_cargo'] ?>">
                            <?php endif; ?>
                            <div class="mb-3">
                                <label for="nombre_cargo" class="form-label">Nombre del Cargo</label>
                                <input type="text" class="form-control" id="nombre_cargo" name="nombre_cargo" 
                                    value="<?= isset($cargo_edicion) ? htmlspecialchars($cargo_edicion['nombre_cargo']) : '' ?>" required>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" name="<?= isset($cargo_edicion) ? 'actualizar_cargo' : 'crear_cargo' ?>" class="btn btn-primary">
                                    <?= isset($cargo_edicion) ? 'Actualizar' : 'Guardar' ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección de permisos -->
        <div class="mb-4">
            <form method="get" class="mb-4">
                <div class="row">
                    <div class="col-md-6">
                        <label for="cargo" class="form-label fw-bold">Seleccione un cargo para gestionar permisos:</label>
                        <select name="cargo" id="cargo" class="form-select" onchange="this.form.submit()">
                            <option value="">-- Seleccione un cargo --</option>
                            <?php foreach ($cargos as $cargo): ?>
                                <option value="<?= $cargo['id_cargo'] ?>" <?= isset($_GET['cargo']) && $_GET['cargo'] == $cargo['id_cargo'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cargo['nombre_cargo']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </form>

            <?php if (isset($_GET['cargo'])): ?>
                <form method="post">
                    <input type="hidden" name="id_cargo" value="<?= $_GET['cargo'] ?>">

                    <h4 class="mb-3"><i class="fas fa-list-check me-2"></i>Módulos disponibles:</h4>
                    <div class="row">
                        <?php foreach ($modulos as $modulo): ?>
                            <div class="col-md-4 col-sm-6 mb-3">
                                <div class="modulo-item">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="modulos[]"
                                            id="modulo-<?= $modulo['id_modulo'] ?>" value="<?= $modulo['id_modulo'] ?>"
                                            <?= in_array($modulo['id_modulo'], $permisos_actuales) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="modulo-<?= $modulo['id_modulo'] ?>">
                                            <i class="fas fa-cube me-2"></i><?= htmlspecialchars($modulo['nombre_modulo']) ?>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="action-buttons">
                        <a href="permisos.php" class="btn btn-volver">
                            <i class="fas fa-arrow-left me-2"></i>Volver
                        </a>
                        <button type="submit" name="guardar_permisos" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Guardar Permisos
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mostrar modal automáticamente si estamos editando
        document.addEventListener('DOMContentLoaded', function () {
            <?php if (isset($cargo_edicion)): ?>
                var modal = new bootstrap.Modal(document.getElementById('modalCargo'));
                modal.show();
            <?php endif; ?>

            // Animación para los módulos
            const moduloItems = document.querySelectorAll('.modulo-item');
            moduloItems.forEach((item, index) => {
                item.style.transitionDelay = `${index * 50}ms`;
            });

            // Seleccionar todos los checkboxes si se hace clic en el título
            document.querySelector('h4').addEventListener('click', function (e) {
                if (e.target === this) {
                    const checkboxes = document.querySelectorAll('.form-check-input');
                    const allChecked = Array.from(checkboxes).every(checkbox => checkbox.checked);

                    checkboxes.forEach(checkbox => {
                        checkbox.checked = !allChecked;
                    });
                }
            });
        });


         // Validación antes de cambiar de planta
         const formCambioPlanta = document.querySelector('form[action="permisos.php"]');
            if (formCambioPlanta) {
                formCambioPlanta.addEventListener('submit', function(e) {
                    const selector = document.getElementById('nueva_planta');
                    if (selector.value === '') {
                        e.preventDefault();
                        alert('Por favor seleccione una planta');
                        selector.focus();
                    }
                });
            }
        


    </script>
</body>

</html>
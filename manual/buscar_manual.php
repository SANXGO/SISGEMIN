<?php
require_once __DIR__ . '/../includes/conexion.php';

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar autenticación
if (!isset($_SESSION['id_usuario'])) {
    die("Usuario no autenticado. Por favor inicie sesión.");
}

$idUsuario = $_SESSION['id_usuario'];

// Obtener la planta del usuario logueado
try {
    $stmtPlantaUsuario = $pdo->prepare("SELECT id_planta FROM usuario WHERE id_usuario = ?");
    $stmtPlantaUsuario->execute([$idUsuario]);
    $usuario = $stmtPlantaUsuario->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        die("Usuario no encontrado en la base de datos.");
    }
    
    $idPlantaUsuario = $usuario['id_planta'];
} catch (PDOException $e) {
    die("Error al obtener planta del usuario: " . $e->getMessage());
}

// Obtener término de búsqueda
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    $sql = "SELECT m.id_manual, m.descripcion, p.nombres as planta_nombre 
            FROM manual m 
            JOIN planta p ON m.id_planta = p.id_planta
            WHERE m.estado = 'activo' AND m.id_planta = ?";
    
    $params = [$idPlantaUsuario];
    
    if (!empty($search)) {
        $sql .= " AND m.descripcion LIKE ?";
        $params[] = "%$search%";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $manuales = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error al obtener manuales: " . $e->getMessage());
}

// Obtener planta para formulario
try {
    $stmt_plantas = $pdo->prepare("SELECT id_planta, nombres FROM planta WHERE id_planta = ?");
    $stmt_plantas->execute([$idPlantaUsuario]);
    $plantas = $stmt_plantas->fetchAll();
} catch (PDOException $e) {
    die("Error al obtener plantas: " . $e->getMessage());
}
?>

<!-- Resultados -->
<?php if (empty($manuales)): ?>
     
<?php else: ?>
    <?php foreach ($manuales as $manual): ?>
    <tr data-id="<?= $manual['id_manual'] ?>" onclick="seleccionarID(<?= $manual['id_manual'] ?>)">
        <td><?= htmlspecialchars($manual['id_manual']) ?></td>
        <td><?= htmlspecialchars($manual['descripcion']) ?></td>
        <td><?= htmlspecialchars($manual['planta_nombre']) ?></td>
        <td>
            <a href="manual/ver_pdf.php?id_manual=<?= $manual['id_manual'] ?>&categoria=<?= urlencode($manual['descripcion']) ?>" 
               class="btn btn-danger">Ver PDFs</a>
        </td>
    </tr>
    <?php endforeach; ?>
<?php endif; ?>
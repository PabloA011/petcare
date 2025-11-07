<?php
session_start();

if (!isset($_SESSION['id_veterinario'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/CONFIG/database.php'; 

$id_veterinario_sesion = $_SESSION['id_veterinario'];
$mensaje = '';
$error = '';

$id_mascota = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_mascota) {
    require_once __DIR__ . '/TEMPLATES/header.php'; 
    echo '<div class="alert alert-danger mt-4" role="alert">Error: ID de mascota no válido o faltante. No se puede gestionar el historial de consultas.</div>';
    echo '<a href="mascotas.php" class="btn btn-secondary mt-3"><i class="bi bi-arrow-left"></i> Volver a Mascotas</a>';
    require_once __DIR__ . '/TEMPLATES/footer.php'; 
    exit;
}

try {
    $sql_mascota = "SELECT m.nombre, c.nombre AS cliente_nombre, c.apellido AS cliente_apellido 
                    FROM mascotas m 
                    JOIN clientes c ON m.id_cliente = c.id_cliente 
                    WHERE m.id_mascota = ?";
    $stmt_mascota = $pdo->prepare($sql_mascota);
    $stmt_mascota->execute([$id_mascota]);
    $mascota = $stmt_mascota->fetch(PDO::FETCH_ASSOC);

    if (!$mascota) {
        require_once __DIR__ . '/TEMPLATES/header.php'; 
        echo '<div class="alert alert-warning mt-4" role="alert">Mascota con ID #' . $id_mascota . ' no encontrada.</div>';
        echo '<a href="mascotas.php" class="btn btn-secondary mt-3"><i class="bi bi-arrow-left"></i> Volver a Mascotas</a>';
        require_once __DIR__ . '/TEMPLATES/footer.php'; 
        exit; 
    }

} catch (PDOException $e) {
    $error = "Error de base de datos al cargar la mascota: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_consulta') {
    $fecha_consulta = trim($_POST['fecha_consulta'] ?? '');
    $motivo = trim($_POST['motivo'] ?? '');
    $diagnostico = trim($_POST['diagnostico'] ?? '');
    $tratamiento = trim($_POST['tratamiento'] ?? '');
    $peso = filter_input(INPUT_POST, 'peso', FILTER_VALIDATE_FLOAT);
    $temperatura = filter_input(INPUT_POST, 'temperatura', FILTER_VALIDATE_FLOAT);
    $observaciones = trim($_POST['observaciones'] ?? '');

    if (empty($fecha_consulta) || empty($motivo)) {
        $error = "La fecha y el motivo de la consulta son obligatorios.";
    } else {
        try {
            $sql_insert = "INSERT INTO consultas (id_mascota, fecha_consulta, motivo, diagnostico, tratamiento, peso, temperatura, observaciones, id_veterinario) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_insert = $pdo->prepare($sql_insert);
            $stmt_insert->execute([
                $id_mascota, 
                $fecha_consulta, 
                $motivo, 
                $diagnostico ?: NULL,
                $tratamiento ?: NULL,
                $peso,
                $temperatura,
                $observaciones ?: NULL,
                $id_veterinario_sesion 
            ]);
            $mensaje = "Consulta registrada exitosamente para {$mascota['nombre']}.";
            header("Location: consultas.php?id={$id_mascota}&msg=" . urlencode($mensaje));
            exit;
        } catch (PDOException $e) {
            $error = "Error al registrar la consulta: " . $e->getMessage();
        }
    }
}


$historial_consultas = [];
try {
    $sql_historial = "
        SELECT c.*, v.nombre AS nombre_veterinario
        FROM consultas c
        LEFT JOIN veterinarios v ON c.id_veterinario = v.id_veterinario
        WHERE c.id_mascota = ? 
        ORDER BY c.fecha_consulta DESC, c.fecha_creacion DESC"; 
    $stmt_historial = $pdo->prepare($sql_historial);
    $stmt_historial->execute([$id_mascota]);
    $historial_consultas = $stmt_historial->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Error al cargar el historial de consultas: " . $e->getMessage();
}

if (isset($_GET['msg'])) {
    $mensaje = urldecode($_GET['msg']);
}
if (isset($_GET['err'])) {
    $error = urldecode($_GET['err']);
}

require_once __DIR__ . '/TEMPLATES/header.php'; 
?>

<div class="row">
    <div class="col-12">
        <h1 class="mb-4">🩺 Historial de Consultas de **<?php echo htmlspecialchars($mascota['nombre']); ?>**</h1>
        <p class="lead">Dueño: <?php echo htmlspecialchars($mascota['cliente_nombre'] . ' ' . $mascota['cliente_apellido']); ?></p>
        
        <a href="mascota_historial.php?id=<?php echo htmlspecialchars($id_mascota); ?>" class="btn btn-secondary mb-4">
            <i class="bi bi-arrow-left"></i> Volver al Historial Clínico
        </a>

        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($mensaje): ?>
            <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm mb-5">
    <div class="card-header bg-primary text-white">
        Registrar Nueva Consulta
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="add_consulta">
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="fecha_consulta" class="form-label">Fecha Consulta (*)</label>
                    <input type="date" class="form-control" id="fecha_consulta" name="fecha_consulta" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="peso" class="form-label">Peso (kg)</label>
                    <input type="number" step="0.01" class="form-control" id="peso" name="peso">
                </div>
                <div class="col-md-3 mb-3">
                    <label for="temperatura" class="form-label">Temperatura (°C)</label>
                    <input type="number" step="0.01" class="form-control" id="temperatura" name="temperatura">
                </div>
            </div>
            
            <div class="mb-3">
                <label for="motivo" class="form-label">Motivo de la Consulta (*)</label>
                <textarea class="form-control" id="motivo" name="motivo" rows="2" required></textarea>
            </div>
            
            <div class="mb-3">
                <label for="diagnostico" class="form-label">Diagnóstico</label>
                <textarea class="form-control" id="diagnostico" name="diagnostico" rows="3"></textarea>
            </div>

            <div class="mb-3">
                <label for="tratamiento" class="form-label">Tratamiento / Medicos Indicados</label>
                <textarea class="form-control" id="tratamiento" name="tratamiento" rows="3"></textarea>
            </div>

            <div class="mb-3">
                <label for="observaciones" class="form-label">Notas Adicionales</label>
                <textarea class="form-control" id="observaciones" name="observaciones" rows="2"></textarea>
            </div>

            <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Registrar Consulta</button>
        </form>
    </div>
</div>

<h2 class="mb-3">Historial de Consultas Médicas</h2>
<div class="accordion" id="historialConsultasAccordion">
    <?php if (!empty($historial_consultas)): ?>
        <?php foreach ($historial_consultas as $index => $consulta): ?>
            <div class="accordion-item shadow-sm mb-2">
                <h2 class="accordion-header" id="heading<?php echo $consulta['id_consulta']; ?>">
                    <button class="accordion-button <?php echo ($index == 0 ? '' : 'collapsed'); ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $consulta['id_consulta']; ?>" aria-expanded="<?php echo ($index == 0 ? 'true' : 'false'); ?>" aria-controls="collapse<?php echo $consulta['id_consulta']; ?>">
                        Fecha: <?php echo date('d/m/Y', strtotime($consulta['fecha_consulta'])); ?> - Motivo: <?php echo htmlspecialchars(substr($consulta['motivo'], 0, 80) . (strlen($consulta['motivo']) > 80 ? '...' : '')); ?>
                    </button>
                </h2>
                <div id="collapse<?php echo $consulta['id_consulta']; ?>" class="accordion-collapse collapse <?php echo ($index == 0 ? 'show' : ''); ?>" aria-labelledby="heading<?php echo $consulta['id_consulta']; ?>" data-bs-parent="#historialConsultasAccordion">
                    <div class="accordion-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Motivo:</strong> <?php echo nl2br(htmlspecialchars($consulta['motivo'])); ?></p>
                                <p><strong>Diagnóstico:</strong> <?php echo nl2br(htmlspecialchars($consulta['diagnostico'] ?? 'N/A')); ?></p>
                                <p><strong>Tratamiento:</strong> <?php echo nl2br(htmlspecialchars($consulta['tratamiento'] ?? 'N/A')); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Peso:</strong> <?php echo htmlspecialchars($consulta['peso'] ? $consulta['peso'] . ' kg' : 'N/A'); ?></p>
                                <p><strong>Temperatura:</strong> <?php echo htmlspecialchars($consulta['temperatura'] ? $consulta['temperatura'] . ' °C' : 'N/A'); ?></p>
                                <p><strong>Veterinario:</strong> <?php echo htmlspecialchars($consulta['nombre_veterinario'] ?? 'N/A'); ?></p>
                                <p><strong>Notas Adicionales:</strong> <?php echo nl2br(htmlspecialchars($consulta['observaciones'] ?? 'N/A')); ?></p>
                                <hr>
                                <p class="text-muted small">Registrado el: <?php echo date('d/m/Y H:i', strtotime($consulta['fecha_creacion'])); ?></p>
                                </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="alert alert-info">No hay consultas médicas registradas para esta mascota.</div>
    <?php endif; ?>
</div>

<?php 
require_once __DIR__ . '/TEMPLATES/footer.php'; 
?>
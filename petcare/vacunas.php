<?php
session_start();

if (!isset($_SESSION['id_veterinario'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/CONFIG/database.php'; 

$mensaje = '';
$error = '';
$id_mascota = null;
$mascota = null;
$historial_vacunas = [];

$clientes_y_mascotas = [];
try {
    $sql_clientes_mascotas = "
        SELECT 
            c.id_cliente, CONCAT(c.nombre, ' ', c.apellido) AS nombre_completo_cliente,
            m.id_mascota, m.nombre AS nombre_mascota, m.especie
        FROM 
            clientes c
        JOIN 
            mascotas m ON c.id_cliente = m.id_cliente
        WHERE 
            c.activo = 1 AND m.activo = 1
        ORDER BY 
            c.apellido, m.nombre
    ";
    $stmt_clientes = $pdo->query($sql_clientes_mascotas);
    $clientes_y_mascotas_raw = $stmt_clientes->fetchAll(PDO::FETCH_ASSOC);

    foreach ($clientes_y_mascotas_raw as $row) {
        $clientes_y_mascotas[] = [
            'id_mascota' => $row['id_mascota'],
            'display_name' => htmlspecialchars($row['nombre_completo_cliente'] . ' (' . $row['nombre_mascota'] . ' - ' . $row['especie'] . ')')
        ];
    }
} catch (PDOException $e) {
    $error = "Error al cargar clientes y mascotas para el buscador: " . $e->getMessage();
}

if (isset($_GET['id'])) {
    $id_mascota = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_mascota_selected'])) {
    $id_mascota = filter_input(INPUT_POST, 'id_mascota_selected', FILTER_VALIDATE_INT);
    if ($id_mascota) {
        header("Location: vacunas.php?id={$id_mascota}");
        exit;
    }
}

if ($id_mascota) {
    try {
        $sql_mascota = "SELECT m.nombre, c.nombre AS cliente_nombre, c.apellido AS cliente_apellido 
                        FROM mascotas m 
                        JOIN clientes c ON m.id_cliente = c.id_cliente 
                        WHERE m.id_mascota = ?";
        $stmt_mascota = $pdo->prepare($sql_mascota);
        $stmt_mascota->execute([$id_mascota]);
        $mascota = $stmt_mascota->fetch(PDO::FETCH_ASSOC);

        if (!$mascota) {
            $error = 'Mascota con ID #' . $id_mascota . ' no encontrada. Por favor, seleccione otra.';
            $id_mascota = null;
        } else {
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_vacuna') {
                $tipo_vacuna = trim($_POST['tipo_vacuna'] ?? '');
                $fecha_aplicacion = trim($_POST['fecha_aplicacion'] ?? '');
                $fecha_vencimiento = trim($_POST['fecha_vencimiento'] ?? ''); 
                $observaciones = trim($_POST['observaciones'] ?? '');

                if (empty($tipo_vacuna) || empty($fecha_aplicacion)) {
                    $error = "El tipo de vacuna y la fecha de aplicación son obligatorios.";
                } else {
                    $sql_insert = "INSERT INTO vacunas (id_mascota, tipo_vacuna, fecha_aplicacion, fecha_vencimiento, observaciones) 
                                   VALUES (?, ?, ?, ?, ?)";
                    $stmt_insert = $pdo->prepare($sql_insert);
                    $stmt_insert->execute([
                        $id_mascota, 
                        $tipo_vacuna, 
                        $fecha_aplicacion, 
                        empty($fecha_vencimiento) ? null : $fecha_vencimiento,
                        $observaciones
                    ]);
                    $mensaje = "Vacuna registrada exitosamente para {$mascota['nombre']}.";
                    header("Location: vacunas.php?id={$id_mascota}&msg=" . urlencode($mensaje));
                    exit;
                }
            }


            if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['vacuna_id'])) {
                $vacuna_id_to_delete = filter_input(INPUT_GET, 'vacuna_id', FILTER_VALIDATE_INT);
                if ($vacuna_id_to_delete) {
                    $sql_delete = "DELETE FROM vacunas WHERE id_vacuna = ? AND id_mascota = ?";
                    $stmt_delete = $pdo->prepare($sql_delete);
                    $stmt_delete->execute([$vacuna_id_to_delete, $id_mascota]);

                    if ($stmt_delete->rowCount() > 0) {
                        $mensaje = "Registro de vacuna eliminado correctamente.";
                    } else {
                        $error = "No se pudo eliminar el registro o no se encontró.";
                    }
                    header("Location: vacunas.php?id={$id_mascota}&msg=" . urlencode($mensaje) . "&err=" . urlencode($error));
                    exit;
                }
            }

            $sql_historial = "SELECT * FROM vacunas WHERE id_mascota = ? ORDER BY fecha_aplicacion DESC";
            $stmt_historial = $pdo->prepare($sql_historial);
            $stmt_historial->execute([$id_mascota]);
            $historial_vacunas = $stmt_historial->fetchAll(PDO::FETCH_ASSOC);
        }

    } catch (PDOException $e) {
        $error = "Error de base de datos: " . $e->getMessage();
    }
}

if (isset($_GET['msg'])) {
    $mensaje = urldecode($_GET['msg']);
}
if (isset($_GET['err'])) {
    $error = urldecode($_GET['err']);
}

require_once __DIR__ . '/TEMPLATES/header.php'; 
?>

<h1 class="mb-4">💉 Gestión de Vacunas</h1>

<?php if ($error): ?>
    <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if ($mensaje): ?>
    <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($mensaje); ?></div>
<?php endif; ?>

<div class="card shadow-sm mb-5">
    <div class="card-header bg-secondary text-white">
        Buscar Mascota para Vacunar
    </div>
    <div class="card-body">
        <form method="POST" action="vacunas.php">
            <div class="row align-items-end">
                <div class="col-md-9 mb-3 mb-md-0">
                    <label for="id_mascota_selected" class="form-label">Seleccione Dueño y Mascota:</label>
                    <select class="form-select" id="id_mascota_selected" name="id_mascota_selected" required>
                        <option value="">-- Buscar por Dueño (Mascota - Especie) --</option>
                        <?php foreach ($clientes_y_mascotas as $item): ?>
                            <option value="<?php echo $item['id_mascota']; ?>"
                                <?php echo ($id_mascota == $item['id_mascota'] ? 'selected' : ''); ?>>
                                <?php echo $item['display_name']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">Cargar Historial</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if ($id_mascota && $mascota): 
    
    ?>

    <div class="row mb-4 border-bottom pb-3">
        <div class="col">
            <h2 class="mb-1">Historial de: <?php echo htmlspecialchars($mascota['nombre']); ?></h2>
            <p class="lead text-muted">Dueño: <?php echo htmlspecialchars($mascota['cliente_nombre'] . ' ' . $mascota['cliente_apellido']); ?></p>
            <a href="mascota_historial.php?id=<?php echo htmlspecialchars($id_mascota); ?>" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver a Historial Clínico
            </a>
        </div>
    </div>


    <div class="card shadow-sm mb-5">
        <div class="card-header bg-success text-white">
            Añadir Nueva Vacuna
        </div>
        <div class="card-body">
            <form method="POST">
                <input type="hidden" name="action" value="add_vacuna">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="tipo_vacuna" class="form-label">Tipo de Vacuna (*)</label>
                        <input type="text" class="form-control" id="tipo_vacuna" name="tipo_vacuna" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="fecha_aplicacion" class="form-label">Fecha de Aplicación (*)</label>
                        <input type="date" class="form-control" id="fecha_aplicacion" name="fecha_aplicacion" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="fecha_vencimiento" class="form-label">Fecha Próxima Dosis / Vencimiento</label>
                        <input type="date" class="form-control" id="fecha_vencimiento" name="fecha_vencimiento">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="observaciones" class="form-label">Observaciones</label>
                    <textarea class="form-control" id="observaciones" name="observaciones" rows="2"></textarea>
                </div>
                <button type="submit" class="btn btn-success"><i class="bi bi-plus-circle-fill"></i> Registrar Vacuna</button>
            </form>
        </div>
    </div>

    <h3 class="mb-3">Historial de Dosis</h3>
    <div class="table-responsive">
        <table class="table table-striped table-hover shadow-sm">
            <thead class="bg-light">
                <tr>
                    <th>Tipo de Vacuna</th>
                    <th>Fecha Aplicada</th>
                    <th>Próxima Dosis</th>
                    <th>Observaciones</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($historial_vacunas)): ?>
                    <?php foreach ($historial_vacunas as $vacuna): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($vacuna['tipo_vacuna']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($vacuna['fecha_aplicacion'])); ?></td>
                            <td>
                                <?php if ($vacuna['fecha_vencimiento']): ?>
                                    <?php 
                                    $vencimiento_ts = strtotime($vacuna['fecha_vencimiento']);
                                    $vencimiento_class = '';

                                    if ($vencimiento_ts <= time()) {
                                        $vencimiento_class = 'text-danger fw-bold';
                                    } elseif ($vencimiento_ts <= strtotime('+30 days')) {
                                        $vencimiento_class = 'text-warning'; 
                                    }
                                    ?>
                                    <span class="<?php echo $vencimiento_class; ?>">
                                        <?php echo date('d/m/Y', $vencimiento_ts); ?>
                                    </span>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars(substr($vacuna['observaciones'], 0, 50) . (strlen($vacuna['observaciones']) > 50 ? '...' : '')); ?></td>
                            <td>
                                <a href="vacunas.php?id=<?php echo htmlspecialchars($id_mascota); ?>&action=delete&vacuna_id=<?php echo htmlspecialchars($vacuna['id_vacuna']); ?>" 
                                   class="btn btn-sm btn-danger" 
                                   onclick="return confirm('¿Estás seguro de que quieres eliminar este registro de vacuna? Esta acción es irreversible.');"
                                   title="Eliminar registro">
                                   <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center">No hay registros de vacunación para esta mascota.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

<?php else: ?>
    <div class="alert alert-info" role="alert">
        Utilice el buscador superior para seleccionar la mascota cuyo historial de vacunas desea gestionar.
    </div>
<?php endif; ?>

<?php 
require_once __DIR__ . '/TEMPLATES/footer.php'; 
?>
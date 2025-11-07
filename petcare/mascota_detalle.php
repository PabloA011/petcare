<?php

session_start();
if (!isset($_SESSION['id_veterinario'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/CONFIG/database.php'; 

$feedback_mensaje = $_SESSION['message'] ?? '';
$feedback_tipo = $_SESSION['message_type'] ?? '';
unset($_SESSION['message']);
unset($_SESSION['message_type']);


$id_mascota = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id_mascota) {
    header('Location: mascotas.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_consulta' && isset($_GET['id_consulta'])) {
    $id_consulta = filter_input(INPUT_GET, 'id_consulta', FILTER_VALIDATE_INT);
    
    if ($id_consulta) {
        $sql = "SELECT diagnostico, tratamiento, notas, fecha FROM consultas WHERE id_consulta = :id_consulta AND id_mascota = :id_mascota";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id_consulta' => $id_consulta, 'id_mascota' => $id_mascota]);
        $consulta_data = $stmt->fetch(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode($consulta_data);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_mascota_data' && isset($_GET['id_mascota'])) {
    $id_mascota_edit = filter_input(INPUT_GET, 'id_mascota', FILTER_VALIDATE_INT);
    
    if ($id_mascota_edit) {
        $sql = "SELECT nombre, especie, raza, fecha_nacimiento, peso, observaciones FROM mascotas WHERE id_mascota = :id_mascota";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id_mascota' => $id_mascota_edit]);
        $mascota_data = $stmt->fetch(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode($mascota_data);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $current_id_mascota = $_POST['id_mascota'] ?? $id_mascota; 
    $id_veterinario = $_SESSION['id_veterinario']; 
    $redirect_hash = '';

    try {
        if ($action === 'create_vacuna') {
            $tipo_vacuna = filter_input(INPUT_POST, 'tipo_vacuna', FILTER_SANITIZE_SPECIAL_CHARS);
            $fecha_aplicacion = filter_input(INPUT_POST, 'fecha_aplicacion', FILTER_SANITIZE_SPECIAL_CHARS);
            $fecha_vencimiento = filter_input(INPUT_POST, 'fecha_vencimiento', FILTER_SANITIZE_SPECIAL_CHARS);
            $lote = filter_input(INPUT_POST, 'lote', FILTER_SANITIZE_SPECIAL_CHARS);
            $veterinario_nombre = filter_input(INPUT_POST, 'veterinario', FILTER_SANITIZE_SPECIAL_CHARS);

            $sql = "INSERT INTO vacunas (id_mascota, tipo_vacuna, fecha_aplicacion, fecha_vencimiento, lote, veterinario) 
                    VALUES (:id_mascota, :tipo_vacuna, :fecha_aplicacion, :fecha_vencimiento, :lote, :veterinario)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'id_mascota' => $current_id_mascota,
                'tipo_vacuna' => $tipo_vacuna,
                'fecha_aplicacion' => $fecha_aplicacion,
                'fecha_vencimiento' => $fecha_vencimiento,
                'lote' => $lote ?: null,
                'veterinario' => $veterinario_nombre
            ]);
            $feedback_mensaje = "Vacuna registrada exitosamente.";
            $feedback_tipo = "success";
            $redirect_hash = '#vacunas';

        } elseif ($action === 'delete_vacuna' && isset($_POST['id_vacuna'])) {
            $sql = "DELETE FROM vacunas WHERE id_vacuna = :id_vacuna AND id_mascota = :id_mascota";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id_vacuna' => $_POST['id_vacuna'], 'id_mascota' => $current_id_mascota]);

            $feedback_mensaje = "Vacuna eliminada exitosamente.";
            $feedback_tipo = "warning";
            $redirect_hash = '#vacunas';

        } elseif ($action === 'crear_consulta') {
            $fecha = date('Y-m-d H:i:s'); 
            $diagnostico = filter_input(INPUT_POST, 'diagnostico', FILTER_SANITIZE_SPECIAL_CHARS);
            $tratamiento = filter_input(INPUT_POST, 'tratamiento', FILTER_SANITIZE_SPECIAL_CHARS);
            $notas = filter_input(INPUT_POST, 'notas', FILTER_SANITIZE_SPECIAL_CHARS);
            
            if (!$diagnostico || !$tratamiento) {
                 throw new Exception("Diagnóstico y Tratamiento son campos obligatorios.");
            }

            $sql_insert = "INSERT INTO consultas (id_mascota, id_veterinario, fecha, diagnostico, tratamiento, notas)
                           VALUES (:id_mascota, :id_veterinario, :fecha, :diagnostico, :tratamiento, :notas)";
            
            $stmt_insert = $pdo->prepare($sql_insert);
            $stmt_insert->execute([
                'id_mascota' => $current_id_mascota,
                'id_veterinario' => $id_veterinario,
                'fecha' => $fecha,
                'diagnostico' => $diagnostico,
                'tratamiento' => $tratamiento,
                'notas' => $notas
            ]);
            $feedback_mensaje = "Consulta médica registrada exitosamente.";
            $feedback_tipo = "success";
            $redirect_hash = '#historial';

        } elseif ($action === 'editar_consulta' && isset($_POST['id_consulta'])) {
            $id_consulta = filter_input(INPUT_POST, 'id_consulta', FILTER_VALIDATE_INT);
            $diagnostico = filter_input(INPUT_POST, 'diagnostico', FILTER_SANITIZE_SPECIAL_CHARS);
            $tratamiento = filter_input(INPUT_POST, 'tratamiento', FILTER_SANITIZE_SPECIAL_CHARS);
            $notas = filter_input(INPUT_POST, 'notas', FILTER_SANITIZE_SPECIAL_CHARS);
            
            if (!$diagnostico || !$tratamiento) {
                 throw new Exception("Diagnóstico y Tratamiento son campos obligatorios.");
            }

            $sql_update = "UPDATE consultas SET 
                           diagnostico = :diagnostico, 
                           tratamiento = :tratamiento, 
                           notas = :notas 
                           WHERE id_consulta = :id_consulta AND id_mascota = :id_mascota";

            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([
                'diagnostico' => $diagnostico,
                'tratamiento' => $tratamiento,
                'notas' => $notas,
                'id_consulta' => $id_consulta,
                'id_mascota' => $current_id_mascota
            ]);
            
            $feedback_mensaje = "Consulta médica actualizada exitosamente.";
            $feedback_tipo = "warning";
            $redirect_hash = '#historial';
        
        } elseif ($action === 'delete_consulta' && isset($_POST['id_consulta'])) {
            $id_consulta = filter_input(INPUT_POST, 'id_consulta', FILTER_VALIDATE_INT);

            $sql = "DELETE FROM consultas WHERE id_consulta = :id_consulta AND id_mascota = :id_mascota";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id_consulta' => $id_consulta, 'id_mascota' => $current_id_mascota]);

            $feedback_mensaje = "Consulta eliminada exitosamente.";
            $feedback_tipo = "danger";
            $redirect_hash = '#historial';
 
        } elseif ($action === 'update_mascota' && isset($_POST['id_mascota'])) {
            $id_mascota_update = filter_input(INPUT_POST, 'id_mascota', FILTER_VALIDATE_INT);
            $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_SPECIAL_CHARS);
            $especie = filter_input(INPUT_POST, 'especie', FILTER_SANITIZE_SPECIAL_CHARS);
            $raza = filter_input(INPUT_POST, 'raza', FILTER_SANITIZE_SPECIAL_CHARS);
            $fecha_nacimiento = filter_input(INPUT_POST, 'fecha_nacimiento', FILTER_SANITIZE_SPECIAL_CHARS);
            $peso = filter_input(INPUT_POST, 'peso', FILTER_VALIDATE_FLOAT); 
            $observaciones = filter_input(INPUT_POST, 'observaciones', FILTER_SANITIZE_SPECIAL_CHARS);

            if (!$nombre || !$especie) {
                 throw new Exception("Nombre y Especie son campos obligatorios.");
            }
            
            $sql_update = "UPDATE mascotas SET 
                           nombre = :nombre, 
                           especie = :especie, 
                           raza = :raza, 
                           fecha_nacimiento = :fecha_nacimiento, 
                           peso = :peso, 
                           observaciones = :observaciones 
                           WHERE id_mascota = :id_mascota";

            $stmt_update = $pdo->prepare($sql_update);
            $stmt_update->execute([
                'nombre' => $nombre,
                'especie' => $especie,
                'raza' => $raza,
                'fecha_nacimiento' => $fecha_nacimiento,
                'peso' => $peso,
                'observaciones' => $observaciones,
                'id_mascota' => $id_mascota_update
            ]);
            
            $feedback_mensaje = "Datos básicos de la mascota actualizados exitosamente.";
            $feedback_tipo = "warning";
            $redirect_hash = '#detalles'; 
        }

        $_SESSION['message'] = $feedback_mensaje;
        $_SESSION['message_type'] = $feedback_tipo;
        header('Location: mascota_detalle.php?id=' . $current_id_mascota . $redirect_hash); 
        exit;

    } catch (Exception $e) { 
        $_SESSION['message'] = "Error: " . $e->getMessage();
        $_SESSION['message_type'] = "danger";
        header('Location: mascota_detalle.php?id=' . $current_id_mascota);
        exit;
    }
}

$mascota = null;
$edad_texto = 'N/A';
try {
    $sql_mascota = "SELECT m.*, c.nombre AS nombre_cliente, c.telefono, c.email 
                    FROM mascotas m 
                    LEFT JOIN clientes c ON m.id_cliente = c.id_cliente 
                    WHERE m.id_mascota = :id AND m.activo = 1"; 
    $stmt = $pdo->prepare($sql_mascota);
    $stmt->execute(['id' => $id_mascota]);
    $mascota = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$mascota) {
        header('Location: mascotas.php');
        exit;
    }

    $fecha_nacimiento = $mascota['fecha_nacimiento'];
    if ($fecha_nacimiento && $fecha_nacimiento !== '0000-00-00') {
        $fecha_nacimiento_obj = new DateTime($fecha_nacimiento);
        $hoy = new DateTime();
        $intervalo = $hoy->diff($fecha_nacimiento_obj);
        $edad_texto = $intervalo->y . ' años, ' . $intervalo->m . ' meses';
    }


} catch (PDOException $e) {
    die("Error de base de datos al cargar la mascota: " . $e->getMessage());
}

$historial_medico = [];
$vacunas = [];
try {
    $sql_consultas = "SELECT id_consulta, fecha, diagnostico, tratamiento, notas FROM consultas WHERE id_mascota = :id_mascota ORDER BY fecha DESC";
    $stmt_consultas = $pdo->prepare($sql_consultas);
    $stmt_consultas->execute(['id_mascota' => $id_mascota]);
    $historial_medico = $stmt_consultas->fetchAll(PDO::FETCH_ASSOC);

    $sql_vacunas = "SELECT * FROM vacunas WHERE id_mascota = :id_mascota ORDER BY fecha_aplicacion DESC";
    $stmt_vacunas = $pdo->prepare($sql_vacunas);
    $stmt_vacunas->execute(['id_mascota' => $id_mascota]);
    $vacunas = $stmt_vacunas->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $feedback_mensaje = "Error al cargar el historial médico: " . $e->getMessage();
    $feedback_tipo = "danger";
}


require_once __DIR__ . '/TEMPLATES/header.php'; 
?>

<h1 class="mb-4"><i class="bi bi-file-earmark-medical-fill"></i> Historial Clínico de **<?php echo htmlspecialchars($mascota['nombre']); ?>**</h1>

<?php
if ($feedback_mensaje): 
?>
    <div class="alert alert-<?php echo $feedback_tipo; ?> alert-dismissible fade show" role="alert">
        <?php echo $feedback_mensaje; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php 
endif; 
?>

<a href="mascotas.php" class="btn btn-secondary mb-4"><i class="bi bi-arrow-left"></i> Volver al Listado</a>

<ul class="nav nav-tabs mb-4" id="historialTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="detalles-tab" data-bs-toggle="tab" data-bs-target="#detalles" type="button" role="tab" aria-controls="detalles" aria-selected="true"><i class="bi bi-info-circle-fill"></i> Detalles Generales</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="vacunas-tab" data-bs-toggle="tab" data-bs-target="#vacunas" type="button" role="tab" aria-controls="vacunas" aria-selected="false"><i class="bi bi-virus"></i> Vacunas</button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="historial-tab" data-bs-toggle="tab" data-bs-target="#historial" type="button" role="tab" aria-controls="historial" aria-selected="false"><i class="bi bi-journal-medical"></i> Historial Médico</button>
    </li>
</ul>

<div class="tab-content" id="historialTabsContent">
    
    <div class="tab-pane fade show active" id="detalles" role="tabpanel" aria-labelledby="detalles-tab">
        <div class="card shadow mb-4">
            <div class="card-header bg-info text-white">
                Información Básica
            </div>
            <div class="card-body">
                
                <div class="row mb-3">
                    <h5 class="mb-3"><i class="bi bi-box-seam"></i> Datos de la Mascota</h5>
                    <div class="col-md-6">
                        <p><strong>Nombre:</strong> <?php echo htmlspecialchars($mascota['nombre']); ?></p>
                        <p><strong>Especie:</strong> <?php echo htmlspecialchars($mascota['especie']); ?></p>
                        <p><strong>Raza:</strong> <?php echo htmlspecialchars($mascota['raza'] ?? 'N/A'); ?></p>
                        <p><strong>Peso Actual:</strong> <?php echo htmlspecialchars($mascota['peso'] ?? 'N/A'); ?> Kg</p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>F. Nacimiento:</strong> <?php echo htmlspecialchars($fecha_nacimiento ?? 'N/A'); ?></p>
                        <p><strong>Edad Estimada:</strong> <?php echo $edad_texto; ?></p>
                        
                        <p><strong>Fecha de Registro:</strong> 
                            <?php 
                            $registro = $mascota['fecha_registro'] ?? null;
                            if ($registro && $registro !== '0000-00-00 00:00:00') {
                                echo date('d/m/Y', strtotime($registro)); 
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </p>
                        
                    </div>
                </div>
                
                <hr>
                
                <div class="row mb-3">
                    <h5 class="mb-3"><i class="bi bi-person-fill"></i> Información del Dueño</h5>
                    <div class="col-md-12">
                        <p><strong>Dueño:</strong> **<?php echo htmlspecialchars($mascota['nombre_cliente'] ?? 'N/A'); ?>**</p>
                        <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($mascota['telefono'] ?? 'N/A'); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($mascota['email'] ?? 'N/A'); ?></p>
                    </div>
                    </div>
                
                <hr>
                
                <p><strong>Observaciones Generales:</strong></p>
                <p><?php echo nl2br(htmlspecialchars($mascota['observaciones'] ?? 'Sin observaciones iniciales registradas.')); ?></p>
            </div>
            <div class="card-footer text-end">
                <button type="button" class="btn btn-warning btn-sm btn-editar-mascota" 
                        data-bs-toggle="modal" data-bs-target="#editarMascotaModal" 
                        data-id="<?php echo $id_mascota; ?>">
                    <i class="bi bi-pencil"></i> Editar Datos Básicos
                </button>
            </div>
        </div>
    </div>
    
    <div class="tab-pane fade" id="vacunas" role="tabpanel" aria-labelledby="vacunas-tab">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="bi bi-shield-lock-fill me-2"></i> Historial de Vacunación</h4>
                <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalNuevaVacuna">
                    <i class="bi bi-plus-circle-fill me-1"></i> Registrar Vacuna
                </button>
            </div>
            <div class="card-body">
                <?php
                if (empty($vacunas)) {
                    echo "<div class='alert alert-secondary mb-0'>Aún no hay vacunas registradas para " . htmlspecialchars($mascota['nombre']) . ".</div>";
                } else {
                ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Vacuna</th>
                                    <th>Lote</th>
                                    <th>Aplicación</th>
                                    <th>Vencimiento</th>
                                    <th>Veterinario</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($vacunas as $vacuna): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($vacuna['tipo_vacuna']); ?></td>
                                    <td><?php echo htmlspecialchars($vacuna['lote'] ?: 'N/A'); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($vacuna['fecha_aplicacion'])); ?></td>
                                    <td class="<?php echo (strtotime($vacuna['fecha_vencimiento']) < time()) ? 'text-danger fw-bold' : ''; ?>">
                                        <?php echo date('d/m/Y', strtotime($vacuna['fecha_vencimiento'])); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($vacuna['veterinario']); ?></td>
                                    <td>
                                        <form action="mascota_detalle.php?id=<?php echo $id_mascota; ?>" method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="delete_vacuna">
                                            <input type="hidden" name="id_vacuna" value="<?php echo $vacuna['id_vacuna']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Está seguro de eliminar esta vacuna? Esta acción es irreversible.');">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
    
    <div class="tab-pane fade" id="historial" role="tabpanel" aria-labelledby="historial-tab">
        <div class="d-flex justify-content-end mb-3">
            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalNuevaConsulta">
                <i class="bi bi-plus-circle-fill"></i> Registrar Nueva Consulta
            </button>
        </div>
        
        <?php if (empty($historial_medico)): ?>
            <div class="alert alert-warning">
                Aún no hay consultas registradas para **<?php echo htmlspecialchars($mascota['nombre']); ?>**.
            </div>
        <?php else: ?>
            <?php foreach ($historial_medico as $consulta): ?>
            <div class="card shadow mb-3">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    Consulta del: <strong><?php echo date('d/m/Y H:i', strtotime($consulta['fecha'])); ?></strong>
                    
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-warning btn-editar-consulta" 
                                data-bs-toggle="modal" data-bs-target="#modalEditarConsulta" 
                                data-id="<?php echo $consulta['id_consulta']; ?>">
                            <i class="bi bi-pencil"></i> Editar
                        </button>
                        
                        <form action="mascota_detalle.php?id=<?php echo $id_mascota; ?>" method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="delete_consulta">
                            <input type="hidden" name="id_consulta" value="<?php echo $consulta['id_consulta']; ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" 
                                    onclick="return confirm('¿Está seguro de eliminar este registro de consulta? Esta acción es irreversible.');">
                                <i class="bi bi-trash"></i> Eliminar
                            </button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <h6>Diagnóstico:</h6>
                    <p class="card-text"><?php echo nl2br(htmlspecialchars($consulta['diagnostico'])); ?></p>
                    <h6>Tratamiento:</h6>
                    <p class="card-text text-success"><strong><?php echo nl2br(htmlspecialchars($consulta['tratamiento'])); ?></strong></p>
                    <?php if (!empty($consulta['notas'])): ?>
                    <h6>Notas Adicionales:</h6>
                    <p class="card-text fst-italic small"><?php echo nl2br(htmlspecialchars($consulta['notas'])); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
</div>

<div class="modal fade" id="editarMascotaModal" tabindex="-1" aria-labelledby="editarMascotaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="mascota_detalle.php?id=<?php echo $id_mascota; ?>" method="POST">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="editarMascotaModalLabel"><i class="bi bi-pencil-square"></i> Editar Datos Básicos</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_mascota">
                    <input type="hidden" name="id_mascota" id="edit_mascota_id" value="<?php echo $id_mascota; ?>"> 
                    
                    <div class="mb-3">
                        <label for="edit_nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_especie" class="form-label">Especie</label>
                        <input type="text" class="form-control" id="edit_especie" name="especie" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_raza" class="form-label">Raza</label>
                        <input type="text" class="form-control" id="edit_raza" name="raza">
                    </div>
                    <div class="mb-3">
                        <label for="edit_fecha_nacimiento" class="form-label">F. Nacimiento</label>
                        <input type="date" class="form-control" id="edit_fecha_nacimiento" name="fecha_nacimiento">
                    </div>
                    <div class="mb-3">
                        <label for="edit_peso" class="form-label">Peso Actual (Kg)</label>
                        <input type="number" step="0.01" class="form-control" id="edit_peso" name="peso">
                    </div>
                    <div class="mb-3">
                        <label for="edit_observaciones" class="form-label">Observaciones Generales</label>
                        <textarea class="form-control" id="edit_observaciones" name="observaciones" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-arrow-repeat"></i> Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNuevaVacuna" tabindex="-1" aria-labelledby="modalNuevaVacunaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="mascota_detalle.php?id=<?php echo $id_mascota; ?>" method="POST">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="modalNuevaVacunaLabel">Registrar Nueva Vacuna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_vacuna">
                    <input type="hidden" name="id_mascota" value="<?php echo $id_mascota; ?>">
                    
                    <div class="mb-3">
                        <label for="tipo_vacuna" class="form-label">Tipo de Vacuna</label>
                        <input type="text" class="form-control" id="tipo_vacuna" name="tipo_vacuna" required>
                    </div>
                    <div class="mb-3">
                        <label for="fecha_aplicacion" class="form-label">Fecha de Aplicación</label>
                        <input type="date" class="form-control" id="fecha_aplicacion" name="fecha_aplicacion" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="fecha_vencimiento" class="form-label">Fecha de Vencimiento</label>
                        <input type="date" class="form-control" id="fecha_vencimiento" name="fecha_vencimiento" required>
                    </div>
                    <div class="mb-3">
                        <label for="lote" class="form-label">Número de Lote (Opcional)</label>
                        <input type="text" class="form-control" id="lote" name="lote">
                    </div>
                    <input type="hidden" name="veterinario" value="<?php echo htmlspecialchars($_SESSION['nombre_veterinario'] ?? 'Veterinario Desconocido'); ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-info">Guardar Registro</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNuevaConsulta" tabindex="-1" aria-labelledby="modalNuevaConsultaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="mascota_detalle.php?id=<?php echo $id_mascota; ?>" method="POST">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalNuevaConsultaLabel">Registrar Nueva Consulta Médica</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="crear_consulta">
                    <input type="hidden" name="id_mascota" value="<?php echo $id_mascota; ?>">
                    
                    <div class="mb-3">
                        <label for="diagnostico" class="form-label">Diagnóstico <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="diagnostico" name="diagnostico" rows="4" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="tratamiento" class="form-label">Tratamiento <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="tratamiento" name="tratamiento" rows="4" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="notas" class="form-label">Notas Adicionales / Indicaciones</label>
                        <textarea class="form-control" id="notas" name="notas" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Guardar Consulta</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEditarConsulta" tabindex="-1" aria-labelledby="modalEditarConsultaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="mascota_detalle.php?id=<?php echo $id_mascota; ?>" method="POST" id="form-editar-consulta">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="modalEditarConsultaLabel">Editar Consulta Médica</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="editar_consulta">
                    <input type="hidden" name="id_mascota" value="<?php echo $id_mascota; ?>">
                    <input type="hidden" name="id_consulta" id="editar-id-consulta"> 
                    
                    <div class="mb-3">
                        <label for="editar-diagnostico" class="form-label">Diagnóstico <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="editar-diagnostico" name="diagnostico" rows="4" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editar-tratamiento" class="form-label">Tratamiento <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="editar-tratamiento" name="tratamiento" rows="4" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="editar-notas" class="form-label">Notas Adicionales / Indicaciones</label>
                        <textarea class="form-control" id="editar-notas" name="notas" rows="3"></textarea>
                    </div>
                    <p class="text-muted small mt-2">Registrado el: <span id="fecha-registro-consulta"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-arrow-repeat"></i> Actualizar Consulta</button>
                </div>
            </form>
        </div>
    </div>
</div>


<?php 
require_once __DIR__ . '/TEMPLATES/footer.php'; 
?>

<script>
document.addEventListener('DOMContentLoaded', function() {

    var editarConsultaModal = document.getElementById('modalEditarConsulta');
    if (editarConsultaModal) {
        editarConsultaModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var id_consulta = button.getAttribute('data-id');
            
            fetch('mascota_detalle.php?action=get_consulta&id_consulta=' + id_consulta + '&id=' + <?php echo $id_mascota; ?>)
                .then(response => response.json())
                .then(data => {
                    if(data) {
                        document.getElementById('editar-id-consulta').value = id_consulta;
                        document.getElementById('editar-diagnostico').value = data.diagnostico;
                        document.getElementById('editar-tratamiento').value = data.tratamiento;
                        document.getElementById('editar-notas').value = data.notas;
                        
                        var fecha = new Date(data.fecha);
                        document.getElementById('fecha-registro-consulta').textContent = fecha.toLocaleDateString() + ' ' + fecha.toLocaleTimeString();
                    }
                })
                .catch(error => console.error('Error al cargar datos de consulta:', error));
        });
    }

    var editarMascotaModal = document.getElementById('editarMascotaModal');
    if (editarMascotaModal) {
        editarMascotaModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var id_mascota_edit = button.getAttribute('data-id');

            fetch('mascota_detalle.php?action=get_mascota_data&id_mascota=' + id_mascota_edit)
                .then(response => response.json())
                .then(data => {
                    if(data) {
                        document.getElementById('edit_mascota_id').value = id_mascota_edit; 
                        document.getElementById('edit_nombre').value = data.nombre || '';
                        document.getElementById('edit_especie').value = data.especie || '';
                        document.getElementById('edit_raza').value = data.raza || '';
                        document.getElementById('edit_fecha_nacimiento').value = data.fecha_nacimiento || '';
                        document.getElementById('edit_peso').value = data.peso || '';
                        document.getElementById('edit_observaciones').value = data.observaciones || '';
                    }
                })
                .catch(error => console.error('Error al cargar datos de mascota:', error));
        });
    }

    var hash = window.location.hash;
    if (hash) {
        var triggerEl = document.querySelector('button[data-bs-target="' + hash + '"]');
        if (triggerEl) {
            var tab = new bootstrap.Tab(triggerEl);
            tab.show();
        }
    }
});
</script>
<?php
session_start();
if (!isset($_SESSION['id_veterinario'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/CONFIG/database.php'; 

$feedback_mensaje = '';
$feedback_tipo = '';
$id_cliente_filtrado = null; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    
    if ($_POST['accion'] === 'crear_mascota') {
        
        $id_cliente = filter_input(INPUT_POST, 'id_cliente', FILTER_VALIDATE_INT);
        $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_SPECIAL_CHARS);
        $especie = filter_input(INPUT_POST, 'especie', FILTER_SANITIZE_SPECIAL_CHARS);
        $raza = filter_input(INPUT_POST, 'raza', FILTER_SANITIZE_SPECIAL_CHARS);
        $fecha_nacimiento = filter_input(INPUT_POST, 'fecha_nacimiento', FILTER_SANITIZE_SPECIAL_CHARS);
        $observaciones = filter_input(INPUT_POST, 'observaciones', FILTER_SANITIZE_SPECIAL_CHARS);

        if (!$id_cliente || !$nombre || !$especie) {
            $feedback_mensaje = "Error: El Dueño, Nombre y Especie son campos obligatorios.";
            $feedback_tipo = 'danger';
        } else {
            $sql = "INSERT INTO mascotas (id_cliente, nombre, especie, raza, fecha_nacimiento, observaciones, activo) 
                    VALUES (:id_cliente, :nombre, :especie, :raza, :fecha_nacimiento, :observaciones, 1)";
            $stmt = $pdo->prepare($sql);
            
            try {
                $stmt->execute([
                    'id_cliente' => $id_cliente,
                    'nombre' => $nombre,
                    'especie' => $especie,
                    'raza' => $raza ?: NULL,
                    'fecha_nacimiento' => $fecha_nacimiento ?: NULL,
                    'observaciones' => $observaciones ?: NULL
                ]);
                header('Location: mascotas.php?success=creado');
                exit;
            } catch (PDOException $e) {
                $feedback_mensaje = "Error al registrar la mascota: " . $e->getMessage();
                $feedback_tipo = 'danger';
            }
        }
    } 

    elseif ($_POST['accion'] === 'desactivar_mascota' && isset($_POST['id_mascota'])) {
        
        $id_mascota = filter_input(INPUT_POST, 'id_mascota', FILTER_VALIDATE_INT);
        if ($id_mascota) {
            $redirect = isset($_POST['redirect_id_cliente']) ? '?id_cliente=' . $_POST['redirect_id_cliente'] . '&success=eliminado' : '?success=eliminado';

            $sql = "DELETE FROM mascotas WHERE id_mascota = :id";
            $stmt = $pdo->prepare($sql);
            
            try {
                $stmt->execute(['id' => $id_mascota]);
                header('Location: mascotas.php' . $redirect);
                exit;
            } catch (PDOException $e) {
                $feedback_mensaje = "Error al ELIMINAR la mascota: Podría tener registros pendientes que no se eliminan en cascada. Detalle: " . $e->getMessage();
                $feedback_tipo = 'danger';
            }
        } else {
            $feedback_mensaje = "Error: ID de mascota no válido.";
            $feedback_tipo = 'danger';
        }
    }
 
    elseif ($_POST['accion'] === 'editar_mascota') {
        $id_mascota = filter_input(INPUT_POST, 'id_mascota_edit', FILTER_VALIDATE_INT);
        $id_cliente = filter_input(INPUT_POST, 'id_cliente_edit', FILTER_VALIDATE_INT);
        $nombre = filter_input(INPUT_POST, 'nombre_edit', FILTER_SANITIZE_SPECIAL_CHARS);
        $especie = filter_input(INPUT_POST, 'especie_edit', FILTER_SANITIZE_SPECIAL_CHARS);
        $raza = filter_input(INPUT_POST, 'raza_edit', FILTER_SANITIZE_SPECIAL_CHARS);
        $fecha_nacimiento = filter_input(INPUT_POST, 'fecha_nacimiento_edit', FILTER_SANITIZE_SPECIAL_CHARS);
        $observaciones = filter_input(INPUT_POST, 'observaciones_edit', FILTER_SANITIZE_SPECIAL_CHARS);

        if (!$id_mascota || !$id_cliente || !$nombre || !$especie) {
            $feedback_mensaje = "Error de edición: Faltan campos obligatorios o ID inválido.";
            $feedback_tipo = 'danger';
        } else {
            $sql = "UPDATE mascotas SET 
                        id_cliente = :id_cliente, 
                        nombre = :nombre, 
                        especie = :especie, 
                        raza = :raza, 
                        fecha_nacimiento = :fecha_nacimiento, 
                        observaciones = :observaciones 
                    WHERE id_mascota = :id_mascota";
            $stmt = $pdo->prepare($sql);
            
            try {
                $stmt->execute([
                    'id_cliente' => $id_cliente,
                    'nombre' => $nombre,
                    'especie' => $especie,
                    'raza' => $raza ?: NULL,
                    'fecha_nacimiento' => $fecha_nacimiento ?: NULL,
                    'observaciones' => $observaciones ?: NULL,
                    'id_mascota' => $id_mascota
                ]);
                header('Location: mascotas.php?success=editado');
                exit;
            } catch (PDOException $e) {
                $feedback_mensaje = "Error al actualizar la mascota: " . $e->getMessage();
                $feedback_tipo = 'danger';
            }
        }
    }
} 

if (isset($_GET['success'])) {
    if ($_GET['success'] === 'creado') {
        $feedback_mensaje = "🐾 Mascota registrada exitosamente.";
        $feedback_tipo = 'success';
    } elseif ($_GET['success'] === 'eliminado') {
        $feedback_mensaje = "🗑️ Mascota eliminada permanentemente (y sus registros asociados).";
        $feedback_tipo = 'danger';
    } elseif ($_GET['success'] === 'editado') { 
        $feedback_mensaje = "✅ Datos de la mascota actualizados correctamente.";
        $feedback_tipo = 'success';
    }
}

$sql_clientes_dropdown = "SELECT id_cliente, CONCAT(nombre, ' ', apellido) AS nombre_completo 
                          FROM clientes 
                          WHERE activo = 1 
                          ORDER BY nombre_completo ASC";
$stmt_clientes = $pdo->query($sql_clientes_dropdown);
$clientes_dropdown = $stmt_clientes->fetchAll(PDO::FETCH_ASSOC);

$sql_mascotas = "
    SELECT 
        m.id_mascota, m.nombre, m.especie, m.raza, m.fecha_nacimiento, m.observaciones, 
        c.id_cliente, CONCAT(c.nombre, ' ', c.apellido) AS nombre_completo_cliente
    FROM 
        mascotas m
    JOIN 
        clientes c ON m.id_cliente = c.id_cliente
    WHERE 
        m.activo = 1
";

$params = [];

if (isset($_GET['id_cliente'])) {
    $id_cliente_filtrado = filter_input(INPUT_GET, 'id_cliente', FILTER_VALIDATE_INT);
    if ($id_cliente_filtrado) {
        $sql_mascotas .= " AND m.id_cliente = :id_cliente";
        $params['id_cliente'] = $id_cliente_filtrado;
    }
}

$sql_mascotas .= " ORDER BY c.apellido, c.nombre, m.nombre ASC";

$stmt = $pdo->prepare($sql_mascotas);
$stmt->execute($params);
$mascotas = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/TEMPLATES/header.php'; 
?>

<h1 class="mb-4">🐾 Gestión de Mascotas</h1>

<div class="d-flex justify-content-between align-items-center mb-4">
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#crearMascotaModal">
        <i class="bi bi-plus-circle-fill"></i> Agregar Nueva Mascota
    </button>
    
    <?php if ($id_cliente_filtrado): ?>
        <a href="mascotas.php" class="btn btn-outline-secondary">
            <i class="bi bi-x-circle"></i> Mostrar todas las Mascotas
        </a>
    <?php endif; ?>
</div>


<?php if ($feedback_mensaje): ?>
    <div class="alert alert-<?php echo $feedback_tipo; ?> alert-dismissible fade show" role="alert">
        <?php echo $feedback_mensaje; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>


<div class="card shadow">
    <div class="card-body">
        <h5 class="card-title">
            <?php 
                if ($id_cliente_filtrado && !empty($mascotas)) {
                    echo "Mascotas de: " . htmlspecialchars($mascotas[0]['nombre_completo_cliente']);
                } else if ($id_cliente_filtrado && empty($mascotas)) {

                    $sql_nombre_cliente = "SELECT CONCAT(nombre, ' ', apellido) AS nombre_completo_cliente FROM clientes WHERE id_cliente = :id";
                    $stmt_nombre = $pdo->prepare($sql_nombre_cliente);
                    $stmt_nombre->execute(['id' => $id_cliente_filtrado]);
                    $nombre_cliente = $stmt_nombre->fetchColumn();
                    echo "Mascotas de: " . htmlspecialchars($nombre_cliente ?? 'Cliente Desconocido');
                }
                else {
                    echo "Listado General de Mascotas Activas";
                }
            ?>
        </h5>
        
        <?php if (empty($mascotas)): ?>
            <div class="alert alert-info" role="alert">
                <?php if ($id_cliente_filtrado): ?>
                    El cliente no tiene mascotas registradas.
                <?php else: ?>
                    Aún no hay mascotas registradas.
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Dueño</th>
                            <th>Especie</th>
                            <th>Raza</th>
                            <th>Edad/Nac.</th>
                            <th>Observaciones</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mascotas as $mascota): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($mascota['nombre']); ?></td>
                            <td><?php echo htmlspecialchars($mascota['nombre_completo_cliente']); ?></td>
                            <td><?php echo htmlspecialchars($mascota['especie']); ?></td>
                            <td><?php echo htmlspecialchars($mascota['raza'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($mascota['fecha_nacimiento'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars(substr($mascota['observaciones'], 0, 50) . (strlen($mascota['observaciones']) > 50 ? '...' : '') ?? 'N/A'); ?></td>
                            <td>
                                <a href="mascota_historial.php?id=<?php echo $mascota['id_mascota']; ?>" class="btn btn-sm btn-info" title="Ver Historial Clínico">
                                    <i class="bi bi-journal-medical"></i>
                                </a>

                                <button type="button" 
                                        class="btn btn-sm btn-warning" 
                                        title="Editar Mascota" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editarMascotaModal"
                                        data-id="<?php echo $mascota['id_mascota']; ?>"
                                        data-cliente-id="<?php echo $mascota['id_cliente']; ?>"
                                        data-nombre="<?php echo htmlspecialchars($mascota['nombre']); ?>"
                                        data-especie="<?php echo htmlspecialchars($mascota['especie']); ?>"
                                        data-raza="<?php echo htmlspecialchars($mascota['raza'] ?? ''); ?>"
                                        data-fecha-nacimiento="<?php echo htmlspecialchars($mascota['fecha_nacimiento'] ?? ''); ?>"
                                        data-observaciones="<?php echo htmlspecialchars($mascota['observaciones'] ?? ''); ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                
                                <form action="mascotas.php" method="POST" style="display:inline;" onsubmit="return confirm('⚠️ ADVERTENCIA: Esta acción ELIMINARÁ PERMANENTEMENTE a la mascota <?php echo htmlspecialchars($mascota['nombre']); ?> y todos sus turnos y vacunas asociados. ¿Continuar?');">
                                    <input type="hidden" name="accion" value="desactivar_mascota">
                                    <input type="hidden" name="id_mascota" value="<?php echo $mascota['id_mascota']; ?>">
                                    <?php if ($id_cliente_filtrado):  ?>
                                        <input type="hidden" name="redirect_id_cliente" value="<?php echo $id_cliente_filtrado; ?>">
                                    <?php endif; ?>
                                    <button type="submit" class="btn btn-sm btn-danger" title="Eliminar Mascota Permanentemente">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>


<div class="modal fade" id="crearMascotaModal" tabindex="-1" aria-labelledby="crearMascotaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="mascotas.php" method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="crearMascotaModalLabel">Registrar Nueva Mascota</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="crear_mascota"> 
                    
                    <div class="mb-3">
                        <label for="id_cliente" class="form-label">Dueño (Cliente) <span class="text-danger">*</span></label>
                        <select class="form-control" id="id_cliente" name="id_cliente" required>
                            <option value="">Seleccione un dueño</option>
                            <?php foreach ($clientes_dropdown as $cliente): ?>
                                <option value="<?php echo $cliente['id_cliente']; ?>"
                                        <?php echo ($id_cliente_filtrado == $cliente['id_cliente'] ? 'selected' : ''); ?>>
                                    <?php echo htmlspecialchars($cliente['nombre_completo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre de la Mascota <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="especie" class="form-label">Especie <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="especie" name="especie" required>
                    </div>

                    <div class="mb-3">
                        <label for="raza" class="form-label">Raza</label>
                        <input type="text" class="form-control" id="raza" name="raza">
                    </div>
                    
                    <div class="mb-3">
                        <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                        <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento">
                    </div>

                    <div class="mb-3">
                        <label for="observaciones" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observaciones" name="observaciones" rows="3"></textarea>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar Mascota</button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="modal fade" id="editarMascotaModal" tabindex="-1" aria-labelledby="editarMascotaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="mascotas.php" method="POST">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="editarMascotaModalLabel">Editar Mascota</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="editar_mascota"> 
                    <input type="hidden" name="id_mascota_edit" id="id_mascota_edit"> 
                    
                    <div class="mb-3">
                        <label for="id_cliente_edit" class="form-label">Dueño (Cliente) <span class="text-danger">*</span></label>
                        <select class="form-control" id="id_cliente_edit" name="id_cliente_edit" required>
                            <option value="">Seleccione un dueño</option>
                            <?php foreach ($clientes_dropdown as $cliente): ?>
                                <option value="<?php echo $cliente['id_cliente']; ?>">
                                    <?php echo htmlspecialchars($cliente['nombre_completo']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="nombre_edit" class="form-label">Nombre de la Mascota <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombre_edit" name="nombre_edit" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="especie_edit" class="form-label">Especie <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="especie_edit" name="especie_edit" required>
                    </div>

                    <div class="mb-3">
                        <label for="raza_edit" class="form-label">Raza</label>
                        <input type="text" class="form-control" id="raza_edit" name="raza_edit">
                    </div>
                    
                    <div class="mb-3">
                        <label for="fecha_nacimiento_edit" class="form-label">Fecha de Nacimiento</label>
                        <input type="date" class="form-control" id="fecha_nacimiento_edit" name="fecha_nacimiento_edit">
                    </div>

                    <div class="mb-3">
                        <label for="observaciones_edit" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observaciones_edit" name="observaciones_edit" rows="3"></textarea>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-save"></i> Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const editarMascotaModal = document.getElementById('editarMascotaModal');
        editarMascotaModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            const idMascota = button.getAttribute('data-id');
            const idCliente = button.getAttribute('data-cliente-id');
            const nombre = button.getAttribute('data-nombre');
            const especie = button.getAttribute('data-especie');
            const raza = button.getAttribute('data-raza');
            const fechaNacimiento = button.getAttribute('data-fecha-nacimiento');
            const observaciones = button.getAttribute('data-observaciones');

            const modal = this;
            modal.querySelector('#id_mascota_edit').value = idMascota;
            modal.querySelector('#id_cliente_edit').value = idCliente; 
            modal.querySelector('#nombre_edit').value = nombre;
            modal.querySelector('#especie_edit').value = especie;
            modal.querySelector('#raza_edit').value = raza;
            modal.querySelector('#fecha_nacimiento_edit').value = fechaNacimiento;
            modal.querySelector('#observaciones_edit').value = observaciones;
        });
    });
</script>

<?php 
require_once __DIR__ . '/TEMPLATES/footer.php'; 
?>
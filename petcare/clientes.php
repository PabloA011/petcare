<?php
session_start();
if (!isset($_SESSION['id_veterinario'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/CONFIG/database.php'; 

$feedback_mensaje = '';
$feedback_tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {

    if ($_POST['accion'] === 'crear_cliente') {
        
        $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_SPECIAL_CHARS);
        $apellido = filter_input(INPUT_POST, 'apellido', FILTER_SANITIZE_SPECIAL_CHARS);
        $telefono = filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_SPECIAL_CHARS);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $direccion = filter_input(INPUT_POST, 'direccion', FILTER_SANITIZE_SPECIAL_CHARS);

        if (!$nombre || !$telefono) {
            $feedback_mensaje = "Error: El Nombre y el Teléfono son campos obligatorios.";
            $feedback_tipo = 'danger';
        } else {
            $sql = "INSERT INTO clientes (nombre, apellido, telefono, email, direccion, activo) 
                    VALUES (:nombre, :apellido, :telefono, :email, :direccion, 1)";
            $stmt = $pdo->prepare($sql);
            
            try {
                $stmt->execute([
                    'nombre' => $nombre,
                    'apellido' => $apellido ?: NULL,
                    'telefono' => $telefono,
                    'email' => $email ? $email : NULL,
                    'direccion' => $direccion ?: NULL
                ]);
                header('Location: clientes.php?success=creado');
                exit;
            } catch (PDOException $e) {
                $feedback_mensaje = "Error al registrar el cliente: " . $e->getMessage();
                $feedback_tipo = 'danger';
            }
        }
    } 

    elseif ($_POST['accion'] === 'desactivar_cliente' && isset($_POST['id_cliente'])) {
        
        $id_cliente = filter_input(INPUT_POST, 'id_cliente', FILTER_VALIDATE_INT);
        if ($id_cliente) {
            $sql = "DELETE FROM clientes WHERE id_cliente = :id"; 
            $stmt = $pdo->prepare($sql);
            try {
                $stmt->execute(['id' => $id_cliente]);
                header('Location: clientes.php?success=eliminado');
                exit;
            } catch (PDOException $e) {
                $feedback_mensaje = "Error al eliminar el cliente: " . $e->getMessage();
                $feedback_tipo = 'danger';
            }
        }
    }

    elseif ($_POST['accion'] === 'editar_cliente' && isset($_POST['id_cliente'])) {
        
        $id_cliente = filter_input(INPUT_POST, 'id_cliente', FILTER_VALIDATE_INT);
        $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_SPECIAL_CHARS);
        $apellido = filter_input(INPUT_POST, 'apellido', FILTER_SANITIZE_SPECIAL_CHARS);
        $telefono = filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_SPECIAL_CHARS);
        $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
        $direccion = filter_input(INPUT_POST, 'direccion', FILTER_SANITIZE_SPECIAL_CHARS);

        if ($id_cliente && $nombre && $telefono) {
            $sql = "UPDATE clientes SET 
                        nombre = :nombre, 
                        apellido = :apellido, 
                        telefono = :telefono, 
                        email = :email, 
                        direccion = :direccion
                    WHERE id_cliente = :id_cliente";
            $stmt = $pdo->prepare($sql);
            
            try {
                $stmt->execute([
                    'id_cliente' => $id_cliente,
                    'nombre' => $nombre,
                    'apellido' => $apellido ?: NULL,
                    'telefono' => $telefono,
                    'email' => $email ? $email : NULL,
                    'direccion' => $direccion ?: NULL
                ]);
                header('Location: clientes.php?success=editado');
                exit;
            } catch (PDOException $e) {
                $feedback_mensaje = "Error al actualizar el cliente: " . $e->getMessage();
                $feedback_tipo = 'danger';
            }
        } else {
             $feedback_mensaje = "Error: Faltan datos obligatorios (ID, Nombre o Teléfono).";
             $feedback_tipo = 'danger';
        }
    }
} 

else if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if (isset($_GET['accion']) && $_GET['accion'] === 'obtener_cliente' && isset($_GET['id'])) {
        $id_cliente = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if ($id_cliente) {
            $sql = "SELECT id_cliente, nombre, apellido, telefono, email, direccion FROM clientes WHERE id_cliente = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $id_cliente]);
            $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

            header('Content-Type: application/json');
            echo json_encode($cliente);
            exit; 
        }
    }
}

if (isset($_GET['success'])) {
    if ($_GET['success'] === 'creado') {
        $feedback_mensaje = "👤 Cliente registrado exitosamente.";
        $feedback_tipo = 'success';
    } elseif ($_GET['success'] === 'eliminado') {
        $feedback_mensaje = "🗑️ Cliente eliminado permanentemente (y sus mascotas/registros asociados).";
        $feedback_tipo = 'danger';
    } elseif ($_GET['success'] === 'editado') {
        $feedback_mensaje = "✏️ Datos del cliente actualizados correctamente.";
        $feedback_tipo = 'success';
    }
}

$sql_clientes = "SELECT id_cliente, nombre, apellido, telefono, email, direccion FROM clientes WHERE activo = 1 ORDER BY apellido, nombre ASC";
$stmt = $pdo->query($sql_clientes);
$clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/TEMPLATES/header.php'; 
?>

<h1 class="mb-4">👥 Gestión de Clientes (Dueños)</h1>

<button type="button" class="btn btn-primary mb-4" data-bs-toggle="modal" data-bs-target="#crearClienteModal">
    <i class="bi bi-person-plus-fill"></i> Agregar Nuevo Cliente
</button>

<?php if ($feedback_mensaje): ?>
    <div class="alert alert-<?php echo $feedback_tipo; ?> alert-dismissible fade show" role="alert">
        <?php echo $feedback_mensaje; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>


<div class="card shadow">
    <div class="card-body">
        <h5 class="card-title">Listado de Clientes Activos</h5>
        
        <?php if (empty($clientes)): ?>
            <div class="alert alert-info" role="alert">
                Aún no hay clientes registrados.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Nombre Completo</th>
                            <th>Teléfono</th>
                            <th>Email</th>
                            <th>Dirección</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $cliente): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido']); ?></td>
                            <td><?php echo htmlspecialchars($cliente['telefono']); ?></td>
                            <td><?php echo htmlspecialchars($cliente['email'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($cliente['direccion'] ?? 'N/A'); ?></td>
                            <td>
                                <a href="mascotas.php?id_cliente=<?php echo $cliente['id_cliente']; ?>" class="btn btn-sm btn-info" title="Ver Mascotas del Cliente">
                                    <i class="bi bi-eye"></i> Mascotas
                                </a>
                                
                                <button type="button" class="btn btn-sm btn-warning btn-editar-cliente" title="Editar Cliente"
                                    data-bs-toggle="modal" data-bs-target="#editarClienteModal" 
                                    data-id="<?php echo $cliente['id_cliente']; ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                
                                <form action="clientes.php" method="POST" style="display:inline;" onsubmit="return confirm('⚠️ ADVERTENCIA: Esta acción ELIMINARÁ PERMANENTEMENTE a <?php echo htmlspecialchars($cliente['nombre']); ?> y TODAS sus mascotas, turnos y registros asociados. ¿Continuar?');">
                                    <input type="hidden" name="accion" value="desactivar_cliente">
                                    <input type="hidden" name="id_cliente" value="<?php echo $cliente['id_cliente']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Eliminar Cliente Permanentemente">
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


<div class="modal fade" id="crearClienteModal" tabindex="-1" aria-labelledby="crearClienteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="clientes.php" method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="crearClienteModalLabel">Registrar Nuevo Cliente</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="crear_cliente"> 
                    
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="apellido" class="form-label">Apellido</label>
                        <input type="text" class="form-control" id="apellido" name="apellido">
                    </div>

                    <div class="mb-3">
                        <label for="telefono" class="form-label">Teléfono <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="telefono" name="telefono" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email">
                    </div>

                    <div class="mb-3">
                        <label for="direccion" class="form-label">Dirección</label>
                        <input type="text" class="form-control" id="direccion" name="direccion">
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar Cliente</button>
                </div>
            </form>
        </div>
    </div>
</div>


<div class="modal fade" id="editarClienteModal" tabindex="-1" aria-labelledby="editarClienteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="clientes.php" method="POST" id="form-editar-cliente">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="editarClienteModalLabel">Editar Cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="editar_cliente"> 
                    <input type="hidden" name="id_cliente" id="editar-id-cliente"> 
                    
                    <div class="mb-3">
                        <label for="editar-nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editar-nombre" name="nombre" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editar-apellido" class="form-label">Apellido</label>
                        <input type="text" class="form-control" id="editar-apellido" name="apellido">
                    </div>

                    <div class="mb-3">
                        <label for="editar-telefono" class="form-label">Teléfono <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editar-telefono" name="telefono" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editar-email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="editar-email" name="email">
                    </div>

                    <div class="mb-3">
                        <label for="editar-direccion" class="form-label">Dirección</label>
                        <input type="text" class="form-control" id="editar-direccion" name="direccion">
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-arrow-repeat"></i> Actualizar Cliente</button>
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
    
    var editarClienteModal = document.getElementById('editarClienteModal');
    if (editarClienteModal) {
        editarClienteModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var id_cliente = button.getAttribute('data-id');
   
            fetch('clientes.php?accion=obtener_cliente&id=' + id_cliente)
                .then(response => response.json())
                .then(data => {
                    if(data) {
                        document.getElementById('editar-id-cliente').value = data.id_cliente || '';
                        document.getElementById('editar-nombre').value = data.nombre || '';
                        document.getElementById('editar-apellido').value = data.apellido || '';
                        document.getElementById('editar-telefono').value = data.telefono || '';
                        document.getElementById('editar-email').value = data.email || '';
                        document.getElementById('editar-direccion').value = data.direccion || '';
                    }
                })
                .catch(error => console.error('Error al cargar datos del cliente:', error));
        });
    }
});
</script>
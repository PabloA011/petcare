<?php

session_start();
if (!isset($_SESSION['id_veterinario'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/CONFIG/database.php'; 

$id_veterinario_sesion = $_SESSION['id_veterinario'];

$feedback_mensaje = $_SESSION['message'] ?? '';
$feedback_tipo = $_SESSION['message_type'] ?? '';
unset($_SESSION['message']);
unset($_SESSION['message_type']);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_events') {

    $sql = "
        SELECT 
            t.id_turno AS id, 
            t.fecha_hora, 
            t.motivo, 
            m.nombre AS nombre_mascota,
            m.id_mascota,
            t.notas,
            c.nombre AS nombre_cliente, 
            c.telefono, 
            t.estado 
        FROM turnos t
        JOIN mascotas m ON t.id_mascota = m.id_mascota
        LEFT JOIN clientes c ON m.id_cliente = c.id_cliente
        WHERE t.id_veter = :id_vet_filtro
        ORDER BY t.fecha_hora
    ";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id_vet_filtro' => $id_veterinario_sesion]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $events = [];
        foreach ($results as $row) {
             $color = '#3788d8'; 
             if ($row['estado'] === 'Confirmado') $color = '#28a745'; 
             elseif ($row['estado'] === 'Atendido') $color = '#17a2b8';
             elseif ($row['estado'] === 'Cancelado') $color = '#dc3545'; 
    
             $titulo_evento = htmlspecialchars($row['nombre_mascota']) . ' (' . htmlspecialchars($row['nombre_cliente'] ?? 'Sin Dueño') . ')'; 
             $events[] = [
                 'id' => $row['id'],
                 'title' => $titulo_evento, 
                 'start' => $row['fecha_hora'], 
                 'extendedProps' => [ 
                     'motivo' => htmlspecialchars($row['motivo']),
                     'id_mascota' => $row['id_mascota'],
                     'notas' => htmlspecialchars($row['notas']),
                     'nombre_cliente' => htmlspecialchars($row['nombre_cliente'] ?? 'Sin Dueño'),
                     'telefono_cliente' => htmlspecialchars($row['telefono'] ?? 'N/A'),
                     'estado' => htmlspecialchars($row['estado'] ?? 'Pendiente')
                 ],
                 'backgroundColor' => $color,
                 'borderColor' => $color
             ];
        }

        header('Content-Type: application/json');
        echo json_encode($events);
        exit;

    } catch (PDOException $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['error' => 'Error al cargar los turnos: ' . $e->getMessage()]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_turno') {
    
    $id_mascota = filter_input(INPUT_POST, 'id_mascota', FILTER_VALIDATE_INT);
    $fecha_hora = filter_input(INPUT_POST, 'fecha_hora', FILTER_SANITIZE_SPECIAL_CHARS);
    $motivo = filter_input(INPUT_POST, 'motivo', FILTER_SANITIZE_SPECIAL_CHARS);
    $notas = filter_input(INPUT_POST, 'notas', FILTER_SANITIZE_SPECIAL_CHARS);

    $id_veter = $id_veterinario_sesion; 

    if (!$id_mascota || !$fecha_hora || !$motivo) {
        $_SESSION['message'] = "Error: Faltan datos obligatorios para crear el turno.";
        $_SESSION['message_type'] = "danger";
        header('Location: turnos.php');
        exit;
    }
    
    try {
        $sql = "INSERT INTO turnos (id_mascota, id_veter, fecha_hora, motivo, notas, estado) 
                VALUES (:id_mascota, :id_veter, :fecha_hora, :motivo, :notas, 'Pendiente')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id_mascota' => $id_mascota,
            'id_veter' => $id_veter, 
            'fecha_hora' => date('Y-m-d H:i:s', strtotime($fecha_hora)), 
            'motivo' => $motivo,
            'notas' => $notas 
        ]);

        $_SESSION['message'] = "Turno agendado exitosamente para " . date('d/m/Y H:i', strtotime($fecha_hora)) . ".";
        $_SESSION['message_type'] = "success";
        header('Location: turnos.php');
        exit;

    } catch (PDOException $e) {
        $_SESSION['message'] = "Error al guardar el turno: " . $e->getMessage();
        $_SESSION['message_type'] = "danger";
        header('Location: turnos.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_turno') {
    $id_turno = filter_input(INPUT_POST, 'id_turno', FILTER_VALIDATE_INT);
    
    if (!$id_turno) {
        $_SESSION['message'] = "Error: ID de turno inválido.";
        $_SESSION['message_type'] = "danger";
        header('Location: turnos.php');
        exit;
    }

    try {
        $sql = "DELETE FROM turnos WHERE id_turno = :id_turno";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id_turno' => $id_turno]);

        $_SESSION['message'] = "Turno eliminado correctamente.";
        $_SESSION['message_type'] = "warning";
        header('Location: turnos.php');
        exit;

    } catch (PDOException $e) {
        $_SESSION['message'] = "Error al eliminar el turno: " . $e->getMessage();
        $_SESSION['message_type'] = "danger";
        header('Location: turnos.php');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_consulta_from_turno') {
    $id_turno = filter_input(INPUT_POST, 'id_turno_consulta', FILTER_VALIDATE_INT);
    
    if (!$id_turno) {
        $_SESSION['message'] = "Error: ID de turno inválido o faltante.";
        $_SESSION['message_type'] = "danger";
        header('Location: turnos.php');
        exit;
    }

    try {
        $sql_get_turno = "SELECT id_mascota, fecha_hora, motivo, id_veter FROM turnos WHERE id_turno = :id";
        $stmt_get = $pdo->prepare($sql_get_turno);
        $stmt_get->execute(['id' => $id_turno]);
        $datos_turno = $stmt_get->fetch(PDO::FETCH_ASSOC);

        if (!$datos_turno) {
            $_SESSION['message'] = "Error: Turno no encontrado.";
            $_SESSION['message_type'] = "danger";
            header('Location: turnos.php');
            exit;
        }

        $id_mascota_consulta = $datos_turno['id_mascota'];
        $fecha_consulta = date('Y-m-d', strtotime($datos_turno['fecha_hora'])); 
        $motivo_consulta = "Consulta de Turno: " . $datos_turno['motivo'];

        $sql_insert = "INSERT INTO consultas (id_mascota, fecha_consulta, motivo, id_veterinario) 
                        VALUES (:id_mascota, :fecha_consulta, :motivo, :id_veterinario)";
        $stmt_insert = $pdo->prepare($sql_insert);
        $stmt_insert->execute([
            'id_mascota' => $id_mascota_consulta,
            'fecha_consulta' => $fecha_consulta,
            'motivo' => $motivo_consulta,
            'id_veterinario' => $id_veterinario_sesion
        ]);

        $sql_update_turno = "UPDATE turnos SET estado = 'Atendido' WHERE id_turno = :id";
        $stmt_update = $pdo->prepare($sql_update_turno);
        $stmt_update->execute(['id' => $id_turno]);

        header("Location: consultas.php?id={$id_mascota_consulta}&msg=" . urlencode("✅ Consulta iniciada automáticamente. El turno fue marcado como Atendido. Rellene el diagnóstico."));
        exit;

    } catch (PDOException $e) {
        $_SESSION['message'] = "Error al iniciar la consulta: " . $e->getMessage();
        $_SESSION['message_type'] = "danger";
        header('Location: turnos.php');
        exit;
    }
}

$mascotas_options = [];
try {
    $sql_mascotas = "SELECT m.id_mascota, m.nombre, m.especie, c.nombre AS nombre_cliente 
                      FROM mascotas m 
                      LEFT JOIN clientes c ON m.id_cliente = c.id_cliente 
                      WHERE m.activo = 1 ORDER BY m.nombre ASC";
    $stmt_mascotas = $pdo->query($sql_mascotas);
    $mascotas_options = $stmt_mascotas->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {

}

require_once __DIR__ . '/TEMPLATES/header.php'; 
?>

<h1 class="mb-4"><i class="bi bi-calendar-event"></i> Gestión de Turnos</h1>

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

<div class="card shadow mb-4">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
        Calendario
        <button type="button" class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#modalNuevoTurnoManual">
            <i class="bi bi-plus-circle-fill me-1"></i> Agendar Nuevo Turno
        </button>
    </div>
    <div class="card-body">
        <div id='calendar'></div>
    </div>
</div>


<div class="modal fade" id="modalNuevoTurno" tabindex="-1" aria-labelledby="modalNuevoTurnoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="turnos.php" method="POST">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalNuevoTurnoLabel"><i class="bi bi-calendar-plus"></i> Agendar Turno</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_turno">
                    
                    <div class="mb-3">
                        <label for="mascota_select" class="form-label">Mascota y Dueño <span class="text-danger">*</span></label>
                        <select class="form-select" id="mascota_select" name="id_mascota" required>
                            <option value="">Seleccione una mascota</option>
                            <?php foreach ($mascotas_options as $mascota): 
                                $nombre_display = htmlspecialchars($mascota['nombre'] . ' (' . ($mascota['nombre_cliente'] ?? 'Sin Dueño') . ')');
                            ?>
                                <option value="<?php echo $mascota['id_mascota']; ?>">
                                    <?php echo $nombre_display; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="fecha_hora_input" class="form-label">Fecha y Hora <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" id="fecha_hora_input" name="fecha_hora" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="motivo_input" class="form-label">Motivo del Turno <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="motivo_input" name="motivo" required>
                    </div>

                    <div class="mb-3">
                        <label for="notas_input" class="form-label">Notas Adicionales</label>
                        <textarea class="form-control" id="notas_input" name="notas" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Guardar Turno</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNuevoTurnoManual" tabindex="-1" aria-labelledby="modalNuevoTurnoManualLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="turnos.php" method="POST">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalNuevoTurnoManualLabel"><i class="bi bi-calendar-plus"></i> Agendar Turno</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_turno">
                    
                    <div class="mb-3">
                        <label for="mascota_select_manual" class="form-label">Mascota y Dueño <span class="text-danger">*</span></label>
                        <select class="form-select" id="mascota_select_manual" name="id_mascota" required>
                            <option value="">Seleccione una mascota</option>
                            <?php foreach ($mascotas_options as $mascota): 
                                $nombre_display = htmlspecialchars($mascota['nombre'] . ' (' . ($mascota['nombre_cliente'] ?? 'Sin Dueño') . ')');
                            ?>
                                <option value="<?php echo $mascota['id_mascota']; ?>">
                                    <?php echo $nombre_display; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="fecha_hora_input_manual" class="form-label">Fecha y Hora <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" id="fecha_hora_input_manual" name="fecha_hora" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="motivo_input_manual" class="form-label">Motivo del Turno <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="motivo_input_manual" name="motivo" required>
                    </div>

                    <div class="mb-3">
                        <label for="notas_input_manual" class="form-label">Notas Adicionales</label>
                        <textarea class="form-control" id="notas_input_manual" name="notas" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="bi bi-save"></i> Guardar Turno</button>
                </div>
            </form>
        </div>
        
    </div>
</div>


<div class="modal fade" id="modalDetalleTurno" tabindex="-1" aria-labelledby="modalDetalleTurnoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="modalDetalleTurnoLabel"><i class="bi bi-info-circle"></i> Detalle del Turno</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Mascota:</strong> <span id="detalle-mascota"></span></p>
                
                <p><strong>Dueño:</strong> <span id="detalle-cliente" class="fw-bold"></span></p>
                <p><strong>Teléfono:</strong> <span id="detalle-telefono-cliente"></span></p>
                <p><strong>Fecha y Hora:</strong> <span id="detalle-fecha-hora"></span></p>
                <p><strong>Motivo:</strong> <span id="detalle-motivo" class="fw-bold"></span></p>
                <p><strong>Estado:</strong> <span id="detalle-estado" class="badge bg-secondary"></span></p>
                <p><strong>Notas:</strong> <span id="detalle-notas"></span></p>
                
                <hr>
                <a href="#" id="detalle-link-historial" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark-medical"></i> Ver Historial Clínico</a>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                
                <form action="turnos.php" method="POST" id="form-iniciar-consulta" onsubmit="return confirm('¿Confirma que desea iniciar la consulta? Esto creará un nuevo registro de atención y marcará el turno como ATENDIDO.');" style="display:inline;">
                    <input type="hidden" name="action" value="create_consulta_from_turno">
                    <input type="hidden" name="id_turno_consulta" id="detalle-id-turno-consulta">
                    <button type="submit" class="btn btn-success" id="btn-iniciar-consulta"><i class="bi bi-file-medical"></i> Iniciar Consulta</button>
                </form>
                
                <form action="turnos.php" method="POST" id="form-eliminar-turno" onsubmit="return confirm('¿Está seguro de CANCELAR este turno? Esta acción no se puede deshacer.');" style="display:inline;">
                    <input type="hidden" name="action" value="delete_turno">
                    <input type="hidden" name="id_turno" id="detalle-id-turno-eliminar">
                    <button type="submit" class="btn btn-danger" id="btn-cancelar-turno"><i class="bi bi-trash"></i> Cancelar Turno</button>
                </form>

                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>


<?php 
require_once __DIR__ . '/TEMPLATES/footer.php'; 
?>

<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var modalNuevoTurno = new bootstrap.Modal(document.getElementById('modalNuevoTurno'));
    var modalDetalleTurno = new bootstrap.Modal(document.getElementById('modalDetalleTurno'));

    function formatDateTimeLocal(date) {
        var y = date.getFullYear();
        var m = (date.getMonth() + 1).toString().padStart(2, '0');
        var d = date.getDate().toString().padStart(2, '0');
        var h = date.getHours().toString().padStart(2, '0');
        var min = date.getMinutes().toString().padStart(2, '0');
        return `${y}-${m}-${d}T${h}:${min}`;
    }

    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'es',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay'
        },
        editable: true,
        selectable: true,
        
        events: 'turnos.php?action=get_events',
    
        select: function(info) {
            document.getElementById('fecha_hora_input').value = formatDateTimeLocal(info.start);
            modalNuevoTurno.show();
        },

        eventClick: function(info) {
            var event = info.event;
            var props = event.extendedProps;
 
            document.getElementById('detalle-id-turno-eliminar').value = event.id;
            document.getElementById('detalle-id-turno-consulta').value = event.id;

            document.getElementById('detalle-mascota').textContent = event.title; 
            document.getElementById('detalle-cliente').textContent = props.nombre_cliente; 
            document.getElementById('detalle-telefono-cliente').textContent = props.telefono_cliente;
            document.getElementById('detalle-motivo').textContent = props.motivo;
            document.getElementById('detalle-notas').textContent = props.notas || 'Sin notas.';

            const estadoSpan = document.getElementById('detalle-estado');
            estadoSpan.textContent = props.estado;
            
            let badgeClass = 'bg-secondary';
            let iniciarDisabled = false;
            let cancelarDisabled = false;

            if (props.estado === 'Confirmado') {
                badgeClass = 'bg-success';
            } else if (props.estado === 'Atendido') {
                badgeClass = 'bg-primary';
                iniciarDisabled = true; 
            } else if (props.estado === 'Cancelado') {
                badgeClass = 'bg-danger';
                iniciarDisabled = true;
                cancelarDisabled = true; 
            } else { 
                badgeClass = 'bg-warning';
            }
            estadoSpan.className = `badge ${badgeClass}`;

            document.getElementById('btn-iniciar-consulta').style.display = iniciarDisabled ? 'none' : 'inline-block';
            document.getElementById('btn-cancelar-turno').style.display = cancelarDisabled ? 'none' : 'inline-block';

            var dateOptions = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
            document.getElementById('detalle-fecha-hora').textContent = event.start.toLocaleString('es-ES', dateOptions);

            var linkHistorial = `mascota_historial.php?id=${props.id_mascota}`;
            document.getElementById('detalle-link-historial').href = linkHistorial;

            modalDetalleTurno.show();
        },

        eventDrop: function(info) {
            if (!confirm("¿Está seguro de cambiar la fecha/hora de este turno? La actualización en DB es PENDIENTE.")) {
                info.revert();
            } else {
                 alert('Turno movido. Para finalizar la gestión de arrastrar y soltar, necesitarías implementar la lógica de UPDATE vía AJAX.');
            }
        },
    });

    calendar.render();
});
</script>
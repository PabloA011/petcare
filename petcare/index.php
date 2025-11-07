<?php

date_default_timezone_set('America/Argentina/Cordoba'); 

session_start();

if (!isset($_SESSION['id_veterinario'])) {
    header('Location: login.php');
    exit;
}

$id_veterinario_sesion = $_SESSION['id_veterinario'];
$nombre_veterinario = $_SESSION['nombre_veterinario'] ?? 'Usuario';

require_once __DIR__ . '/CONFIG/database.php'; 
require_once __DIR__ . '/TEMPLATES/header.php'; 

$total_pacientes = 0;
$turnos_hoy = 0;
$vacunas_vencen = 0;

$hoy = date('Y-m-d'); 

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM mascotas WHERE activo = 1");
    $total_pacientes = $stmt->fetchColumn();

    $sql_turnos = "
        SELECT COUNT(*) 
        FROM turnos 
        WHERE 
            DATE(fecha_hora) = :fecha_actual        /* Compara solo el día */
            AND estado IN ('Pendiente', 'Confirmado')  /* Incluye turnos no cancelados/atendidos */
            AND id_veter = :id_veterinario_filtro";           /* Filtra por el veterinario logueado */
            
    $stmt_turnos = $pdo->prepare($sql_turnos);

    $stmt_turnos->execute([
        ':id_veterinario_filtro' => $id_veterinario_sesion,
        ':fecha_actual' => $hoy 
    ]);
    $turnos_hoy = $stmt_turnos->fetchColumn();

    $sql_vacunas = "SELECT COUNT(*) FROM vacunas WHERE fecha_vencimiento BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)";
    $stmt_vacunas = $pdo->query($sql_vacunas);
    $vacunas_vencen = $stmt_vacunas->fetchColumn();

} catch (PDOException $e) { 
    $turnos_hoy = 0;
    $vacunas_vencen = 0;

}

$alertas = []; 

try {

    $sql_turnos_alertas = "
        SELECT 
            t.fecha_hora, t.motivo, m.nombre AS mascota_nombre, m.especie 
        FROM 
            turnos t
        JOIN 
            mascotas m ON t.id_mascota = m.id_mascota
        WHERE 
            t.fecha_hora >= NOW() AND t.estado = 'Pendiente'
            AND t.id_veter = :id_vet_alertas_turnos 
        ORDER BY
            t.fecha_hora ASC
        LIMIT 10
    ";
    $stmt_turnos = $pdo->prepare($sql_turnos_alertas);
    $stmt_turnos->execute([':id_vet_alertas_turnos' => $id_veterinario_sesion]);
    $turnos_proximos = $stmt_turnos->fetchAll(PDO::FETCH_ASSOC);

    foreach ($turnos_proximos as $turno) {
        $fecha_formateada = date('d/m/Y H:i', strtotime($turno['fecha_hora']));
        $alertas[] = [
            'tipo' => 'turno',
            'nivel' => 'warning',
            'mensaje' => "El turno con '{$turno['mascota_nombre']}' ({$turno['especie']}) es el '{$fecha_formateada}' por motivo: {$turno['motivo']}.",
            'fecha_orden' => $turno['fecha_hora']
        ];
    }

    $sql_vacunas_alertas = "
        SELECT 
            v.tipo_vacuna, v.fecha_vencimiento, m.nombre AS mascota_nombre, m.especie 
        FROM 
            vacunas v
        JOIN 
            mascotas m ON v.id_mascota = m.id_mascota
        WHERE 
            v.fecha_vencimiento BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 30 DAY)
        ORDER BY 
            v.fecha_vencimiento ASC
    ";
    $stmt_vacunas = $pdo->query($sql_vacunas_alertas);
    $vacunas_vencimiento = $stmt_vacunas->fetchAll(PDO::FETCH_ASSOC);

    foreach ($vacunas_vencimiento as $vacuna) {
        $fecha_formateada = date('d/m/Y', strtotime($vacuna['fecha_vencimiento']));
        $nivel_alerta = (strtotime($vacuna['fecha_vencimiento']) < strtotime('+7 days', strtotime($hoy))) ? 'danger' : 'info'; 

        $alertas[] = [
            'tipo' => 'vacuna',
            'nivel' => $nivel_alerta,
            'mensaje' => "La vacuna '{$vacuna['tipo_vacuna']}' de '{$vacuna['mascota_nombre']}' vence el '{$fecha_formateada}'.",
            'fecha_orden' => $vacuna['fecha_vencimiento'] 
        ];
    }

    usort($alertas, function($a, $b) {
        return strtotime($a['fecha_orden']) - strtotime($b['fecha_orden']);
    });

} catch (PDOException $e) {
    $alertas = [['tipo' => 'error', 'nivel' => 'danger', 'mensaje' => 'Error al cargar alertas.']];
}

?>

<h1 class="mb-4">Panel Principal, Dr/a. <?php echo htmlspecialchars($nombre_veterinario); ?></h1>

<div class="row mb-5">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h4 class="mb-0"><i class="bi bi-bell-fill"></i> Alertas y Recordatorios</h4>
            </div>
            <div class="card-body" id="alertas-proximas">
                
                <?php if (empty($alertas) || (count($alertas) == 1 && ($alertas[0]['tipo'] === 'error' && $alertas[0]['nivel'] === 'danger'))): ?>
                    <div class="alert alert-success mb-0" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i> ¡Sistema de alertas limpio! No hay recordatorios urgentes.
                    </div>
                <?php else: ?>
                    <?php foreach ($alertas as $alerta): ?>
                        <div class="alert alert-<?php echo $alerta['nivel']; ?> d-flex align-items-center mb-2" role="alert">
                            <?php 
                                $icon = '';
                                if ($alerta['tipo'] === 'turno') $icon = '<i class="bi bi-calendar-x-fill me-2"></i>';
                                if ($alerta['tipo'] === 'vacuna') $icon = '<i class="bi bi-virus me-2"></i>';
                                if ($alerta['tipo'] === 'error') $icon = '<i class="bi bi-exclamation-triangle-fill me-2"></i>';
                                
                                echo $icon;
                                echo $alerta['mensaje']; 
                            ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card text-white bg-success h-100 shadow">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title">Turnos Hoy</h3>
                        <p class="fs-1 fw-bold"><?php echo htmlspecialchars($turnos_hoy); ?></p>
                    </div>
                    <i class="bi bi-calendar3-event display-4"></i>
                </div>
            </div>
            <a href="turnos.php" class="card-footer text-white text-decoration-none">
                Ver Agenda Completa <i class="bi bi-arrow-right-circle-fill"></i>
            </a>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card text-white bg-primary h-100 shadow">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title">Pacientes Activos</h3>
                        <p class="fs-1 fw-bold"><?php echo htmlspecialchars($total_pacientes); ?></p>
                    </div>
                    <i class="bi bi-hospital-fill display-4"></i>
                </div>
            </div>
            <a href="mascotas.php" class="card-footer text-white text-decoration-none">
                Gestionar Mascotas <i class="bi bi-arrow-right-circle-fill"></i>
            </a>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card text-white bg-danger h-100 shadow">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title">Vacunas Vencen (7 días)</h3>
                        <p class="fs-1 fw-bold"><?php echo htmlspecialchars($vacunas_vencen); ?></p>
                    </div>
                    <i class="bi bi-shield-fill-exclamation display-4"></i>
                </div>
            </div>
            <a href="vacunas.php" class="card-footer text-white text-decoration-none">
                Revisar Vencimientos <i class="bi bi-arrow-right-circle-fill"></i>
            </a>
        </div>
    </div>
</div>

<?php 

?>
    <div class="animal-slider-fixed">
        <div class="animal-slide-track">
            <div class="animal-slide"><img src="img/perro.png" alt="Perro"></div>
            <div class="animal-slide"><img src="img/gato.png" alt="Gato"></div>
            <div class="animal-slide"><img src="img/conejo.png" alt="Conejo"></div>
            <div class="animal-slide"><img src="img/ave.png" alt="Ave"></div>
            <div class="animal-slide"><img src="img/pez.png" alt="Pez"></div>

            <div class="animal-slide"><img src="img/perro.png" alt="Perro"></div>
            <div class="animal-slide"><img src="img/gato.png" alt="Gato"></div>
            <div class="animal-slide"><img src="img/conejo.png" alt="Conejo"></div>
            <div class="animal-slide"><img src="img/ave.png" alt="Ave"></div>
            <div class="animal-slide"><img src="img/pez.png" alt="Pez"></div>


            <div class="animal-slide"><img src="img/perro.png" alt="Perro"></div>
            <div class="animal-slide"><img src="img/gato.png" alt="Gato"></div>
            <div class="animal-slide"><img src="img/conejo.png" alt="Conejo"></div>
            <div class="animal-slide"><img src="img/ave.png" alt="Ave"></div>
            <div class="animal-slide"><img src="img/pez.png" alt="Pez"></div>

        </div>
    </div>

<?php 
require_once __DIR__ . '/TEMPLATES/footer.php'; 
?>
</body>
</html>
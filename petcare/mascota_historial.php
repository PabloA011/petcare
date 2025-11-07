<?php
session_start();

if (!isset($_SESSION['id_veterinario'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/CONFIG/database.php'; 

$id_mascota = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$mascota = null;
$error = '';

if (!$id_mascota) {
    $error = "Error: ID de mascota no válido o faltante.";
} else {
    try {
        $sql = "
            SELECT 
                m.id_mascota, m.nombre, m.especie, m.raza, m.fecha_nacimiento, m.observaciones, 
                c.id_cliente, CONCAT(c.nombre, ' ', c.apellido) AS nombre_completo_cliente, c.telefono, c.email
            FROM 
                mascotas m
            JOIN 
                clientes c ON m.id_cliente = c.id_cliente
            WHERE 
                m.id_mascota = :id_mascota AND m.activo = 1
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id_mascota' => $id_mascota]);
        $mascota = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$mascota) {
            $error = "La mascota con ID #{$id_mascota} no fue encontrada o está inactiva.";
        }
    } catch (PDOException $e) {
        $error = "Error de base de datos al cargar la mascota: " . $e->getMessage();
    }
}

require_once __DIR__ . '/TEMPLATES/header.php'; 
?>

<div class="container mt-4">

    <h1 class="mb-4">📋 Historial Clínico</h1>
    
    <a href="mascotas.php" class="btn btn-secondary mb-4">
        <i class="bi bi-arrow-left"></i> Volver al Listado de Mascotas
    </a>
    
    <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($mascota): ?>
        
        <div class="card shadow mb-4">
            <div class="card-header bg-info text-white">
                <h3 class="mb-0">
                    <i class="bi bi-person-bounding-box"></i> Ficha de **<?php echo htmlspecialchars($mascota['nombre']); ?>**
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Dueño:</strong> <a href="clientes.php?id=<?php echo htmlspecialchars($mascota['id_cliente']); ?>"><?php echo htmlspecialchars($mascota['nombre_completo_cliente']); ?></a></p>
                        <p><strong>Especie:</strong> <?php echo htmlspecialchars($mascota['especie']); ?></p>
                        <p><strong>Raza:</strong> <?php echo htmlspecialchars($mascota['raza'] ?? 'N/A'); ?></p>
                        <p><strong>Fecha Nacimiento:</strong> <?php echo htmlspecialchars($mascota['fecha_nacimiento'] ? date('d/m/Y', strtotime($mascota['fecha_nacimiento'])) : 'N/A'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Teléfono Dueño:</strong> <?php echo htmlspecialchars($mascota['telefono'] ?? 'N/A'); ?></p>
                        <p><strong>Email Dueño:</strong> <?php echo htmlspecialchars($mascota['email'] ?? 'N/A'); ?></p>
                        <p><strong>Observaciones Generales:</strong> <?php echo htmlspecialchars($mascota['observaciones'] ?? 'Sin observaciones.'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="card text-center bg-light shadow-sm h-100">
                    <div class="card-body">
                        <h4 class="card-title">Historial de Vacunas</h4>
                        <p class="card-text text-muted">Añadir, ver y editar las dosis y fechas de vencimiento.</p>
                        <a href="vacunas.php?id=<?php echo htmlspecialchars($mascota['id_mascota']); ?>" class="btn btn-success btn-lg">
                            <i class="bi bi-bandaid-fill"></i> Gestionar Vacunas
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 mb-3">
                <div class="card text-center bg-light shadow-sm h-100">
                    <div class="card-body">
                        <h4 class="card-title">Historial de Consultas</h4>
                        <p class="card-text text-muted">Registrar atenciones médicas, diagnósticos y tratamientos.</p>
                        <a href="consultas.php?id=<?php echo htmlspecialchars($mascota['id_mascota']); ?>" class="btn btn-primary btn-lg">
                            <i class="bi bi-cardio"></i> Gestionar Consultas
                        </a>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>

</div>

<?php 
require_once __DIR__ . '/TEMPLATES/footer.php'; 
?>
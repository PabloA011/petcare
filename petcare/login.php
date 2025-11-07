<?php

session_start();

require_once __DIR__ . '/config/database.php'; 

if (isset($_SESSION['id_veterinario'])) {
    header('Location: index.php');
    exit;
}

$error_message = '';     
$registro_exito = '';    
$registro_error = '';    


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'registro') {
    
    $nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_SPECIAL_CHARS);
    $usuario = filter_input(INPUT_POST, 'usuario_reg', FILTER_SANITIZE_SPECIAL_CHARS);
    $password_clara = $_POST['password_reg'];
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    if ($nombre && $usuario && $password_clara && $email) {
        
        try {
            $password_hash = password_hash($password_clara, PASSWORD_DEFAULT);

            $sql = "INSERT INTO veterinarios (nombre, usuario, password, email) VALUES (:nombre, :usuario, :password, :email)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'nombre' => $nombre,
                'usuario' => $usuario,
                'password' => $password_hash,
                'email' => $email
            ]);

            $registro_exito = "¡Registro exitoso! Ya puedes iniciar sesión con tu nuevo usuario.";

        } catch (PDOException $e) {
            if ($e->getCode() == '23000') {
                $registro_error = "El nombre de usuario o el correo electrónico ya están registrados. Intente con otros datos.";
            } else {
                $registro_error = "Ocurrió un error inesperado al intentar registrar el usuario. Contacte soporte.";
            }
        }
    } else {
        $registro_error = "Por favor, complete todos los campos del formulario de registro.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['accion']) || $_POST['accion'] !== 'registro')) {
    
    $usuario = filter_input(INPUT_POST, 'usuario', FILTER_SANITIZE_SPECIAL_CHARS);
    $password = $_POST['password'];

    if ($usuario && $password) {
        
        $sql = "SELECT id_veterinario, password, nombre FROM veterinarios WHERE usuario = :usuario";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['usuario' => $usuario]);
        $veterinario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($veterinario) {
        
            if (password_verify($password, $veterinario['password'])) {
                
                $_SESSION['id_veterinario'] = $veterinario['id_veterinario'];
                $_SESSION['nombre_veterinario'] = $veterinario['nombre']; 
                
                header('Location: index.php');
                exit;

            } else {
                $error_message = "Credenciales incorrectas. Verifique usuario y contraseña.";
            }
        } else {
            $error_message = "Credenciales incorrectas. Verifique usuario y contraseña.";
        }
    } else {
        $error_message = "Por favor, complete ambos campos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetCare | Iniciar Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            background-color: #f8f9fa; 
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .login-card {
            max-width: 400px;
            padding: 30px;
            width: 100%; 
        }
    </style>
</head>
<body>

    <div class="card shadow-lg login-card">
        <div class="card-header text-center bg-primary text-white">
            <h4 class="mb-0"><i class="bi bi-heart-pulse-fill"></i> PetCare Inicio de Sesión</h4>
        </div>
        <div class="card-body">
            <h5 class="card-title text-center mb-4">Acceso Veterinario</h5>

            <?php if ($error_message): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                
                <div class="mb-3">
                    <label for="usuario" class="form-label">Usuario</label>
                    <input type="text" class="form-control" id="usuario" name="usuario" required autofocus>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-box-arrow-in-right"></i> Ingresar</button>
                </div>
            </form>
        </div>
        
        <div class="card-footer text-center">
            <p class="mb-2 text-muted">¿Eres nuevo en el sistema?</p>
            <button type="button" class="btn btn-outline-secondary w-100" data-bs-toggle="modal" data-bs-target="#registroModal">
                <i class="bi bi-person-plus-fill"></i> Registrar nuevo Veterinario
            </button>
        </div>
    </div>

    <div class="modal fade" id="registroModal" tabindex="-1" aria-labelledby="registroModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title" id="registroModalLabel">Registro de Nuevo Veterinario</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="login.php" method="POST">
                    <div class="modal-body">
                        
                        <?php if ($registro_exito): ?>
                            <div class="alert alert-success" role="alert"><?php echo $registro_exito; ?></div>
                        <?php endif; ?>
                        <?php if ($registro_error): ?>
                            <div class="alert alert-danger" role="alert"><?php echo $registro_error; ?></div>
                        <?php endif; ?>

                        <input type="hidden" name="accion" value="registro"> 

                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre Completo</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>

                        <div class="mb-3">
                            <label for="usuario_reg" class="form-label">Usuario</label>
                            <input type="text" class="form-control" id="usuario_reg" name="usuario_reg" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="password_reg" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="password_reg" name="password_reg" required>
                        </div>
                        
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-person-check-fill"></i> Registrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($registro_exito || $registro_error): ?>
                
                var registroModal = new bootstrap.Modal(document.getElementById('registroModal'));
                registroModal.show();
                
            <?php endif; ?>
        });
    </script>
</body>
</html>
<?php

$nombre_veterinario = $_SESSION['nombre_veterinario'] ?? 'Veterinario'; 
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetCare | Clínica Veterinaria</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link rel="stylesheet" href="style.css"> 
    
    <style>
        html, body {
            transform: none !important;
            filter: none !important;
            will-change: auto !important;
            height: 100%; 
        }
        
     
        .animal-slider-fixed {
            position: relative !important; 
            bottom: 0 !important;       
            left: 0 !important;         
            width: 145% !important;
            z-index: 2 !important; 
            overflow: hidden !important; 
            white-space: nowrap !important;
            opacity: 0.15 !important;   
            background: #f4f6f9 !important; 
            pointer-events: none !important; 
            padding: 20px 0 !important; 
            margin-left: -22.5%;
            margin-top: 150px;
        }

        .animal-slider-fixed .animal-slide-track {
            display: inline-block !important; 
            animation: scrollAnimals 30s linear infinite; 
        }

        .animal-slider-fixed .animal-slide {
            display: inline-block !important; 
            width: 170px !important; 
            height: 80px !important; 
            padding: 10px !important; 
            vertical-align: middle !important;
        }

        .animal-slider-fixed .animal-slide img {
            height: 100% !important; 
            width: auto !important;   
            object-fit: contain !important; 
            filter: grayscale(100%) !important; 
        }

        @keyframes scrollAnimals {
            0% {
                transform: translateX(0);
            }
            100% {
                transform: translateX(calc(-170px * 5)); 
            }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm sticky-top">
    <div class="container-fluid">
        
        <a class="navbar-brand" href="index.php">
            <i class="bi bi-heart-pulse-fill me-2"></i> PetCare
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                
                <li class="nav-item">
                    <a class="nav-link" href="index.php">
                        <i class="bi bi-house-door-fill"></i> Inicio
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="mascotas.php">
                        <i class="bi bi-hospital-fill"></i> Pacientes
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="turnos.php">
                        <i class="bi bi-calendar3"></i> Turnero
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="clientes.php">
                        <i class="bi bi-person-circle"></i> Clientes
                    </a>
                </li>
                
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item me-3">
                    <span class="nav-link text-white-50">
                        <i class="bi bi-person-fill me-1"></i> Hola, '<?php echo htmlspecialchars($nombre_veterinario); ?>'
                    </span>
                </li>
                
                <li class="nav-item">
                    <a class="btn btn-danger" href="logout.php">
                        <i class="bi bi-box-arrow-right"></i> Salir
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container main-content">
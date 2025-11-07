<?php

/**
 * @param PDO 
 * @return array 
 */
function obtener_metricas_dashboard(PDO $pdo): array
{
    $hoy_inicio = date('Y-m-d 00:00:00');
    $hoy_fin = date('Y-m-d 23:59:59');

    $manana = date('Y-m-d', strtotime('+1 day'));
    $en_7_dias = date('Y-m-d', strtotime('+7 days'));

    $metricas = [
        'turnos_hoy' => 0,
        'pacientes_activos' => 0,
        'vacunas_proximas' => 0,
    ];

    try {
        $sql1 = "SELECT COUNT(*) FROM turnos 
                 WHERE fecha_hora BETWEEN :inicio AND :fin
                 AND estado IN ('Pendiente', 'Confirmado')";
        $stmt1 = $pdo->prepare($sql1);
        $stmt1->execute(['inicio' => $hoy_inicio, 'fin' => $hoy_fin]);
        $metricas['turnos_hoy'] = $stmt1->fetchColumn();

        $sql2 = "SELECT COUNT(*) FROM mascotas WHERE activo = 1";
        $stmt2 = $pdo->query($sql2);
        $metricas['pacientes_activos'] = $stmt2->fetchColumn();

        $sql3 = "SELECT COUNT(DISTINCT id_mascota) FROM vacunas 
                 WHERE fecha_proxima_dosis IS NOT NULL
                 AND fecha_proxima_dosis BETWEEN :manana AND :en_7_dias";
        $stmt3 = $pdo->prepare($sql3);
        $stmt3->execute(['manana' => $manana, 'en_7_dias' => $en_7_dias]);
        $metricas['vacunas_proximas'] = $stmt3->fetchColumn();
    } catch (PDOException $e) {
        error_log("Error al obtener métricas del dashboard: " . $e->getMessage());
    }

    return $metricas;
}
<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/header.php';

$sql_kpis = "
    SELECT 
        (SELECT COUNT(*) FROM alumnos WHERE estado='Activo') as total_alumnos,
        (SELECT COUNT(*) FROM alertas WHERE estado IN ('Abierta', 'En Proceso')) as alertas_activas,
        (SELECT COUNT(*) FROM alertas WHERE estado = 'Cerrada') as casos_resueltos,
        (SELECT MAX(fecha_cache_actualizacion) FROM alumnos) as ultima_actualizacion
    FROM dual";
$kpis = $conn->query($sql_kpis)->fetch_assoc();

$sql_distribucion = "
    SELECT riesgo_ia_cache, COUNT(*) as total 
    FROM alumnos 
    WHERE estado = 'Activo' 
    GROUP BY riesgo_ia_cache 
    ORDER BY total DESC";
$res_distribucion = $conn->query($sql_distribucion);

// RIESGO SILENCIOSO
// Reglas:
// 1. Probabilidad > 50%
// 2. Que el riesgo no sea Sin Riesgo
// 3. Que no tenga alerta activa (Abierta/En Proceso)
// 4. Que no tenga alerta cerrada recientemente (Periodo de gracia de 30 dias)

$sql_silent_risk = "
    SELECT a.id, a.nombre, a.apellido_paterno, a.grado, a.grupo, a.riesgo_ia_cache, a.probabilidad_ia_cache
    FROM alumnos a
    -- Join para verificar si tiene alertas activas
    LEFT JOIN alertas al_activa 
        ON a.id = al_activa.alumno_id 
        AND al_activa.estado IN ('Abierta', 'En Proceso')
    -- Join para verificar si tiene alertas cerradas RECIENTES (< 30 días)
    LEFT JOIN alertas al_reciente 
        ON a.id = al_reciente.alumno_id 
        AND al_reciente.estado = 'Cerrada' 
        AND al_reciente.fecha_cierre >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    WHERE a.estado = 'Activo' 
      AND a.probabilidad_ia_cache > 50
      AND a.riesgo_ia_cache NOT LIKE '%Sin Riesgo%'  -- <--- CORRECCIÓN 1: Excluir sanos
      AND al_activa.id IS NULL                       -- <--- Que no tenga activa
      AND al_reciente.id IS NULL                     -- <--- CORRECCIÓN 2: Respetar periodo de gracia
    ORDER BY a.probabilidad_ia_cache DESC
    LIMIT 5";
    
$res_silent = $conn->query($sql_silent_risk);

function getBadge($riesgo) {
    if (strpos($riesgo, 'TND') !== false || strpos($riesgo, 'TD') !== false) return 'bg-danger';
    if (strpos($riesgo, 'TDAH') !== false) return 'bg-warning text-dark';
    if (strpos($riesgo, 'Sin') !== false) return 'bg-success';
    return 'bg-secondary';
}
?>

<div class="container-fluid px-4">
    
    <div class="d-flex justify-content-between align-items-center mt-4 mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0">Tablero de Inteligencia Artificial</h2>
            <p class="text-muted small mb-0">Monitor predictivo de riesgo escolar.</p>
        </div>
        <div class="btn-group shadow-sm" role="group">
            <a href="../alertas/alertas_list.php" class="btn btn-outline-secondary">
                <i class="fas fa-bell me-2"></i> Gestión de Alertas
            </a>
            <a href="actualizar_cache_logic.php" class="btn btn-primary" onclick="return confirm('¿Deseas recalcular el riesgo de TODA la escuela basándote en los últimos incidentes?');">
                <i class="fas fa-sync-alt me-2"></i> Actualizar IA
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['mensaje_sistema'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm">
            <?= $_SESSION['mensaje_sistema']; unset($_SESSION['mensaje_sistema']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_sistema'])): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm">
            <?= $_SESSION['error_sistema']; unset($_SESSION['error_sistema']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small text-uppercase fw-bold mb-1">Población Activa</p>
                        <h2 class="fw-bold text-dark mb-0"><?= $kpis['total_alumnos'] ?></h2>
                    </div>
                    <div class="text-primary fs-1 opacity-25"><i class="fas fa-users"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small text-uppercase fw-bold mb-1">Casos Resueltos</p>
                        <h2 class="fw-bold text-success mb-0"><?= $kpis['casos_resueltos'] ?></h2>
                    </div>
                    <div class="text-success fs-1 opacity-25"><i class="fas fa-clipboard-check"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small text-uppercase fw-bold mb-1">Alertas Activas</p>
                        <h2 class="fw-bold text-danger mb-0"><?= $kpis['alertas_activas'] ?></h2>
                    </div>
                    <div class="text-danger fs-1 opacity-25"><i class="fas fa-exclamation-triangle"></i></div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 bg-dark text-white">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-white-50 small text-uppercase fw-bold mb-1">Último Análisis</p>
                        <h5 class="fw-bold mb-0"><?= $kpis['ultima_actualizacion'] ? date('d M, H:i', strtotime($kpis['ultima_actualizacion'])) : 'Pendiente' ?></h5>
                    </div>
                    <div class="text-white fs-1 opacity-25"><i class="fas fa-robot"></i></div>
                </div>
            </div>
        </div>
    </div>




    <div class="row">
        
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0 fw-bold text-danger"><i class="fas fa-eye me-2"></i>Riesgo Silencioso</h5>
                        <small class="text-muted">Alumnos con alta probabilidad de riesgo <strong>sin atención actual</strong>.</small>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="bg-light text-secondary small text-uppercase">
                                <tr>
                                    <th class="ps-4">Alumno</th>
                                    <th>Diagnóstico IA</th>
                                    <th>Probabilidad</th>
                                    <th class="text-end pe-4">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($res_silent->num_rows > 0): ?>
                                    <?php while($row = $res_silent->fetch_assoc()): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold text-dark"><?= $row['nombre'].' '.$row['apellido_paterno'] ?></div>
                                            <small class="text-muted"><?= $row['grado'] ?>° <?= $row['grupo'] ?></small>
                                        </td>
                                        <td><span class="badge <?= getBadge($row['riesgo_ia_cache']) ?>"><?= $row['riesgo_ia_cache'] ?></span></td>
                                        <td>
                                            <div class="d-flex align-items-center" style="width: 120px;">
                                                <div class="progress flex-grow-1 me-2" style="height: 5px;">
                                                    <div class="progress-bar bg-danger" style="width: <?= $row['probabilidad_ia_cache'] ?>%"></div>
                                                </div>
                                                <span class="fw-bold text-danger small"><?= round($row['probabilidad_ia_cache']) ?>%</span>
                                            </div>
                                        </td>
                                        <td class="text-end pe-4">
                                            <a href="../alertas/alertas_form.php?alumno_id=<?= $row['id'] ?>" class="btn btn-sm btn-outline-danger shadow-sm">
                                                <i class="fas fa-plus-circle me-1"></i> Alertar
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5 text-muted">
                                            <i class="fas fa-check-circle text-success fs-1 mb-3"></i>
                                            <p class="mb-0">Todo bajo control. No hay riesgos silenciosos detectados.</p>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h5 class="mb-0 fw-bold">Panorama Escolar</h5>
                    <small class="text-muted">Distribución total de la población segun la IA.</small>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <?php 
                        if ($res_distribucion->num_rows > 0):
                            while($d = $res_distribucion->fetch_assoc()): 
                                $porcentaje = ($d['total'] / $kpis['total_alumnos']) * 100;
                        ?>
                        <li class="list-group-item px-0 py-3 border-bottom-0">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="fw-bold text-dark">
                                    <span class="badge <?= getBadge($d['riesgo_ia_cache']) ?> me-2 rounded-pill">●</span>
                                    <?= $d['riesgo_ia_cache'] ?>
                                </span>
                                <span class="text-muted small"><?= $d['total'] ?> alumnos (<?= round($porcentaje) ?>%)</span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar <?= getBadge($d['riesgo_ia_cache']) ?>" style="width: <?= $porcentaje ?>%"></div>
                            </div>
                        </li>
                        <?php endwhile; else: ?>
                        <li class="list-group-item text-center text-muted py-5">Sin datos de análisis aún.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 bg-light mb-5">
        <div class="card-body p-4">
            <div class="row">
                <div class="col-md-1 text-center text-primary fs-2">
                    <i class="fas fa-info-circle"></i>
                </div>
                <div class="col-md-11">
                    <h5 class="fw-bold text-dark mb-2">Entendiendo la Inteligencia Artificial</h5>
                    <p class="text-muted mb-0">
                        Este sistema utiliza un modelo de aprendizaje automático (Machine Learning) que analiza el historial conductual de los últimos 12 meses.
                        Para determinar el riesgo, la IA evalúa la combinación de:
                    </p>
                    <div class="d-flex flex-wrap gap-2 mt-3">
                        <span class="badge bg-white text-secondary border px-3 py-2"><i class="fas fa-history me-1"></i> Frecuencia de incidentes</span>
                        <span class="badge bg-white text-secondary border px-3 py-2"><i class="fas fa-tachometer-alt me-1"></i> Intensidad/Gravedad</span>
                        <span class="badge bg-white text-secondary border px-3 py-2"><i class="fas fa-tag me-1"></i> Palabras Clave (Agresión, Desafío)</span>
                        <span class="badge bg-white text-secondary border px-3 py-2"><i class="fas fa-user-tag me-1"></i> Rol (Agresor/Víctima)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>



<?php
require_once __DIR__ . '/../../includes/footer.php';
$conn->close();
?>
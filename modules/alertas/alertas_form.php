<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/header.php';

$alumno_id = (int)($_GET['alumno_id'] ?? 0);
if ($alumno_id === 0) { header('Location: ../../alertas/alertas_list.php'); exit; }

// Obtener Datos Alumno
$sql = "SELECT id, nombre, apellido_paterno, apellido_materno, grado, grupo FROM alumnos WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $alumno_id);
$stmt->execute();
$alumno = $stmt->get_result()->fetch_assoc();

if (!$alumno) { die("Alumno no encontrado."); }

// VERIFICAR ALERTAS PREVIAS
// Alerta Activa
$sql_active = "SELECT id, titulo, fecha_alerta FROM alertas WHERE alumno_id = ? AND estado IN ('Abierta', 'En Proceso')";
$stmt_a = $conn->prepare($sql_active);
$stmt_a->bind_param("i", $alumno_id);
$stmt_a->execute();
$active_alert = $stmt_a->get_result()->fetch_assoc();

// Alerta Reciente Cerrada - ultimos 30 dias
$sql_recent = "SELECT id, titulo, fecha_cierre FROM alertas WHERE alumno_id = ? AND estado = 'Cerrada' AND fecha_cierre >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$stmt_r = $conn->prepare($sql_recent);
$stmt_r->bind_param("i", $alumno_id);
$stmt_r->execute();
$recent_alert = $stmt_r->get_result()->fetch_assoc();

// Lista de docentes
$docentes = $conn->query("SELECT id, nombre, apellido_paterno FROM docentes WHERE estado='Activo'");

// URL de retorno
$return = $_GET['return'] ?? '../alertas/alertas_list.php';
if (!str_starts_with($return, '../')) { $return = '../alertas/alertas_list.php'; }
?>

<div class="container mt-4 mb-5">
    
    <?php if ($active_alert): ?>
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card border-danger shadow text-center">
                <div class="card-body py-5">
                    <div class="text-danger display-1 mb-3"><i class="fas fa-exclamation-circle"></i></div>
                    <h2 class="fw-bold text-danger">¡Atención! Ya existe un caso activo</h2>
                    <p class="lead text-muted">
                        El alumno <strong><?= $alumno['nombre'] ?></strong> ya tiene una alerta en curso.<br>
                        Por política de "Caso Único", no se pueden abrir múltiples expedientes simultáneos.
                    </p>
                    <div class="alert alert-light border d-inline-block text-start mt-3">
                        <strong>Alerta Actual:</strong> <?= htmlspecialchars($active_alert['titulo']) ?><br>
                        <strong>Fecha:</strong> <?= date('d/m/Y', strtotime($active_alert['fecha_alerta'])) ?>
                    </div>
                    <div class="mt-4 gap-2">
                        <a href="<?= htmlspecialchars($return) ?>" class="btn btn-outline-secondary">Cancelar</a>
                        <a href="alerta_detalle.php?id=<?= $active_alert['id'] ?>" class="btn btn-danger px-4">
                            Ir al Caso Existente
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>

    <div class="row">
        <div class="col-lg-8">
            
            <?php if ($recent_alert): ?>
            <div class="alert alert-warning border-warning shadow-sm mb-4">
                <h5 class="alert-heading"><i class="fas fa-history"></i> Caso Reciente Detectado</h5>
                <p class="mb-0 small">
                    Este alumno tuvo un caso cerrado el <strong><?= date('d/m/Y', strtotime($recent_alert['fecha_cierre'])) ?></strong> ("<?= htmlspecialchars($recent_alert['titulo']) ?>").
                    <br>Considere si es mejor <strong><a href="alerta_detalle.php?id=<?= $recent_alert['id'] ?>" class="alert-link">reabrir ese caso</a></strong> en lugar de crear uno nuevo.
                </p>
            </div>
            <?php endif; ?>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom">
                    <h4 class="mb-0 fw-bold text-primary">Nueva Alerta de Riesgo</h4>
                    <p class="text-muted small mb-0">Reportando a: <strong><?= $alumno['nombre'].' '.$alumno['apellido_paterno'] ?></strong> (<?= $alumno['grado'] ?>° <?= $alumno['grupo'] ?>)</p>
                </div>
                <div class="card-body p-4">
                    <form action="alertas_logic.php" method="POST">
                        <input type="hidden" name="alumno_id" value="<?= $alumno_id ?>">
                        
                        <div class="mb-4">
                            <label class="form-label fw-bold">Reportado por:</label>
                            <select name="creador_id" class="form-select" required>
                                <option value="" disabled selected>Seleccione al docente...</option>
                                <?php while($doc = $docentes->fetch_assoc()): ?>
                                    <option value="<?= $doc['id'] ?>">
                                        <?= $doc['nombre'] . ' ' . $doc['apellido_paterno'] ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">1. Motivo Principal</label>
                            <input type="text" class="form-control form-control-lg" name="titulo" placeholder="Ej: Agresión física en recreo" required>
                            <div class="form-text">Título breve y descriptivo.</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">2. Nivel de Urgencia</label>
                            <div class="row g-2">
                                <div class="col-6 col-md-3">
                                    <input type="radio" class="btn-check" name="prioridad" id="p_baja" value="Baja">
                                    <label class="btn btn-outline-secondary w-100" for="p_baja">Baja</label>
                                </div>
                                <div class="col-6 col-md-3">
                                    <input type="radio" class="btn-check" name="prioridad" id="p_media" value="Media" checked>
                                    <label class="btn btn-outline-info w-100" for="p_media">Media</label>
                                </div>
                                <div class="col-6 col-md-3">
                                    <input type="radio" class="btn-check" name="prioridad" id="p_alta" value="Alta">
                                    <label class="btn btn-outline-warning w-100" for="p_alta">Alta</label>
                                </div>
                                <div class="col-6 col-md-3">
                                    <input type="radio" class="btn-check" name="prioridad" id="p_critica" value="Critica">
                                    <label class="btn btn-outline-danger w-100" for="p_critica">Crítica</label>
                                </div>
                            </div>
                            <div class="form-text mt-2 text-warning d-none" id="avisoCorreo">
                                <i class="fas fa-envelope"></i> <strong>Nota:</strong> Se enviará notificación automática a Dirección y Tutores.
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold">3. Descripción Detallada</label>
                            <textarea class="form-control" name="descripcion" rows="5" placeholder="Describa los hechos de manera objetiva (Qué, Quién, Cuándo, Dónde)..." required></textarea>
                        </div>

                        <div class="d-flex justify-content-between pt-3 border-top">
                            <a href="<?= htmlspecialchars($return) ?>" class="btn btn-light text-muted">Cancelar</a>
                            <button type="submit" class="btn btn-primary px-5 shadow-sm">Generar Alerta</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4 d-none d-lg-block">
            <div class="alert alert-info border-0 shadow-sm">
                <h5 class="alert-heading"><i class="fas fa-info-circle"></i> Guía de Prioridad</h5>
                <hr>
                <ul class="list-unstyled mb-0 small">
                    <li class="mb-2"><strong>Baja:</strong> Cambios leves de humor, rumores.</li>
                    <li class="mb-2"><strong>Media:</strong> Disrupción en clase, bajada de notas.</li>
                    <li class="mb-2"><strong>Alta:</strong> Bullying, ausentismo crónico.</li>
                    <li class="mb-0"><strong>Crítica:</strong> Riesgo físico inmediato, sustancias.</li>
                </ul>
            </div>
            
            <div class="card shadow-sm border-0 mt-3 bg-light">
                <div class="card-body">
                    <h6 class="fw-bold text-muted">Proceso Automático</h6>
                    <p class="small text-muted mb-0">
                        Al guardar, la IA analizará el historial completo del alumno para complementar este reporte con datos estadísticos.
                    </p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<script>
// Pequeño script para mostrar aviso de correo en Alta/Critica
document.querySelectorAll('input[name="prioridad"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const aviso = document.getElementById('avisoCorreo');
        if(this.value === 'Alta' || this.value === 'Critica') {
            aviso.classList.remove('d-none');
        } else {
            aviso.classList.add('d-none');
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
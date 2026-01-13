<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/header.php';

$alerta_id = (int)($_GET['id'] ?? 0);
if ($alerta_id === 0) { header('Location: alertas_list.php'); exit; }

$sql = "SELECT a.*, al.nombre, al.apellido_paterno, al.grado, al.grupo, d.nombre as creador 
        FROM alertas a JOIN alumnos al ON a.alumno_id = al.id 
        LEFT JOIN docentes d ON a.creador_id = d.id WHERE a.id = ?";
$stmt = $conn->prepare($sql); $stmt->bind_param("i", $alerta_id); $stmt->execute();
$alerta = $stmt->get_result()->fetch_assoc();

$sql_int = "SELECT i.*, d.nombre as resp FROM intervenciones i 
            LEFT JOIN docentes d ON i.responsable_id = d.id 
            WHERE i.alerta_id = ? ORDER BY i.fecha_intervencion DESC";
$stmt_i = $conn->prepare($sql_int); $stmt_i->bind_param("i", $alerta_id); $stmt_i->execute();
$intervenciones = $stmt_i->get_result();

// Lista Docentes
$docentes = $conn->query("SELECT id, nombre, apellido_paterno FROM docentes WHERE estado='Activo'");

function getBadge($t, $v) {
    if ($t == 'prioridad') return match($v) { 'Critica'=>'bg-danger', 'Alta'=>'bg-warning text-dark', default=>'bg-info text-dark' };
    if ($t == 'estado') return match($v) { 'Abierta'=>'bg-danger', 'En Proceso'=>'bg-primary', 'Cerrada'=>'bg-success', default=>'bg-secondary' };
}
?>

<div class="container mt-4 mb-5">

<div class="card shadow-sm border-0 mb-4">
        <div class="card-body py-2 d-flex justify-content-between align-items-center">
            <a href="alertas_list.php" class="btn btn-light text-muted btn-sm">
                <i class="fas fa-arrow-left"></i> Volver al listado
            </a>

            <div class="d-flex gap-2">
                <form action="alertas_acciones.php" method="POST" onsubmit="return confirm('¿Enviar reporte al tutor?');">
                    <input type="hidden" name="accion" value="enviar_reporte">
                    <input type="hidden" name="alerta_id" value="<?= $alerta['id'] ?>">
                    <button class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-envelope"></i> Enviar Expediente
                    </button>
                </form>

                <form action="alertas_acciones.php" method="POST" onsubmit="return confirm('¿⚠️ ESTÁS SEGURO? Se borrará todo el historial.');">
                    <input type="hidden" name="accion" value="eliminar_alerta">
                    <input type="hidden" name="alerta_id" value="<?= $alerta['id'] ?>">
                    <button class="btn btn-outline-danger btn-sm">
                        <i class="fas fa-trash"></i> Eliminar Caso
                    </button>
                </form>
            </div>
        </div>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= htmlspecialchars($_GET['msg']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

   

    <div class="row">
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body">
                    <h6 class="text-uppercase text-muted small fw-bold">Expediente del Alumno</h6>
                    <h3 class="fw-bold mb-0"><?php echo htmlspecialchars($alerta['nombre'] . ' ' . $alerta['apellido_paterno']); ?></h3>
                    <p class="text-muted"><?php echo $alerta['grado']; ?>° "<?php echo $alerta['grupo']; ?>"</p>
                    
                    <div class="p-3 bg-light rounded mt-3">
                        <h6 class="fw-bold small mb-2"><i class="fas fa-robot"></i> Análisis de IA</h6>
                        <?php if(strpos($alerta['riesgo_detectado'], 'Sin') === false): ?>
                            <div class="d-flex align-items-center mb-2">
                                <span class="badge bg-danger me-2">Riesgo Detectado</span>
                                <strong><?php echo $alerta['riesgo_detectado']; ?></strong>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-danger" style="width: <?php echo $alerta['probabilidad_riesgo']; ?>%"></div>
                            </div>
                            <small class="text-muted d-block mt-1">Probabilidad del modelo: <?php echo round($alerta['probabilidad_riesgo']); ?>%</small>
                            <p class="small text-muted mt-2 mb-0 border-top pt-2">
                                * Esto significa que el patrón de conducta coincide con casos históricos de este riesgo.
                            </p>
                        <?php else: ?>
                            <span class="text-success fw-bold"><i class="fas fa-check-circle"></i> Sin Riesgo Histórico</span>
                            <p class="small text-muted mt-1 mb-0">El comportamiento previo no muestra patrones de riesgo.</p>
                        <?php endif; ?>
                    </div>
                </div>
                
            </div>

            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white fw-bold py-3">Estado de la Alerta</div>
                <div class="card-body">
                    <form action="alerta_gestion_logic.php" method="POST">
                        <input type="hidden" name="alerta_id" value="<?php echo $alerta['id']; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label small text-muted">Fase Actual</label>
                            <select name="nuevo_estado" class="form-select">
                                <option value="Abierta" <?php echo $alerta['estado']=='Abierta'?'selected':''; ?>>Abierta</option>
                                <option value="En Proceso" <?php echo $alerta['estado']=='En Proceso'?'selected':''; ?>>En Proceso</option>
                                <option value="Cerrada" <?php echo $alerta['estado']=='Cerrada'?'selected':''; ?>>Cerrada (Resuelto)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small text-muted">Prioridad</label>
                            <select name="nueva_prioridad" class="form-select">
                                <option value="Baja" <?php echo $alerta['prioridad']=='Baja'?'selected':''; ?>>Baja</option>
                                <option value="Media" <?php echo $alerta['prioridad']=='Media'?'selected':''; ?>>Media</option>
                                <option value="Alta" <?php echo $alerta['prioridad']=='Alta'?'selected':''; ?>>Alta</option>
                                <option value="Critica" <?php echo $alerta['prioridad']=='Critica'?'selected':''; ?>>Crítica</option>
                            </select>
                        </div>
                        <button class="btn btn-dark w-100">Guardar Cambios</button>
                    </form>
                </div>
            </div>
        </div>

        





        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <span class="badge bg-light text-dark border mb-2">Reporte Inicial</span>
                    <h4 class="fw-bold mb-3"><?php echo htmlspecialchars($alerta['titulo']); ?></h4>
                    <p class="text-secondary fs-6" style="line-height: 1.6;">
                        <?php echo nl2br(htmlspecialchars($alerta['descripcion'])); ?>
                    </p>
                    <div class="text-muted small mt-3 border-top pt-2">
                        Reportado el <?php echo date('d/m/Y', strtotime($alerta['fecha_alerta'])); ?> 
                        por <strong><?php echo htmlspecialchars($alerta['creador'] ?? 'Sistema'); ?></strong>
                    </div>
                </div>
            </div>


            
            <h5 class="fw-bold mb-3 ms-1">Seguimiento e Intervenciones</h5>
            
            <div class="vstack gap-3 mb-4">
                <?php if($intervenciones->num_rows > 0): ?>
                    <?php while($int = $intervenciones->fetch_assoc()): ?>
                        <div class="card shadow-sm border-0 border-start border-4 border-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <h6 class="fw-bold text-primary mb-1"><?php echo $int['tipo_intervencion']; ?></h6>
                                    <small class="text-muted"><?php echo date('d M, h:i A', strtotime($int['fecha_intervencion'])); ?></small>
                                </div>
                                <p class="mb-2"><?php echo nl2br(htmlspecialchars($int['detalles'])); ?></p>
                                
                                <?php if($int['resultado_observado']): ?>
                                    <div class="bg-light p-2 rounded small">
                                        <strong>Resultado:</strong> <?php echo htmlspecialchars($int['resultado_observado']); ?>
                                    </div>
                                <?php endif; ?>
                                <small class="text-muted d-block mt-2 text-end fst-italic">
                                    — <?php echo htmlspecialchars($int['resp'] ?? 'Personal'); ?>
                                </small>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="alert alert-light text-center border-dashed">
                        No hay intervenciones registradas. ¡Inicie el seguimiento abajo!
                    </div>
                <?php endif; ?>
            </div>

            <div class="card shadow-sm border-0 bg-light">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3"><i class="fas fa-pen"></i> Agregar Nueva Intervención</h6>
                    <form action="intervencion_logic.php" method="POST">
                        <input type="hidden" name="alerta_id" value="<?php echo $alerta['id']; ?>">
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <select name="tipo_intervencion" class="form-select bg-white" required>
                                    <option value="" disabled selected>Tipo de Acción...</option>
                                    <option value="Entrevista">Entrevista con Alumno</option>
                                    <option value="Terapia">Sesión Terapéutica</option>
                                    <option value="Llamada">Comunicación con Tutor</option>
                                    <option value="Sancion">Medida Disciplinaria</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <textarea name="detalles" class="form-control bg-white" rows="3" placeholder="¿Qué se hizo? Detalles de la sesión..." required></textarea>
                        </div>
                        <div class="mb-3">
                            <textarea name="resultado_observado" class="form-control bg-white" rows="2" placeholder="Resultado inmediato u observaciones (Opcional)"></textarea>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary px-4">Registrar Acción</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
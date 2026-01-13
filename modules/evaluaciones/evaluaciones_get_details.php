<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../..//config/utils/evaluaciones_data.php'; 

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id === 0) exit("Error: ID no válido");
$DATOS_EVAL = getEvaluationFull($conn, $id); 

if (!$DATOS_EVAL || !$DATOS_EVAL['evaluacion']) exit("Evaluación no encontrada.");

$eval = $DATOS_EVAL['evaluacion'];
$alumnos_data = $DATOS_EVAL['alumnos'] ?? []; 
$categorias = $DATOS_EVAL['categorias'] ?? [];
$detonantes = $DATOS_EVAL['detonantes'] ?? [];

$gravedad_map = [
    // 'Critica' => 'bg-danger',
    // 'Grave' => 'bg-warning',
    // 'Moderada' => 'bg-info',
    // 'Leve' => 'bg-success',
];
$gravedad_color = $gravedad_map[$eval['nivel_gravedad']] ?? 'bg-secondary';
?>
<div class="row border-bottom pb-2 mb-3">
    <div class="col-12 text-center">
        <h5 class="text-primary-custom fw-bold">REPORTE DE INCIDENCIA #<?= $eval['id'] ?></h5>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <strong>DOCENTE:</strong> <?= htmlspecialchars($eval['docente'] ?? 'No especificado') ?><br>
        <strong>ASIGNATURA:</strong> <?= htmlspecialchars($eval['materia'] ?? 'No especificada') ?><br>
        <strong>LUGAR/ACTIVIDAD:</strong> <?= htmlspecialchars($eval['actividad_momento'] ?? 'N/A') ?>
     </div>
     <div class="col-md-6 text-end">
        <strong>FECHA Y HORA:</strong> <?= date('d/m/Y H:i', strtotime($eval['fecha_incidente'])) ?><br>
        <strong>GRAVEDAD:</strong> 
        <span class="badge <?= $gravedad_color ?> text-uppercase">
            <?= htmlspecialchars($eval['nivel_gravedad']) ?>
        </span><br>
        <strong>INTENSIDAD:</strong> 
        <span class="badge bg-secondary">
            <?= intval($eval['intensidad']) ?> / 5
        </span>
    </div>
</div>

<!-- no borrar -->
<hr> 

<div class="mb-3">
    <strong>ALUMNOS INVOLUCRADOS:</strong>
    <?php if (!empty($alumnos_data)): ?>
        <ul class="list-unstyled ps-3 mt-1">
            <?php foreach($alumnos_data as $a): ?>
                <li class="mb-1">
                    <i class="bi bi-person-circle text-primary-custom me-1"></i>
                    <strong><?= htmlspecialchars($a['nombre_completo']) ?></strong> 
                        (<?= htmlspecialchars($a['grado_grupo'] ?? 'N/A') ?>) - Rol: 
                    <span class="badge bg-secondary"><?= htmlspecialchars($a['rol_incidente'] ?? 'No definido') ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="text-muted fst-italic ms-3">Sin alumnos registrados.</p>
    <?php endif; ?>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <strong>CATEGORÍAS DE CONDUCTA:</strong>
        <?php if (!empty($categorias)): ?>
        <ul class="list-unstyled ps-3 mt-1 text-muted">
            <?php foreach($categorias as $o): ?>
                <li><i class="bi bi-dot text-success me-1"></i> <?= htmlspecialchars($o['nombre']) ?></li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
             <p class="text-muted fst-italic ms-3">Sin categorías seleccionadas.</p>
        <?php endif; ?>
    </div>
    <div class="col-md-6">
        <strong>DETONANTES PERCIBIDOS:</strong>
        <?php if (!empty($detonantes)): ?>
        <ul class="list-unstyled ps-3 mt-1 text-muted">
            <?php foreach($detonantes as $d): ?>
                <li><i class="bi bi-dot text-success me-1"></i> <?= htmlspecialchars($d['nombre']) ?></li>
            <?php endforeach; ?>
        </ul>
        <?php else: ?>
             <p class="text-muted fst-italic ms-3">Sin detonantes seleccionados.</p>
        <?php endif; ?>
    </div>
</div>

<hr>

<div class="mb-3">
    <strong>DESCRIPCIÓN DETALLADA DE LOS HECHOS:</strong>
    <div class="p-3 bg-light border rounded mt-1 shadow-sm">
        <?= nl2br(htmlspecialchars($eval['descripcion_hechos'])) ?>
    </div>
</div>

<div class="mb-3">
    <strong>ACCIÓN TOMADA POR EL PROFESOR:</strong>
    <div class="p-3 bg-light border rounded mt-1 shadow-sm">
        <?= nl2br(htmlspecialchars($eval['accion_tomada'])) ?>
   </div>
</div>
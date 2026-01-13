<?php
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/utils/evaluaciones_data.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : null;
$esEdicion = (bool)$id;
$CATALOGOS = getEvaluationCatalogs($conn);
$DATOS_EVAL = $esEdicion ? getEvaluationFull($conn, $id) : null;

if ($esEdicion && $DATOS_EVAL === null) {
    echo "<script>window.location.href='evaluaciones_list.php?error=notfound';</script>";
    exit;
}

$evaluacion = $DATOS_EVAL['evaluacion'] ?? null;
// Alumnos ya formateados desde la DB
$alumnosSeleccionados = $DATOS_EVAL['alumnos'] ?? []; 
$categoriasSeleccionadas = $DATOS_EVAL['categorias'] ?? [];
$detonantesSeleccionados = $DATOS_EVAL['detonantes'] ?? [];

$listaAsignaturas = $CATALOGOS['asignaturas'];
$listaDocentes = $CATALOGOS['docentes'];
$listaCategorias = $CATALOGOS['conducta_categorias'];
$listaDetonantes = $CATALOGOS['detonantes'];

// modal
$listaGrupos = $CATALOGOS['grupos'] ?? []; 
$listaTiposConducta = $CATALOGOS['conducta_categorias'] ?? []; 
$obsSel = array_column($categoriasSeleccionadas, 'id');

?>

<link rel="stylesheet" href="../../assets/css/evaluaciones.css">

<body class="d-flex flex-column min-vh-100 bg-light">
  <div class="container py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold mb-0">
            <i class="bi bi-clipboard-data me-2"></i><?= $id ? 'Editar Evaluación' : 'Nueva Evaluación' ?>
        </h3>
        <a href="evaluaciones_list.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Volver a la lista
        </a>
    </div>

    <form method="POST" action="evaluaciones_logic.php" id="formEvaluacion" class="needs-validation" novalidate>
      <?php if($id): ?>
        <input type="hidden" name="id_evaluacion" value="<?= $id ?>">
      <?php endif; ?>

      <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-end mb-2">
                        <label class="form-label mb-0 fs-5">Alumnos Involucrados</label>
                        <button type="button" class="btn btn-orange btn-sm" data-bs-toggle="modal" data-bs-target="#modalAlumnos">
                            <i class="bi bi-person-plus-fill me-1"></i> Añadir Alumnos
                        </button>
                    </div>
                    
                    <div id="alumnos-list-container" class="alumnos-list-container">
                        <div id="empty-state-alumnos" class="text-center p-4 text-muted <?= !empty($alumnosSeleccionados) ? 'd-none' : '' ?>">
                            <i class="bi bi-people fs-1 d-block mb-2"></i>
                            No hay alumnos seleccionados.
                        </div>
                        
                        <div id="alumnos-rows-wrapper">
                            <?php foreach($alumnosSeleccionados as $al): ?>
                                <div class="alumno-row d-flex align-items-center flex-wrap gap-2" id="row-alumno-<?= $al['alumno_id'] ?>">
                                    <div class="flex-grow-1">
                                        <div class="fw-bold text-dark">
                                            <?= htmlspecialchars($al['nombre_completo']) ?>
                                        </div>
                                        <small class="text-muted">
                                            <i class="bi bi-mortarboard-fill me-1"></i><?= htmlspecialchars($al['grado_grupo'] ?? 'Sin grupo') ?>
                                        </small>
                                        <input type="hidden" name="alumnos_ids[]" value="<?= $al['alumno_id'] ?>">
                                    </div>

                                    <div class="me-2" style="width: 200px;">
                                        <select name="roles[<?= $al['alumno_id'] ?>]" class="form-select form-select-sm border-primary">
                                            <?php 
                                            $roles = ['Agresor', 'Victima', 'Participante', 'Testigo'];
                                            foreach($roles as $rol): ?>
                                                <option value="<?= $rol ?>" <?= ($al['rol_incidente'] ?? 'Agresor') === $rol ? 'selected' : '' ?>>
                                                    Rol: <?= $rol ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="invalid-feedback d-block" id="error-alumnos"></div>
                </div>
            </div>

            <hr class="text-muted opacity-25 my-4">

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label">Docente que reporta</label>
                    <select name="docente_id" class="form-select" required>
                        <option value="">-- Seleccionar Docente --</option>
                        <?php foreach ($listaDocentes as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= ($evaluacion && $evaluacion['docente_id'] == $d['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Requerido.</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Asignatura</label>
                    <select name="materia_id" class="form-select" required>
                        <option value="">-- Seleccionar Asignatura --</option>
                        <?php foreach ($listaAsignaturas as $m): ?>
                            <option value="<?= $m['id'] ?>" <?= ($evaluacion && $evaluacion['materia_id'] == $m['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Lugar / Actividad</label>
                    <select name="actividad_momento" class="form-select" required>
                        <option value="">-- Seleccionar --</option>
                        <?php foreach (['Clase Magistral','Trabajo Grupo','Examen','Recreo','Transicion','Baño','Otro'] as $opt): ?>
                            <option value="<?= $opt ?>" <?= ($evaluacion && $evaluacion['actividad_momento'] == $opt) ? 'selected' : '' ?>>
                                <?= $opt ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Fecha del Incidente</label>
                    <input type="datetime-local" name="fecha_incidente" class="form-control" 
                           value="<?= $evaluacion ? date('Y-m-d\TH:i', strtotime($evaluacion['fecha_incidente'])) : date('Y-m-d\TH:i') ?>" required>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-8">
                    <label class="form-label">Categorías de Conducta (Selección múltiple)</label>
                    <div class="card bg-light border-0 p-3" style="max-height: 200px; overflow-y: auto;">
                        <?php foreach ($listaCategorias as $cat): 
                             $checked = in_array($cat['id'], array_column($categoriasSeleccionadas, 'id')); 
                        ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input chk-categoria" type="checkbox" name="categorias_ids[]" 
                                       value="<?= $cat['id'] ?>" id="cat-<?= $cat['id'] ?>" 
                                       data-desc="<?= htmlspecialchars($cat['descripcion']) ?>" 
                                       <?= $checked ? 'checked' : '' ?>>
                                <label class="form-check-label" for="cat-<?= $cat['id'] ?>">
                                    <?= htmlspecialchars($cat['nombre']) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div id="categoria-descripcion" class="form-text text-primary fst-italic mt-1" style="min-height: 20px;">
                        Selecciona o pasa el mouse sobre una categoría para ver detalles.
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Nivel de Gravedad</label>
                    <select name="nivel_gravedad" class="form-select border-warning fw-bold text-dark" required>
                        <?php foreach (['Leve','Moderada','Grave','Critica'] as $g): ?>
                            <option value="<?= $g ?>" <?= ($evaluacion && $evaluacion['nivel_gravedad'] == $g) ? 'selected' : '' ?>>
                                <?= $g ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row mb-4 align-items-center bg-white border rounded p-3 mx-0">
                <div class="col-md-8 border-end">
                    <label class="form-label">Detonantes Percibidos</label>
                    <div class="d-flex flex-wrap gap-3">
                        <?php foreach ($listaDetonantes as $det): 
                            $checked = in_array($det['id'], array_column($detonantesSeleccionados, 'id'));
                        ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="detonantes_ids[]" 
                                       value="<?= $det['id'] ?>" id="det-<?= $det['id'] ?>" <?= $checked ? 'checked' : '' ?>>
                                <label class="form-check-label" for="det-<?= $det['id'] ?>">
                                    <?= htmlspecialchars($det['nombre']) ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="col-md-4 text-center">
                    <label class="form-label d-block">Intensidad</label>
                    <div class="intensity-widget justify-content-center">
                        <button type="button" class="btn-intensity" id="btn-minus">-</button>
                        <input type="text" name="intensidad" id="input-intensidad" class="intensity-display" 
                               value="<?= $evaluacion ? intval($evaluacion['intensidad']) : 1 ?>" readonly>
                        <button type="button" class="btn-intensity" id="btn-plus">+</button>
                    </div>
                    <small class="text-muted">Escala 1 (Baja) a 5 (Alta)</small>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Descripción detallada de los hechos</label>
                    <textarea name="descripcion_hechos" class="form-control" rows="4" 
                              placeholder="Describa objetivamente qué sucedió..." required><?= $evaluacion ? htmlspecialchars($evaluacion['descripcion_hechos']) : '' ?></textarea>
                    <div class="invalid-feedback">La descripción es obligatoria.</div>
                </div>
                
                <div class="col-12">
                    <label class="form-label">Acción tomada por el profesor</label>
                    <textarea name="accion_tomada" class="form-control" rows="3" 
                              placeholder="¿Qué medidas inmediatas se tomaron?"><?= $evaluacion ? htmlspecialchars($evaluacion['accion_tomada']) : '' ?></textarea>
                </div>
            </div>

        </div> <div class="card-footer bg-light p-3 text-end">
            <button type="submit" class="btn btn-success btn-lg px-5 shadow-sm">
                <i class="bi bi-save me-2"></i>Guardar Evaluación
            </button>
        </div>
      </div>
    </form>
  </div>

<?php require_once __DIR__ . '/modales/modales_evaluaciones_form.php'; ?>

<script src="../../assets/js/evaluaciones.js"></script>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
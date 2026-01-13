<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/header.php';

$sql = "SELECT e.id, e.nivel_gravedad, e.fecha_incidente,
         GROUP_CONCAT(
             CONCAT(a.nombre, ' ', COALESCE(a.apellido_paterno, ''), ' ', COALESCE(a.apellido_materno, ''), ' (', a.grado, a.grupo, ')') 
             ORDER BY a.apellido_paterno ASC 
             SEPARATOR ' | '
         ) AS alumnos_list
         FROM evaluacion_conducta e
         LEFT JOIN evaluacion_alumnos ea ON e.id = ea.evaluacion_id
         LEFT JOIN alumnos a ON ea.alumno_id = a.id
         GROUP BY e.id
         ORDER BY e.fecha_incidente DESC, e.id DESC";

$res = $conn->query($sql);
?>

<link rel="stylesheet" href="../../assets/css/evaluaciones_list.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<div class="container mt-4 mb-5">
    <div class="row align-items-center mb-4">
        <div class="col-md-6 col-sm-12">
            <h1 class="evaluaciones-header mb-2">Evaluaciones de Conducta</h1>
            <a href="evaluaciones_form.php" class="btn btn-primary btn-custom-action"><i class="bi bi-plus-circle me-2"></i>Nueva Evaluación</a>
        </div>
        
        <div class="col-md-6 col-sm-12 mt-3 mt-md-0">
            <div class="input-group search-bar">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" class="form-control border-start-0" id="search-evaluaciones" placeholder="Buscar por Nombre, Apellidos, Grado, Grupo o Fecha...">
            </div>
        </div>
    </div>
    
    <div class="row row-header text-muted fw-bold d-none d-lg-flex py-2">
        <div class="col-lg-1 text-center">ID</div>
        <div class="col-lg-2 text-center">Gravedad</div>
        <div class="col-lg-6">Alumnos (Grado/Grupo)</div>
        <div class="col-lg-2 text-center">Fecha</div>
        <div class="col-lg-1 text-center"></div> </div>

    <div id="evaluaciones-list-container">
    <?php while ($r = $res->fetch_assoc()): 
        $id_eval = $r['id'];
        $gravedad = $r['nivel_gravedad'];
        
        $badge_class = 'bg-info text-dark';
        if ($gravedad === 'Moderada') {
            $badge_class = 'bg-warning text-dark';
        } elseif ($gravedad === 'Grave') {
            $badge_class = 'bg-danger';
        } elseif ($gravedad === 'Critica') {
            $badge_class = 'bg-critical';
        }

        $fecha_formato = date('d/m/Y', strtotime($r['fecha_incidente']));
        
        $alumnos_txt = htmlspecialchars($r['alumnos_list'] ?? 'Sin alumnos');
        
        $alumnos_array = explode(' | ', $r['alumnos_list'] ?? 'Sin alumnos');
        
        $alumnos_visibles = array_slice($alumnos_array, 0, 3);
        
        $alumnos_html = implode('<br>', array_map('htmlspecialchars', $alumnos_visibles));
        
        $count_alumnos = count($alumnos_array);
        $texto_adicional = ($count_alumnos > 3) ? '... y ' . ($count_alumnos - 3) . ' más.' : '';
        ?>
        
        <div class="card eval-card" data-id="<?= $id_eval ?>" data-alumnos="<?= $alumnos_txt ?>" data-fecha="<?= $fecha_formato ?>">
            <div class="eval-card-body row mx-0 g-0 align-items-center" data-bs-toggle="collapse" data-bs-target="#actions-<?= $id_eval ?>" aria-expanded="false" aria-controls="actions-<?= $id_eval ?>">
                
                <div class="col-1 d-none d-lg-block text-center fw-bold text-primary">#<?= $id_eval ?></div>

                <div class="col-4 col-lg-2 text-center">
                    <small class="text-muted d-block d-lg-none">Gravedad</small>
                    <span class="badge <?= $badge_class ?>"><?= ucfirst($gravedad) ?></span>
                </div>

                <div class="col-7 col-lg-6">
                    <small class="text-muted d-block d-lg-none">Alumnos Involucrados</small>
                    <div class="alumnos-list-display" title="<?= $alumnos_txt ?>">
                        <span class="fw-bold"><?= $alumnos_html ?></span>
                        <?php if ($texto_adicional): ?>
                            <small class="text-muted d-block fst-italic"><?= $texto_adicional ?></small>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-4 col-lg-2 text-center mt-2 mt-lg-0">
                    <small class="text-muted d-block d-lg-none">Fecha Incidente</small>
                    <span class="fw-bold"><?= $fecha_formato ?></span>
                </div>

                <div class="col-1 text-center">
                    <i class="bi bi-chevron-down toggle-icon text-custom-primary" id="icon-<?= $id_eval ?>"></i>
                </div>
            </div>
            
            <div class="collapse" id="actions-<?= $id_eval ?>">
                <div class="card-footer bg-light p-2 d-flex flex-wrap justify-content-end gap-2">
                    <a class="btn btn-sm btn-outline-dark btn-ver" data-id="<?= $id_eval ?>" href="#"><i class="bi bi-eye-fill me-1"></i> Visualizar</a>
                    <a class="btn btn-sm btn-outline-dark btn-enviar" data-id="<?= $id_eval ?>" href="#"><i class="bi bi-send-fill me-1"></i> Enviar Notificación</a>
                    <a class="btn btn-sm btn-outline-dark btn-imprimir-directo" data-id="<?= $id_eval ?>" href="#"><i class="bi bi-download me-1"></i> Descargar PDF</a>
                    <a class="btn btn-sm btn-custom-edit" href="evaluaciones_form.php?id=<?= $id_eval ?>"><i class="bi bi-pencil-square me-1"></i> Editar</a>
                    <a class="btn btn-sm btn-outline-danger" href="evaluaciones_delete.php?id=<?= $id_eval ?>" onclick="return confirm('¿Desea eliminar permanentemente la evaluacion #<?= $id_eval ?>?')"><i class="bi bi-trash-fill me-1"></i> Eliminar</a>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
    </div>
    
    <?php if ($res->num_rows === 0): ?>
        <div class="alert alert-info text-center mt-5" role="alert">
            No se encontraron evaluaciones de conducta.
        </div>
    <?php endif; ?>
</div>

<?php 
require_once __DIR__ . '/modales/modales_evaluaciones_list.php'; 
?>

<script src="../../assets/js/evaluaciones_list.js"></script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
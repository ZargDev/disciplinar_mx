<?php
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-5">
    <h1>Panel de Control DisciplinarMX</h1>
    <div class="row gy-4">

    <div class="col-md-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Evaluaciones/Reportes</h5>
          <a href="/disciplinar_mx/modules/evaluaciones/evaluaciones_list.php" class="btn btn-primary">Ver Evaluaciones</a>
          <a href="/disciplinar_mx/modules/evaluaciones/evaluaciones_form.php" class="btn btn-secondary">Nueva Evaluación</a>
        </div>
      </div>
    </div>

    <div class="col-md-4">
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Maching Learning</h5>
          <a href="/disciplinar_mx/modules/riesgo_predictivo/dashboard.php" class="btn btn-primary">Ver dashboard</a>
        </div>
      </div>
    </div>

  </div>
</div>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>

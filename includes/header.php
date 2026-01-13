<!DOCTYPE html>
<html lang="es" data-bs-theme="auto">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DisciplinarMX</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="../assets/css/global.css" rel="stylesheet">
</head>
<body>
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container-fluid">
    <a class="navbar-brand" href="/disciplinar_mx/public/index.php">DisciplinarMX</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
      aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item"><a class="nav-link" href="/disciplinar_mx/modules/alumnos/alumnos_list.php">Alumnos</a></li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" id="evaluacionesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            Evaluaciones
          </a>
          <ul class="dropdown-menu" aria-labelledby="evaluacionesDropdown">
            <li><a class="dropdown-item" href="/disciplinar_mx/modules/conducta/conducta_form.php">Nueva Evaluación</a></li>
            <li><a class="dropdown-item" href="/disciplinar_mx/modules/conducta/conducta_list.php">Ver Evaluaciones</a></li>
          </ul>
        </li>
        <li class="nav-item"><a class="nav-link" href="/disciplinar_mx/modules/notificaciones/historial.php">Notificaciones</a></li>
        <li class="nav-item"><a class="nav-link" href="/disciplinar_mx/modules/alertas/alertas_list.php">Alertas</a></li>
        <li class="nav-item"><a class="nav-link" href="/disciplinar_mx/modules/">IA</a></li>
      </ul>
    </div>
    <!-- <div class="form-check form-switch ms-auto">
      <input class="form-check-input" type="checkbox" id="themeSwitch">
      <label class="form-check-label" for="themeSwitch">Modo oscuro</label>
    </div> -->
  </div>
</nav>

  <div class="container mt-4">
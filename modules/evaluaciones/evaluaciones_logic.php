<?php
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: evaluaciones_list.php");
    exit;
}

$id_evaluacion = isset($_POST['id_evaluacion']) ? intval($_POST['id_evaluacion']) : null;

// Recibir Datos
$alumnos_ids = $_POST['alumnos_ids'] ?? []; 
$roles = $_POST['roles'] ?? []; 
$categorias = $_POST['categorias_ids'] ?? [];
$detonantes = $_POST['detonantes_ids'] ?? [];

// Mapeo de nombres del formulario a la db
$asignatura_id = !empty($_POST['materia_id']) ? intval($_POST['materia_id']) : null;
$docente_id    = !empty($_POST['docente_id']) ? intval($_POST['docente_id']) : null;

$gravedad      = $_POST['nivel_gravedad'] ?? 'Leve';
$intensidad    = isset($_POST['intensidad']) ? intval($_POST['intensidad']) : 1;
// Formato fecha
$fecha_raw     = $_POST['fecha_incidente'] ?? date('Y-m-d H:i');
$fecha         = date('Y-m-d H:i:s', strtotime($fecha_raw));

$actividad     = $_POST['actividad_momento'] ?? 'Otro';
$descripcion   = $conn->real_escape_string($_POST['descripcion_hechos'] ?? '');
$accion        = $conn->real_escape_string($_POST['accion_tomada'] ?? '');

if (empty($alumnos_ids)) {
    die("Error: Debes seleccionar al menos un alumno.");
}

// tabla main
if ($id_evaluacion) {
    $sql = "UPDATE evaluacion_conducta SET 
            docente_id=?, asignatura_id=?, fecha_incidente=?, actividad_momento=?, 
            nivel_gravedad=?, intensidad=?, descripcion_hechos=?, accion_tomada=?, updated_at=NOW()
            WHERE id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisssissi", $docente_id, $asignatura_id, $fecha, $actividad, $gravedad, $intensidad, $descripcion, $accion, $id_evaluacion);
    $stmt->execute();
    
    // Limpieza de relaciones previas
    $conn->query("DELETE FROM evaluacion_alumnos WHERE evaluacion_id = $id_evaluacion");
    $conn->query("DELETE FROM evaluacion_categorias WHERE evaluacion_id = $id_evaluacion");
    $conn->query("DELETE FROM evaluacion_detonantes WHERE evaluacion_id = $id_evaluacion");
    
    $eval_id = $id_evaluacion;
} else {
    $sql = "INSERT INTO evaluacion_conducta 
            (docente_id, asignatura_id, fecha_incidente, actividad_momento, nivel_gravedad, intensidad, descripcion_hechos, accion_tomada) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisssiss", $docente_id, $asignatura_id, $fecha, $actividad, $gravedad, $intensidad, $descripcion, $accion);
    $stmt->execute();
    $eval_id = $stmt->insert_id;
}

// Insertar Alumnos
$stmt_al = $conn->prepare("INSERT INTO evaluacion_alumnos (evaluacion_id, alumno_id, rol_incidente) VALUES (?, ?, ?)");
foreach ($alumnos_ids as $aid) {
    $aid = intval($aid);
    $rol = isset($roles[$aid]) ? $roles[$aid] : 'Agresor';
    $stmt_al->bind_param("iis", $eval_id, $aid, $rol);
    $stmt_al->execute();
}

// Insertar Categorias
if (!empty($categorias)) {
    $stmt_cat = $conn->prepare("INSERT INTO evaluacion_categorias (evaluacion_id, categoria_id) VALUES (?, ?)");
    foreach ($categorias as $cid) {
        $cid = intval($cid);
        $stmt_cat->bind_param("ii", $eval_id, $cid);
        $stmt_cat->execute();
    }
}

// Insertar Detonantes
if (!empty($detonantes)) {
    $stmt_det = $conn->prepare("INSERT INTO evaluacion_detonantes (evaluacion_id, detonante_id) VALUES (?, ?)");
    foreach ($detonantes as $did) {
        $did = intval($did);
        $stmt_det->bind_param("ii", $eval_id, $did);
        $stmt_det->execute();
    }
}

header("Location: evaluaciones_list.php?msg=saved");
exit;
?>
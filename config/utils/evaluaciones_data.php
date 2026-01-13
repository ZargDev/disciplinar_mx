<?php
function getEvaluationCatalogs($conn) {
    $catalogs = [];
    $catalogs['asignaturas'] = $conn->query("SELECT id, nombre FROM asignaturas ORDER BY nombre ASC")->fetch_all(MYSQLI_ASSOC);
    $catalogs['docentes'] = $conn->query("SELECT id, CONCAT(nombre, ' ', apellido_paterno, ' ', apellido_materno) as nombre FROM docentes WHERE estado = 'Activo' ORDER BY nombre ASC")->fetch_all(MYSQLI_ASSOC);
    $catalogs['conducta_categorias'] = $conn->query("SELECT id, nombre, descripcion FROM categorias_conducta ORDER BY nombre ASC")->fetch_all(MYSQLI_ASSOC);
    $catalogs['detonantes'] = $conn->query("SELECT id, nombre FROM detonantes_conducta ORDER BY nombre ASC")->fetch_all(MYSQLI_ASSOC);
    return $catalogs;
}

function getEvaluationFull($conn, $id) {
    if (!$id) return null;

    $data = [
        'evaluacion' => null, 
        'alumnos' => [], 
        'categorias' => [],
        'detonantes' => [] 
    ];

    // 1. Datos Principales
    $stmt = $conn->prepare("
        SELECT ec.*, 
               a.nombre as materia, 
               CONCAT(d.nombre, ' ', d.apellido_paterno, ' ', d.apellido_materno) as docente,
               ec.asignatura_id as materia_id 
        FROM evaluacion_conducta ec
        LEFT JOIN asignaturas a ON ec.asignatura_id = a.id
        LEFT JOIN docentes d ON ec.docente_id = d.id
        WHERE ec.id = ?
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data['evaluacion'] = $stmt->get_result()->fetch_assoc();

    if (!$data['evaluacion']) return null;

    // 2. Alumnos
    $sqlAl = "SELECT ea.alumno_id, ea.rol_incidente,
                     CONCAT(a.nombre, ' ', a.apellido_paterno, ' ', COALESCE(a.apellido_materno,'')) as nombre_completo,
                     CONCAT(a.grado, ' ', a.grupo) as grado_grupo
              FROM evaluacion_alumnos ea 
              JOIN alumnos a ON ea.alumno_id = a.id 
              WHERE ea.evaluacion_id = ?";
    $stmtAl = $conn->prepare($sqlAl);
    $stmtAl->bind_param("i", $id);
    $stmtAl->execute();
    $data['alumnos'] = $stmtAl->get_result()->fetch_all(MYSQLI_ASSOC);

    // 3. Categorias
    $sqlCat = "SELECT cc.id, cc.nombre, cc.descripcion
               FROM evaluacion_categorias ec
               JOIN categorias_conducta cc ON ec.categoria_id = cc.id
               WHERE ec.evaluacion_id = ?";
    $stmtCat = $conn->prepare($sqlCat);
    $stmtCat->bind_param("i", $id);
    $stmtCat->execute();
    $data['categorias'] = $stmtCat->get_result()->fetch_all(MYSQLI_ASSOC);

    // Detonantes
    $sqlDet = "SELECT dc.id, dc.nombre
               FROM evaluacion_detonantes ed
               JOIN detonantes_conducta dc ON ed.detonante_id = dc.id
               WHERE ed.evaluacion_id = ?";
    $stmtDet = $conn->prepare($sqlDet);
    $stmtDet->bind_param("i", $id);
    $stmtDet->execute();
    $data['detonantes'] = $stmtDet->get_result()->fetch_all(MYSQLI_ASSOC);
    
    return $data;
}
?>
<?php
require_once __DIR__ . '/../../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$filtro = isset($data['filtro']) ? $conn->real_escape_string($data['filtro']) : '';
$grado_grupo = isset($data['grupo']) ? $conn->real_escape_string($data['grupo']) : ''; 

$sql = "SELECT 
            id, 
            nombre, 
            CONCAT(COALESCE(apellido_paterno, ''), ' ', COALESCE(apellido_materno, '')) AS apellidos,
            CONCAT(nombre, ' ', COALESCE(apellido_paterno, ''), ' ', COALESCE(apellido_materno, '')) AS nombre_completo,
            CONCAT(grado, grupo) AS grado_grupo,
            'Agresor' AS rol_incidente 
        FROM alumnos 
        WHERE 1=1";

if ($grado_grupo !== '') {
    $sql .= " AND CONCAT(grado, grupo) = '$grado_grupo'";
}

// Logica de filtrado de texto
if ($filtro !== '') {
    $sql .= " AND (
        nombre LIKE '%$filtro%' OR 
        apellido_paterno LIKE '%$filtro%' OR 
        apellido_materno LIKE '%$filtro%' OR
        REPLACE(CONCAT(nombre, IFNULL(apellido_paterno,''), IFNULL(apellido_materno,'')), ' ', '') LIKE '%" . str_replace(' ', '', $filtro) . "%'
    )";
}

$sql .= " ORDER BY nombre ASC LIMIT 50";

$res = $conn->query($sql);
$alumnos = [];

while ($r = $res->fetch_assoc()) {
    $r['apellidos'] = trim($r['apellidos']); 
    $alumnos[] = $r;
}

header('Content-Type: application/json');
echo json_encode($alumnos);
?>
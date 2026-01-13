<?php
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');

// Recibir filtros
$busqueda = $_GET['busqueda'] ?? '';
$grupo = $_GET['grupo'] ?? '';

// Consulta base
$sql = "SELECT id, nombre, apellido_paterno, apellido_materno, grado, grupo, riesgo_ia_cache, probabilidad_ia_cache, fecha_cache_actualizacion, estado 
        FROM alumnos 
        WHERE estado != 'Baja'";

$params = [];
$types = "";

// Filtro de Texto
if (!empty($busqueda)) {
    $sql .= " AND (nombre LIKE ? OR apellido_paterno LIKE ? OR apellido_materno LIKE ?)";
    $term = "%$busqueda%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $types .= "sss";
}

// Filtro de Grupo
if (!empty($grupo) && $grupo !== 'Todos') {
    $sql .= " AND grupo = ?";
    $params[] = $grupo;
    $types .= "s";
}

$sql .= " ORDER BY grado ASC, grupo ASC, apellido_paterno ASC LIMIT 50";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$alumnos = [];
while ($row = $result->fetch_assoc()) {
    $alumnos[] = $row;
}

echo json_encode($alumnos);
$conn->close();
?>
<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

// Parametros buscador y filtros
$busqueda = $_GET['q'] ?? '';
$grado    = $_GET['grado'] ?? '';
$grupo    = $_GET['grupo'] ?? '';
$riesgo   = $_GET['riesgo'] ?? '';
$sort_by  = $_GET['sort'] ?? 'apellido_paterno';
$order    = $_GET['order'] ?? 'ASC';
$page     = (int)($_GET['page'] ?? 1);
$limit    = 10;
$offset   = ($page - 1) * $limit;

$allowed = ['nombre', 'apellido_paterno', 'grado', 'grupo', 'riesgo_ia_cache'];
if (!in_array($sort_by, $allowed)) $sort_by = 'apellido_paterno';
$order = ($order === 'DESC') ? 'DESC' : 'ASC';

// Estructura de filtros
$where = "estado = 'Activo'";
$params = [];
$types = "";

// -Busqueda Texto (Aquí SÍ usamos LIKE porque es búsqueda parcial)
if ($busqueda) {
    $where .= " AND (nombre LIKE ? OR apellido_paterno LIKE ?)";
    $term = "%$busqueda%";
    $params[] = $term; $params[] = $term; 
    $types .= "ss";
}

// -Filtro Grado
if ($grado && $grado !== 'Todos') {
    $where .= " AND grado = ?";
    $params[] = $grado;
    $types .= "s";
}

// -Filtro Grupo
if ($grupo && $grupo !== 'Todos') {
    $where .= " AND grupo = ?";
    $params[] = $grupo;
    $types .= "s";
}

// -Filtro Riesgo IA (modificado revisar)
if ($riesgo && $riesgo !== 'Todos') {
    if ($riesgo === 'ConRiesgo') {
        $where .= " AND riesgo_ia_cache NOT LIKE '%Sin Riesgo%'";
    } else {
        $where .= " AND riesgo_ia_cache = ?";
        $params[] = $riesgo; 
        $types .= "s";
    }
}

// Ejecucion
$sqlCount = "SELECT COUNT(*) as total FROM alumnos WHERE $where";
$stmt = $conn->prepare($sqlCount);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$totalRows = $stmt->get_result()->fetch_assoc()['total'];

$sqlData = "SELECT id, nombre, apellido_paterno, apellido_materno, grado, grupo, riesgo_ia_cache, probabilidad_ia_cache 
            FROM alumnos WHERE $where 
            ORDER BY $sort_by $order 
            LIMIT ? OFFSET ?";
$params[] = $limit; 
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sqlData);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) { $data[] = $row; }

echo json_encode([
    'data' => $data,
    'total' => $totalRows,
    'page' => $page,
    'pages' => ceil($totalRows / $limit)
]);
?>
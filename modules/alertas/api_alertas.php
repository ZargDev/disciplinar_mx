<?php
require_once __DIR__ . '/../../config/db.php';

header('Content-Type: application/json');
$texto     = $_GET['texto'] ?? '';
$estado    = $_GET['estado'] ?? '';
$prioridad = $_GET['prioridad'] ?? '';
$sort      = $_GET['sort'] ?? 'fecha';
$order     = $_GET['order'] ?? 'DESC';
$page      = (int)($_GET['page'] ?? 1);
$limit     = 10;                       
$offset    = ($page - 1) * $limit;

// Configurar ordenamiento y filtros
$columnas_validas = [
    'alumno'    => 'al.apellido_paterno',
    'estado'    => 'a.estado',
    'riesgo' => 'a.riesgo_detectado',
    'fecha'     => 'a.fecha_alerta',
    'prioridad' => "FIELD(a.prioridad, 'Baja', 'Media', 'Alta', 'Critica')"
];
$columna_sql = $columnas_validas[$sort] ?? 'a.fecha_alerta';
$direccion_sql = ($order === 'ASC') ? 'ASC' : 'DESC';

$where = "1=1";
$params = [];
$types = "";

if (!empty($texto)) {
    $where .= " AND (al.nombre LIKE ? OR al.apellido_paterno LIKE ? OR a.titulo LIKE ?)";
    $term = "%$texto%";
    $params[] = $term; $params[] = $term; $params[] = $term;
    $types .= "sss";
}
if (!empty($estado) && $estado !== 'Todos') {
    $where .= " AND a.estado = ?";
    $params[] = $estado;
    $types .= "s";
}
if (!empty($prioridad) && $prioridad !== 'Todas') {
    $where .= " AND a.prioridad = ?";
    $params[] = $prioridad;
    $types .= "s";
}

// Contar el total de resultados
$sqlCount = "
    SELECT COUNT(*) as total 
    FROM alertas a
    JOIN alumnos al ON a.alumno_id = al.id
    WHERE $where
";
$stmt = $conn->prepare($sqlCount);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$totalRows = $stmt->get_result()->fetch_assoc()['total'];

// Obtener los datos paginados
$sqlData = "
    SELECT 
        a.id, a.titulo, a.fecha_alerta, a.riesgo_detectado, 
        a.estado, a.prioridad,
        al.nombre, al.apellido_paterno, al.grupo
    FROM alertas a
    JOIN alumnos al ON a.alumno_id = al.id
    LEFT JOIN docentes d ON a.creador_id = d.id
    WHERE $where
    ORDER BY $columna_sql $direccion_sql
    LIMIT ? OFFSET ?
";

$params[] = $limit;
$params[] = $offset;
$types .= "ii";

$stmt = $conn->prepare($sqlData);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

$alertas = [];
while ($row = $result->fetch_assoc()) {
    $alertas[] = $row;
}

// Devolver estructura completa
echo json_encode([
    'data' => $alertas,
    'total' => $totalRows,
    'page' => $page,
    'pages' => ceil($totalRows / $limit)
]);

$conn->close();
?>
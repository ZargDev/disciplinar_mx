<?php
require_once __DIR__ . '/../../config/db.php';
header('Content-Type: application/json');

$q = $_GET['q'] ?? '';
if(strlen($q) < 2) { echo json_encode([]); exit; } // Mínimo 2 letras

$term = "%$q%";
$sql = "SELECT id, nombre, apellido_paterno, apellido_materno, grado, grupo, riesgo_ia_cache 
        FROM alumnos WHERE nombre LIKE ? OR apellido_paterno LIKE ? LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $term, $term);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while($row = $res->fetch_assoc()) { $data[] = $row; }
echo json_encode($data);
?>
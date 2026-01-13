<?php
require_once __DIR__ . '/../config/db.php';
header('Content-Type: application/json');

$accion = $_GET['accion'] ?? '';

$data = [];

if ($accion === 'get_grados') {
    // Obtener grados únicos
    $sql = "SELECT DISTINCT grado FROM alumnos WHERE estado='Activo' ORDER BY grado ASC";
    $res = $conn->query($sql);
    while($row = $res->fetch_assoc()) { $data[] = $row['grado']; }

} elseif ($accion === 'get_grupos') {
    // Obtener grupos forzosamente en base a grado.
    $grado = $_GET['grado'] ?? '';
    if ($grado) {
        $stmt = $conn->prepare("SELECT DISTINCT grupo FROM alumnos WHERE estado='Activo' AND grado = ? ORDER BY grupo ASC");
        $stmt->bind_param("s", $grado);
        $stmt->execute();
        $res = $stmt->get_result();
        while($row = $res->fetch_assoc()) { $data[] = $row['grupo']; }
    }
}

echo json_encode($data);
?>
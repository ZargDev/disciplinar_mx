<?php
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['alerta_id'])) {
    header('Location: alertas_list.php');
    exit;
}

$alerta_id = (int)$_POST['alerta_id'];
$nuevo_estado = $_POST['nuevo_estado'] ?? null;
$nueva_prioridad = $_POST['nueva_prioridad'] ?? null;
$fecha_cierre = ($nuevo_estado === 'Cerrada') ? date('Y-m-d') : null;

$sql = "UPDATE alertas SET estado = ?, prioridad = ?, fecha_cierre = ? WHERE id = ?";
$stmt = $conn->prepare($sql);

// Manejar la fecha de cierre si el estado es 'Cerrada'
if ($nuevo_estado === 'Cerrada') {
    $stmt->bind_param("sssi", $nuevo_estado, $nueva_prioridad, $fecha_cierre, $alerta_id);
} else {
    $fecha_cierre_null = NULL;
    $stmt->bind_param("sssi", $nuevo_estado, $nueva_prioridad, $fecha_cierre_null, $alerta_id);
}

if ($stmt->execute()) {
    header('Location: alerta_detalle.php?id=' . $alerta_id . '&success=gestion_updated');
} else {
    die('Error al actualizar la gestión de la alerta: ' . $stmt->error);
}
$stmt->close();
$conn->close();
?>
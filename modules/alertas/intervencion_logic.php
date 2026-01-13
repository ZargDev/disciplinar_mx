<?php
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: alertas_list.php');
    exit;
}

// Validar datos
if (empty($_POST['alerta_id']) || empty($_POST['detalles'])) {
    header('Location: alertas_list.php?error=empty_fields');
    exit;
}

$alerta_id = (int)$_POST['alerta_id'];
$responsable_id = (int)($_POST['responsable_id'] ?? 1); // Default
$tipo_intervencion = $_POST['tipo_intervencion'];
$detalles = $_POST['detalles'];
$resultado_observado = $_POST['resultado_observado'] ?? '';

// Insertar intervencion
$sql = "INSERT INTO intervenciones (alerta_id, responsable_id, tipo_intervencion, detalles, resultado_observado) 
        VALUES (?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iisss", $alerta_id, $responsable_id, $tipo_intervencion, $detalles, $resultado_observado);

if ($stmt->execute()) {
    header('Location: alerta_detalle.php?id=' . $alerta_id . '&success=intervention_added');
} else {
    die("Error al guardar: " . $stmt->error);
}

$stmt->close();
$conn->close();
?>
<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/utils/mailer.php'; 

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['alerta_id'])) {
    header('Location: alertas_list.php'); exit;
}

$id = (int)$_POST['alerta_id'];

// Obtener Datos Completos
$sql = "SELECT a.*, al.nombre, al.apellido_paterno, al.grupo, al.grado, t.email as tutor_email, t.nombre as tutor_nombre 
        FROM alertas a 
        JOIN alumnos al ON a.alumno_id = al.id
        JOIN tutores t ON al.tutor_principal_id = t.id
        WHERE a.id = ?";
$stmt = $conn->prepare($sql); $stmt->bind_param("i", $id); $stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data || empty($data['tutor_email'])) {
    header("Location: alerta_detalle.php?id=$id&msg=Error: Tutor sin correo"); exit;
}

// Obtener Intervenciones
$sql_int = "SELECT i.*, d.nombre as resp FROM intervenciones i 
            LEFT JOIN docentes d ON i.responsable_id = d.id 
            WHERE i.alerta_id = ? ORDER BY i.fecha_intervencion ASC";
$stmt_i = $conn->prepare($sql_int); $stmt_i->bind_param("i", $id); $stmt_i->execute();
$ints = $stmt_i->get_result();

// Construir HTML del Reporte
$html = "
    <h2>Expediente de Seguimiento Escolar</h2>
    <p>Estimado/a {$data['tutor_nombre']}, se adjunta el detalle del caso de <strong>{$data['nombre']} {$data['apellido_paterno']}</strong>.</p>
    
    <div style='background:#f4f4f4; padding:15px; border-radius:5px; margin-bottom:20px;'>
        <h3 style='margin-top:0;'>Reporte Inicial (#{$data['id']})</h3>
        <p><strong>Fecha:</strong> ".date('d/m/Y', strtotime($data['fecha_alerta']))."</p>
        <p><strong>Motivo:</strong> {$data['titulo']}</p>
        <p><strong>Detalle:</strong><br>".nl2br($data['descripcion'])."</p>
    </div>

    <h3>Historial de Intervenciones y Seguimiento</h3>
    <table border='1' cellpadding='10' cellspacing='0' style='width:100%; border-collapse:collapse;'>
        <tr style='background:#ddd;'>
            <th>Fecha</th>
            <th>Acción</th>
            <th>Detalles</th>
            <th>Resultado</th>
        </tr>
";

while($row = $ints->fetch_assoc()) {
    $fecha = date('d/m/Y', strtotime($row['fecha_intervencion']));
    $html .= "
        <tr>
            <td>{$fecha}</td>
            <td>{$row['tipo_intervencion']}</td>
            <td>".nl2br($row['detalles'])."</td>
            <td>".nl2br($row['resultado_observado'])."</td>
        </tr>
    ";
}
$html .= "</table><p><small>Generado automáticamente por el Sistema Disciplinar.</small></p>";

?>
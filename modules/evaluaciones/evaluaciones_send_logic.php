<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/utils/evaluaciones_data.php'; 
require_once __DIR__ . '/../../config/utils/mailer.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;

if (!$id) { 
    $response['message'] = "ID de evaluación inválido."; 
    echo json_encode($response); 
    exit; 
}

$eval = getEvaluationFull($conn, $id);

if (!$eval) { 
    $response['message'] = "Evaluación no encontrada."; 
    echo json_encode($response); 
    exit; 
}

$stmtAl = $conn->prepare("SELECT a.nombre, t.correo as email_tutor 
                         FROM evaluacion_alumno ea
                         JOIN alumnos a ON ea.alumno_id = a.id
                         JOIN tutores t ON t.alumno_id = a.id
                         WHERE ea.evaluacion_id = ? AND t.correo IS NOT NULL AND t.correo != ''");
$stmtAl->bind_param("i", $id);
$stmtAl->execute();
$resAl = $stmtAl->get_result();


if ($resAl->num_rows == 0) {
    $response['message'] = "No hay correos de tutores destinatarios.";
    echo json_encode($response); 
    exit;
}

$body = "
<html>
<head>
<style>
  .contenedor { font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #ddd; padding: 20px; }
  .header { background: #f8f9fa; padding: 10px; text-align: center; font-weight: bold; }
  .info { margin-top: 15px; }
  .desc { background: #fff3cd; padding: 15px; border-left: 5px solid #ffc107; margin: 15px 0; }
  .footer { font-size: 12px; color: #777; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px; }
</style>
</head>
<body>
<div class='contenedor'>
  <div class='header'>Notificación de Conducta Escolar</div>
 
  <p>Estimado Padre/Tutor,</p>
  <p>Se ha generado un reporte de conducta relacionado con su hijo(a).</p>
 
  <div class='info'>
    <strong>Fecha:</strong> ".date('d/m/Y', strtotime($eval['fecha_conducta']))."<br>
    <strong>Materia:</strong> ".($eval['materia'] ?? 'N/A')."<br>
    <strong>Docente:</strong> ".($eval['docente'] ?? 'N/A')."<br>
    <strong>Nivel de Gravedad:</strong> ".strtoupper($eval['gravedad'])."
  </div>

  <p><strong>Descripción de los hechos:</strong></p>
  <div class='desc'>
    ".nl2br(htmlspecialchars($eval['descripcion_conducta']))."
  </div>

  <p>Por favor, contacte con la coordinación académica si requiere más información.</p>

  <div class='footer'>
    Este es un mensaje automático del Sistema de Gestión Escolar.
   <br><em>Nota: Para descargar el PDF oficial, solicítelo en administración.</em>
  </div>
</div>
</body>
</html>
";

$enviados = 0;
while($a = $resAl->fetch_assoc()) {
    $para = $a['email_tutor'];
    $asunto = "Reporte de Conducta - Fecha: " . date('d/m/Y', strtotime($eval['fecha_conducta']));
    
    if(sendEmail($para, "Tutor", $asunto, $body)) {
        $enviados++;
    } else {
    }
}

if ($enviados > 0) {
    $response['success'] = true;
    $response['message'] = "Correos enviados con éxito a {$enviados} destinatario(s).";
} else {
    $response['message'] = "La operación terminó sin enviar correos. Verifique la configuración de sendEmail().";
}

echo json_encode($response);
exit;
?>
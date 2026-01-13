<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/utils/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: alertas_list.php'); exit;
}

$accion = $_POST['accion'] ?? '';
$alerta_id = (int)($_POST['alerta_id'] ?? 0);

switch ($accion) {
    
    // ELIMINAR ALERTA COMPLETA
    case 'eliminar_alerta':
        if ($alerta_id > 0) {
            $conn->query("DELETE FROM intervenciones WHERE alerta_id = $alerta_id");
            $conn->query("DELETE FROM alertas WHERE id = $alerta_id");
        }
        header('Location: alertas_list.php?msg=Alerta eliminada');
        break;

    // ELIMINAR UNA INTERVENCION
    case 'eliminar_intervencion':
        $int_id = (int)$_POST['intervencion_id'];
        if ($int_id > 0) {
            $conn->query("DELETE FROM intervenciones WHERE id = $int_id");
        }
        header("Location: alerta_detalle.php?id=$alerta_id&msg=Intervención borrada");
        break;

    // ENVIAR REPORTE POR CORREO
    case 'enviar_reporte':
        // Obtener datos
        $sql = "SELECT a.*, al.nombre, al.apellido_paterno, t.email, t.nombre as t_nom 
                FROM alertas a JOIN alumnos al ON a.alumno_id = al.id 
                JOIN tutores t ON al.tutor_principal_id = t.id WHERE a.id = $alerta_id";
        $data = $conn->query($sql)->fetch_assoc();

        if ($data && $data['email']) {
            // HTML simple
            $cuerpo = "<h2>Expediente: {$data['nombre']} {$data['apellido_paterno']}</h2>";
            $cuerpo .= "<p><strong>Reporte:</strong> {$data['titulo']}</p>";
            $cuerpo .= "<p><strong>Descripción:</strong><br>{$data['descripcion']}</p>";
            $cuerpo .= "<hr><h3>Historial de Intervenciones</h3><ul>";
            
            $ints = $conn->query("SELECT * FROM intervenciones WHERE alerta_id = $alerta_id ORDER BY fecha_intervencion ASC");
            while($i = $ints->fetch_assoc()) {
                $cuerpo .= "<li><strong>{$i['tipo_intervencion']}</strong>: {$i['detalles']}</li>";
            }
            $cuerpo .= "</ul>";

            // Enviar
            sendEmail($data['email'], $data['t_nom'], "Expediente Completo: {$data['nombre']}", $cuerpo);
            header("Location: alerta_detalle.php?id=$alerta_id&msg=Correo enviado exitosamente");
        } else {
            header("Location: alerta_detalle.php?id=$alerta_id&msg=Error: Tutor sin correo");
        }
        break;

    default:
        header('Location: alertas_list.php');
}
?>
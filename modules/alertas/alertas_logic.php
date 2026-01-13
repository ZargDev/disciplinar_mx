<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../config/utils/ai_service_utils.php';
require_once __DIR__ . '/../../config/utils/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: alertas_list.php');
    exit;
}

if (!isset($_POST['alumno_id'], $_POST['titulo'], $_POST['descripcion'], $_POST['creador_id'])) {
    // Manejo básico de error de datos faltantes
    header('Location: ../alumnos/alumnos_list.php?error=data_missing');
    exit;
}

$alumno_id = (int)$_POST['alumno_id'];
$creador_id = (int)$_POST['creador_id'];
$titulo = $_POST['titulo'];
$descripcion = $_POST['descripcion'];
$prioridad = $_POST['prioridad'] ?? 'Media';
$tipo_alerta = 'Manual';

// Obtener la prediccion de riesgo de la IA
$resultado_ia = obtener_riesgo_predictivo_ia($alumno_id);

if ($resultado_ia['success']) {
    $riesgo_detectado = $resultado_ia['riesgo'];
    $probabilidad_riesgo = $resultado_ia['probabilidad'];
} else {
    // Si falla la IA se asigna un valor seguro
    $riesgo_detectado = 'Error IA';
    $probabilidad_riesgo = 0.00;
}

// 2. Guardar alerta en db
$sql = "INSERT INTO alertas (alumno_id, creador_id, tipo_alerta, riesgo_detectado, probabilidad_riesgo, prioridad, titulo, descripcion) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die('Error al preparar la consulta: ' . $conn->error);
}

$stmt->bind_param("iissssss", 
    $alumno_id, 
    $creador_id, 
    $tipo_alerta, 
    $riesgo_detectado, 
    $probabilidad_riesgo, 
    $prioridad, 
    $titulo, 
    $descripcion
);

if ($stmt->execute()) {
    // LOGICA DE CORREO
    if ($prioridad == 'Alta' || $prioridad == 'Critica') {
        $sql_tutor = "SELECT t.email, t.nombre, t.apellido_paterno, a.nombre as alum_nom 
                      FROM alumnos a 
                      JOIN tutores t ON a.tutor_principal_id = t.id 
                      WHERE a.id = ?";
        $stmt_t = $conn->prepare($sql_tutor);
        $stmt_t->bind_param("i", $alumno_id);
        $stmt_t->execute();
        $datos_tutor = $stmt_t->get_result()->fetch_assoc();
        
        if ($datos_tutor && !empty($datos_tutor['email'])) {
            require_once __DIR__ . '/../../config/utils/mailer.php';
            $nombre_tutor = $datos_tutor['nombre'] . ' ' . $datos_tutor['apellido_paterno'];
            sendEmail($d['email'], $nombre_tutor, "Alerta Escolar: " . $d['alum_nom'], 
                "<h3>Aviso de Riesgo Escolar</h3><p>Se ha generado una alerta de prioridad <strong>$prioridad</strong> para el alumno <strong>{$d['alum_nom']}</strong>.</p><p><strong>Motivo:</strong> $titulo</p>");
        }
    }

    header('Location: alertas_list.php?success=created');
} else {
    die('Error al guardar la alerta: ' . $stmt->error);
}

$stmt->close();
$conn->close();
?>




<!-- 
modificar en alerta_detalle el creador que se pueda elegir en la creacion del reporte.
enviar correo con los detalles de el expediente del alumno, reporte inicial e intervenciones.
Permitir eliminar una alerta.
Nueva alerta no tiene para regresar
Intervenciones falta para agregar al responsable_id
Permitir eliminar intervenciones

la ia revisar el funcionamiento. -->
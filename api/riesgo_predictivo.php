<?php
// Este archivo actúa como un API endpoint para la IA.
// Solo permitir acceso si se pasa el ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400); // Bad Request
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'mensaje' => 'ID de alumno requerido.']);
    exit;
}

$alumno_id = (int)$_GET['id'];

require_once __DIR__ . '/../config/utils/ai_service_utils.php';

// Obtener el resultado de la función de IA
$resultado_ia = obtener_riesgo_predictivo_ia($alumno_id);

// Devolver respuesta
header('Content-Type: application/json');

if ($resultado_ia['success']) {
    echo json_encode([
        'success' => true,
        'riesgo' => $resultado_ia['riesgo'],
        'probabilidad' => $resultado_ia['probabilidad']
    ]);
} else {
    // Errores
    http_response_code(500);
    echo json_encode($resultado_ia);
}
?>
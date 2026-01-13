<?php
require_once __DIR__ . '/../../config/db.php';
header('Content-Type: application/json');

$response = ['success' => false, 'id' => null, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';

    if (empty($nombre)) {
        $response['message'] = 'El nombre de la materia no puede estar vacío.';
        echo json_encode($response);
        exit;
    }

    $sql = "INSERT INTO materias (nombre) VALUES (?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt === false) {
        $response['message'] = 'Error de preparación de la consulta: ' . $conn->error;
    } else {
        $stmt->bind_param("s", $nombre);
        
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['id'] = $conn->insert_id;
            $response['message'] = 'Materia guardada con éxito.';
        } else {
            $response['message'] = 'Error de ejecución de la consulta: ' . $stmt->error;
        }
        $stmt->close();
    }
} else {
    $response['message'] = 'Método no permitido.';
}

echo json_encode($response);
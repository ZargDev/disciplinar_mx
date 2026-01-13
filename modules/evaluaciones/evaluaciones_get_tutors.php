<?php
require_once __DIR__ . '/../../config/db.php';
header('Content-Type: application/json');

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$response = [];

if ($id) {
    $sql = "SELECT 
                a.nombre AS nombre_alumno, 
                COALESCE(a.apellido_paterno, '') AS apellido_paterno_alumno, 
                t.correo AS email_tutor, 
                t.nombre AS nombre_tutor 
            FROM evaluacion_alumnos ea
            JOIN alumnos a ON ea.alumno_id = a.id
            LEFT JOIN tutores t ON t.id = a.tutor_principal_id 
            WHERE ea.evaluacion_id = ?";
            
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        // Manejo de error
        echo json_encode(['error' => 'Error en la preparación de la consulta: ' . $conn->error]);
        exit;
    }
    
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();

    while($row = $res->fetch_assoc()) {
        $response[] = [
            'alumno' => trim($row['nombre_alumno'] . ' ' . $row['apellido_paterno_alumno']),
            'tutor'  => $row['nombre_tutor'] ?? 'Tutor No Asignado', 
            'email'  => $row['email_tutor'] ?? 'N/A'
        ];
    }
    
    $stmt->close();
}

echo json_encode($response);
?>
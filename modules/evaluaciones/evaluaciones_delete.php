<?php
require_once __DIR__ . '/../../config/db.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id) {
    $conn->begin_transaction();
    $success = true;
    $related_tables = [
        'evaluacion_alumnos',
        'evaluacion_categorias',
        'evaluacion_detonantes',
    ];

    foreach ($related_tables as $table) {
        $stmt_related = $conn->prepare("DELETE FROM $table WHERE evaluacion_id = ?");
        if (!$stmt_related) {
            $success = false;
            echo "Error preparando DELETE en $table: " . $conn->error;
            break;
        }
        $stmt_related->bind_param("i", $id);
        if (!$stmt_related->execute()) {
            $success = false;
            echo "Error eliminando en $table: " . $stmt_related->error;
            break;
        }
        $stmt_related->close();
    }
    
    if ($success) {
        $stmt_main = $conn->prepare("DELETE FROM evaluacion_conducta WHERE id = ?");
        if (!$stmt_main) {
            $success = false;
            echo "Error preparando DELETE principal: " . $conn->error;
        } else {
            $stmt_main->bind_param("i", $id);
            if ($stmt_main->execute()) {
                $conn->commit();
                header("Location: evaluaciones_list.php?msg=eliminado");
            } else {
                $success = false;
                echo "Error al eliminar evaluación principal: " . $stmt_main->error;
            }
            $stmt_main->close();
        }
    }

    if (!$success) {
        $conn->rollback();
    }
} else {
    header("Location: evaluaciones_list.php");
}
exit;
?>
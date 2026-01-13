<?php
session_start();
// Rutas
$rootPath = str_replace('\\', '/', dirname(dirname(__DIR__))); 
$pythonExecutable = $rootPath . '/venv/Scripts/python.exe';
$pythonScript = $rootPath . '/ml_engine/scripts/update_risk_cache.py';

$python_exe_path = str_replace('/', '\\', $pythonExecutable);
$python_script_path = str_replace('/', '\\', $pythonScript);
$command = '"' . $python_exe_path . '" "' . $python_script_path . '" 2>&1';

$output_array = [];
$return_code = 0;
exec($command, $output_array, $return_code);

// ---  DEBUG  ---
// echo "<h1>Diagnóstico de Actualización</h1>";
// echo "<strong>Comando ejecutado:</strong> " . $command . "<br><br>";
// echo "<strong>Código de Retorno (0=Éxito, 1=Error):</strong> " . $return_code . "<br><br>";
// echo "<strong>Salida completa de Python:</strong><pre>";
// print_r($output_array);
// echo "</pre>";

// Intentar decodificar
$output = end($output_array);
$data = json_decode($output, true);

// echo "<strong>JSON Decodificado:</strong><pre>";
// var_dump($data);
// echo "</pre>";

// DEBUG ERROR, ELIMINAR DESPUES DE VERIGICAR
// exit; 

if ($data && ($data['success'] ?? false)) {
    $_SESSION['mensaje_sistema'] = "OK " . $data['mensaje'];
} else {
    $_SESSION['error_sistema'] = "Error: " . ($data['mensaje'] ?? 'Fallo desconocido al actualizar.');
}

header('Location: dashboard.php');
exit;
?>
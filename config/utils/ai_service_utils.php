<?php
// Función para llamar al motor de IA de Python y obtener el riesgo predictivo.

function obtener_riesgo_predictivo_ia($alumno_id) {
    // Definicion de  rutas absolutas
    $rootPath = str_replace('\\', '/', dirname(dirname(__DIR__)));

    // Rutas al ejecutable de Python y al script
    $pythonExecutable = $rootPath . '/venv/Scripts/python.exe';
    $pythonScript = $rootPath . '/ml_engine/scripts/predict_risk.py';
    $python_exe_path = str_replace('/', '\\', $pythonExecutable);
    $python_script_path = str_replace('/', '\\', $pythonScript);

    $cmdPython = '"' . $python_exe_path . '"';
    $cmdScript = '"' . $python_script_path . '"';

    $command = $cmdPython . ' ' . 
                $cmdScript . ' ' . 
                escapeshellarg($alumno_id) . ' 2>&1';

    $command = $cmdPython . ' ' . 
               $cmdScript . ' ' . 
               escapeshellarg($alumno_id);
    
    // Se ejecuta el comando usando exec() para capturar el cogigo de retorno
    $output_array = [];
    $return_code = 0;

    exec($command, $output_array, $return_code);
    $output = end($output_array); 
    $data = json_decode($output, true);

    // Funcion manejo de los errores
    if ($data === null || ($data['error'] ?? false)) {
        // Si $data es null o hay un error, devuelve el objeto de depuracion completo
        $errorMessage = $data['mensaje'] ?? 'La salida de Python no fue JSON o fue nula.';
        
        // Retornar info de depuracion
        return [
            'success' => false,
            'riesgo' => 'DEBUG_IA',
            'probabilidad' => 0,
            'mensaje' => $errorMessage,
            'debug_command' => $command,
            'debug_output_full' => $output_array,
            'debug_return_code' => $return_code,  // Código de error
            'debug_data_error' => $data
        ];
    }
    
    if ($data['error'] ?? false) {
        // Error controlado desde Python
        error_log("Error de IA: " . ($data['mensaje'] ?? 'Error desconocido'));
        return [
            'success' => false, 
            'riesgo' => 'ERROR_IA', 
            'probabilidad' => 0,
            'mensaje' => $data['mensaje'] ?? 'Error de lógica interna de la IA.'
        ];
    }

    // 5. Retornar el resultado exitoso
    if ($data === null || ($data['error'] ?? false)) {
        $errorMessage = $data['mensaje'] ?? 'Error desconocido del script.';
        // Retornar información de depuracion
        return [
            'success' => false,
            'riesgo' => 'DEBUG',
            'probabilidad' => 0,
            'mensaje' => 'Fallo al procesar JSON',
            'debug_output' => $output,
            'debug_command' => $command,
            'debug_data_error' => $data
        ];
    }
    
    return [
        'success' => true,
        'riesgo' => $data['riesgo_detectado'],
        'probabilidad' => round($data['probabilidad'], 2)
    ];
}
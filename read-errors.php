<?php
// Configurar cabeceras para permitir acceso desde otras aplicaciones
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Ruta del archivo en public_html
$filePath = $_SERVER['DOCUMENT_ROOT'] . '/error-php.log'; // Ruta absoluta del archivo de log

if (!file_exists($filePath)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'El archivo de log no existe.'
    ]);
    exit;
}

// Leer el archivo de logs
$logContent = file_get_contents($filePath);

if ($logContent === false) {
    echo json_encode([
        'status' => 'error',
        'message' => 'No se pudo leer el archivo de log.'
    ]);
    exit;
}

// Separar los errores por línea y devolverlos en formato JSON
$logLines = explode("\n", $logContent);
$logLines = array_filter($logLines); // Quitar líneas vacías

echo json_encode([
    'status' => 'success',
    'data' => $logLines
], JSON_PRETTY_PRINT);

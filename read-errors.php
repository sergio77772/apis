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

// Separar los errores por línea
$logLines = explode("\n", $logContent);
$logLines = array_filter($logLines); // Quitar líneas vacías
$totalRecords = count($logLines);

// Parámetros de paginación
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Página actual (por defecto, 1)
$perPage = 10; // Registros por página
$totalPages = (int)ceil($totalRecords / $perPage); // Total de páginas

// Validar que la página esté dentro de los límites
if ($page < 1) $page = 1;
if ($page > $totalPages) $page

<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

require 'db.php'; // Archivo para la conexión a la base de datos

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'OPTIONS') {
    http_response_code(200);
    exit();
}

switch ($method) {
    case 'GET':
        listarRegistros();
        break;
    case 'PUT':
        modificarRegistro();
        break;
    default:
        echo json_encode(["message" => "Método no permitido"]);
        break;
}

function listarRegistros() {
    global $pdo;
    $stmt = $pdo->query("SELECT id, Nombre, telefono, direccion, email, imagenes FROM comercio_web");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}

function modificarRegistro() {
    global $pdo;
    parse_str(file_get_contents("php://input"), $_PUT);
    
    if (!isset($_PUT['id']) || !isset($_PUT['Nombre']) || !isset($_PUT['telefono'])) {
        echo json_encode(["message" => "Faltan datos"]);
        return;
    }

    $id = $_PUT['id'];
    $nombre = $_PUT['Nombre'];
    $telefono = $_PUT['telefono'];
    $direccion = $_PUT['direccion'] ?? NULL;
    $email = $_PUT['email'] ?? NULL;

    // Actualizar el registro sin afectar las imágenes
    $stmt = $pdo->prepare("UPDATE comercio_web SET Nombre = ?, telefono = ?, direccion = ?, email = ? WHERE id = ?");
    $stmt->execute([$nombre, $telefono, $direccion, $email, $id]);

    echo json_encode(["message" => "Registro actualizado"]);
}

?>

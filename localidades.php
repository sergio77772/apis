<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

require 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'OPTIONS') {
    http_response_code(200);
    exit();
}

switch ($method) {
    case 'GET':
        listarLocalidades();
        break;
    case 'POST':
        agregarLocalidad();
        break;
    case 'PUT':
        modificarLocalidad();
        break;
    case 'DELETE':
        eliminarLocalidad();
        break;
    default:
        echo json_encode(["message" => "Método no permitido"]);
        break;
}

function listarLocalidades() {
    global $pdo;
    $stmt = $pdo->query("SELECT idlocalidad, nombre, precio_envio FROM localidades ORDER BY nombre ASC");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}

function agregarLocalidad() {
    global $pdo;
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['nombre']) || !isset($data['precio_envio'])) {
        echo json_encode(["message" => "Faltan datos"]);
        return;
    }

    $stmt = $pdo->prepare("INSERT INTO localidades (nombre, precio_envio) VALUES (?, ?)");
    $stmt->execute([$data['nombre'], $data['precio_envio']]);

    echo json_encode(["message" => "Localidad agregada correctamente", "id" => $pdo->lastInsertId()]);
}

function modificarLocalidad() {
    global $pdo;
    
    parse_str(file_get_contents("php://input"), $_PUT);

    if (!isset($_PUT['idlocalidad']) || !isset($_PUT['nombre']) || !isset($_PUT['precio_envio'])) {
        echo json_encode(["message" => "Faltan datos"]);
        return;
    }

    $stmt = $pdo->prepare("UPDATE localidades SET nombre = ?, precio_envio = ? WHERE idlocalidad = ?");
    $stmt->execute([$_PUT['nombre'], $_PUT['precio_envio'], $_PUT['idlocalidad']]);

    echo json_encode(["message" => "Localidad actualizada correctamente"]);
}

function eliminarLocalidad() {
    global $pdo;
    
    parse_str(file_get_contents("php://input"), $_DELETE);

    if (!isset($_DELETE['idlocalidad'])) {
        echo json_encode(["message" => "Faltan datos"]);
        return;
    }

    $stmt = $pdo->prepare("DELETE FROM localidades WHERE idlocalidad = ?");
    $stmt->execute([$_DELETE['idlocalidad']]);

    echo json_encode(["message" => "Localidad eliminada correctamente"]);
}
?>

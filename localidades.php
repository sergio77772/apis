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

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;

    $search = isset($_GET['search']) ? $_GET['search'] : '';

    $sql = "SELECT idlocalidad, nombre, precio_envio FROM localidades 
            WHERE nombre LIKE :search 
            ORDER BY nombre ASC 
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM localidades WHERE nombre LIKE :search");
    $countStmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    $countStmt->execute();
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    echo json_encode([
        "total" => $total,
        "page" => $page,
        "limit" => $limit,
        "pages" => ceil($total / $limit),
        "data" => $result
    ]);
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

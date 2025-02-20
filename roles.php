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
        listarRoles();
        break;
    case 'POST':
        agregarRol();
        break;
    case 'PUT':
        modificarRol();
        break;
    case 'DELETE':
        eliminarRol();
        break;
    default:
        echo json_encode(["message" => "Método no permitido"]);
        break;
}

function listarRoles() {
    global $pdo;

    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $offset = ($page - 1) * $limit;

    $search = isset($_GET['search']) ? $_GET['search'] : '';

    $sql = "SELECT idRol, descripcion FROM roles_web 
            WHERE descripcion LIKE :search 
            ORDER BY descripcion ASC 
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM roles_web WHERE descripcion LIKE :search");
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

function agregarRol() {
    global $pdo;
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['descripcion'])) {
        echo json_encode(["message" => "Faltan datos"]);
        return;
    }

    $stmt = $pdo->prepare("INSERT INTO roles_web (descripcion) VALUES (?)");
    $stmt->execute([$data['descripcion']]);

    echo json_encode(["message" => "Rol agregado correctamente", "id" => $pdo->lastInsertId()]);
}

function modificarRol() {
    global $pdo;
    
    parse_str(file_get_contents("php://input"), $_PUT);

    if (!isset($_PUT['idRol']) || !isset($_PUT['descripcion'])) {
        echo json_encode(["message" => "Faltan datos"]);
        return;
    }

    $stmt = $pdo->prepare("UPDATE roles_web SET descripcion = ? WHERE idRol = ?");
    $stmt->execute([$_PUT['descripcion'], $_PUT['idRol']]);

    echo json_encode(["message" => "Rol actualizado correctamente"]);
}

function eliminarRol() {
    global $pdo;
    
    parse_str(file_get_contents("php://input"), $_DELETE);

    if (!isset($_DELETE['idRol'])) {
        echo json_encode(["message" => "Faltan datos"]);
        return;
    }

    $stmt = $pdo->prepare("DELETE FROM roles_web WHERE idRol = ?");
    $stmt->execute([$_DELETE['idRol']]);

    echo json_encode(["message" => "Rol eliminado correctamente"]);
}
?>

<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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
    case 'POST':
        agregarRegistro();
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
    $stmt = $pdo->query("SELECT * FROM registros");
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
}

function agregarRegistro() {
    global $pdo;
    
    if (!isset($_POST['nombre']) || !isset($_POST['telefono']) || !isset($_FILES['imagenes'])) {
        echo json_encode(["message" => "Faltan datos"]);
        return;
    }
    
    $nombre = $_POST['nombre'];
    $telefono = $_POST['telefono'];
    $direccion = $_POST['direccion'] ?? NULL;
    $email = $_POST['email'] ?? NULL;
    $imagenes = [];

    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/img/'; // Directorio donde se guardarán las imágenes

    foreach ($_FILES['imagenes']['tmp_name'] as $key => $tmp_name) {
        $fileName = uniqid() . "_" . $_FILES['imagenes']['name'][$key];
        $filePath = $uploadDir . $fileName;
        move_uploaded_file($tmp_name, $filePath);
        $imagenes[] = "/img/" . $fileName;
    }
    
    $imagenesJson = json_encode($imagenes);
    $stmt = $pdo->prepare("INSERT INTO comercio_web (Nombre, telefono, direccion, email, imagenes) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$nombre, $telefono, $direccion, $email, $imagenesJson]);

    echo json_encode(["message" => "Registro agregado con éxito"]);
}

function modificarRegistro() {
    global $pdo;
    parse_str(file_get_contents("php://input"), $_PUT);
    
    if (!isset($_PUT['id']) || !isset($_PUT['nombre']) || !isset($_PUT['telefono'])) {
        echo json_encode(["message" => "Faltan datos"]);
        return;
    }

    $id = $_PUT['id'];
    $nombre = $_PUT['nombre'];
    $telefono = $_PUT['telefono'];
    $direccion = $_PUT['direccion'] ?? NULL;
    $email = $_PUT['email'] ?? NULL;
    
    $stmt = $pdo->prepare("UPDATE comercio_web SET Nombre = ?, telefono = ?, direccion = ?, email = ? WHERE idPrimaria = ?");
    $stmt->execute([$nombre, $telefono, $direccion, $email, $id]);
    
    echo json_encode(["message" => "Registro actualizado"]);
}

?>

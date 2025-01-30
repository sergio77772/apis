<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

require 'db.php'; // Conexión a la base de datos

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

    // Manejo de imágenes (si se subieron nuevas)
    $imagenes = [];
    if (!empty($_FILES['imagenes']['name'][0])) {
        $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/img/';
        foreach ($_FILES['imagenes']['tmp_name'] as $key => $tmp_name) {
            $fileName = uniqid() . "_" . $_FILES['imagenes']['name'][$key];
            $filePath = $uploadDir . $fileName;
            move_uploaded_file($tmp_name, $filePath);
            $imagenes[] = "/img/" . $fileName;
        }
    } else {
        // Si no se suben nuevas imágenes, mantener las existentes
        $stmt = $pdo->prepare("SELECT imagenes FROM comercio_web WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $imagenes = json_decode($row['imagenes'], true) ?: [];
    }

    $imagenesJson = json_encode($imagenes);

    // Actualizar los datos en la base de datos
    $stmt = $pdo->prepare("UPDATE comercio_web SET Nombre = ?, telefono = ?, direccion = ?, email = ?, imagenes = ? WHERE id = ?");
    $stmt->execute([$nombre, $telefono, $direccion, $email, $imagenesJson, $id]);

    echo json_encode(["message" => "Registro actualizado"]);
}
?>

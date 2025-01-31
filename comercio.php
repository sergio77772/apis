<?php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, POST, OPTIONS');
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
    case 'POST':
        subirImagenes();
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

    $stmt = $pdo->prepare("UPDATE comercio_web SET Nombre = ?, telefono = ?, direccion = ?, email = ? WHERE id = ?");
    $stmt->execute([$nombre, $telefono, $direccion, $email, $id]);

    echo json_encode(["message" => "Registro actualizado"]);
}

function subirImagenes() {
    global $pdo;

    if (!isset($_POST['id']) || empty($_FILES['imagenes']['name'][0])) {
        echo json_encode(["message" => "Faltan datos"]);
        return;
    }

    $id = $_POST['id'];
    $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/img/';
    $imagenes = [];

    // Obtener imágenes actuales para eliminarlas
    $stmt = $pdo->prepare("SELECT imagenes FROM comercio_web WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $imagenesExistentes = json_decode($row['imagenes'], true) ?: [];

    // Eliminar imágenes antiguas del servidor
    foreach ($imagenesExistentes as $imgPath) {
        $fileToDelete = $_SERVER['DOCUMENT_ROOT'] . $imgPath;
        if (file_exists($fileToDelete)) {
            unlink($fileToDelete);
        }
    }

    // Subir y guardar nuevas imágenes
    foreach ($_FILES['imagenes']['tmp_name'] as $key => $tmp_name) {
        $fileName = uniqid() . "_" . $_FILES['imagenes']['name'][$key];
        $filePath = $uploadDir . $fileName;
        move_uploaded_file($tmp_name, $filePath);
        $imagenes[] = "/img/" . $fileName;
    }

    $imagenesJson = json_encode($imagenes);

    // Actualizar base de datos con nuevas imágenes
    $stmt = $pdo->prepare("UPDATE comercio_web SET imagenes = ? WHERE id = ?");
    $stmt->execute([$imagenesJson, $id]);

    echo json_encode(["message" => "Imágenes reemplazadas correctamente"]);
}
?>

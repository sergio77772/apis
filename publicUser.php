<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permite solicitudes desde cualquier dominio
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
require 'db.php'; 
$method = $_SERVER['REQUEST_METHOD'];
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/img/'; // Carpeta donde se guardarán las imágenes
$secretKey = 'your_secret_key';

function uploadImage($file) {
    global $uploadDir;
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = time() . '_' . uniqid() . '.' . $ext; // Nombre único usando la marca de tiempo
    $filePath = $uploadDir . $fileName;
    return move_uploaded_file($file['tmp_name'], $filePath) ? '/img/' . $fileName : null;
}

if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'register') {
    $data = $_POST;
    if (empty($data['correo']) || empty($data['password']) || empty($data['nombre'])) {
        http_response_code(400);
        echo json_encode(["error" => "Faltan datos obligatorios"]);
        exit;
    }
    
    $foto = isset($_FILES['image']) ? uploadImage($_FILES['image']) : null;
    
    try {
        $sql = "INSERT INTO users_web (correo, nombre, password, foto) VALUES (:correo, :nombre, :password, :foto)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':correo' => $data['correo'],
            ':nombre' => $data['nombre'],
            ':password' => password_hash($data['password'], PASSWORD_BCRYPT),
            ':foto' => $foto
        ]);
        http_response_code(201);
        echo json_encode(["success" => "Usuario registrado"]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error en el servidor"]);
    }
}


if ($method === 'POST' && isset($_POST['method']) && $_POST['method'] === 'PUT' isset($_GET['action']) && $_GET['action'] === 'edit') {
    if (empty($_POST['id']) || empty($_POST['nombre']) || empty($_POST['correo'])) {
        http_response_code(400);
        echo json_encode(["error" => "ID, correo y nombre son obligatorios"]);
        exit;
    }

    $foto = isset($_FILES['imagen']) ? uploadImage($_FILES['imagen']) : null;

    try {
        $sql = "UPDATE users_web SET correo = :correo, nombre = :nombre, direccion = :direccion, telefono = :telefono, foto = COALESCE(:foto, foto) WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':correo' => $_POST['correo'],
            ':nombre' => $_POST['nombre'],
            ':foto' => $foto, ':direccion' => $data['direccion'] ?? null,
            ':telefono' => $data['telefono'] ?? null,
            ':id' => $_POST['id']
        ]);
        http_response_code(200);
        echo json_encode(["success" => "Usuario actualizado"]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error interno del servidor"]);
    }
}


?>

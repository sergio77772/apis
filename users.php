<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, token');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require 'db.php'; 
$method = $_SERVER['REQUEST_METHOD'];
$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/img/'; // Carpeta donde se guardarán las imágenes
$secretKey = 'your_secret_key';

// Función para generar JWT
function createJWT($payload, $secretKey) {
    $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
    $headerBase64 = base64UrlEncode($header);
    $payloadBase64 = base64UrlEncode(json_encode($payload));
    $signature = hash_hmac('sha256', "$headerBase64.$payloadBase64", $secretKey, true);
    $signatureBase64 = base64UrlEncode($signature);
    return "$headerBase64.$payloadBase64.$signatureBase64";
}

function base64UrlEncode($data) {
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
}

function validateJWT($jwt, $secretKey) {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) return false;
    [$headerBase64, $payloadBase64, $signatureBase64] = $parts;
    $signatureCheck = hash_hmac('sha256', "$headerBase64.$payloadBase64", $secretKey, true);
    return hash_equals(base64UrlEncode($signatureCheck), $signatureBase64) ? json_decode(base64UrlDecode($payloadBase64), true) : false;
}

// Función para manejar la subida de imágenes con nombre único
function uploadImage($file) {
    global $uploadDir;
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = time() . '_' . uniqid() . '.' . $ext; // Nombre único usando la marca de tiempo
    $filePath = $uploadDir . $fileName;
    return move_uploaded_file($file['tmp_name'], $filePath) ? '/img/' . $fileName : null;
}

if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'login') {
    if (empty($_POST['correo']) || empty($_POST['password'])) {
        http_response_code(400);
        echo json_encode(["error" => "Correo y contraseña son obligatorios"]);
        exit;
    }
    $correo = $_POST['correo'];
    $password = $_POST['password'];
    try {
        $sql = "SELECT id, correo, nombre, idRol, foto, password FROM users_web WHERE correo = :correo";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['correo' => $correo]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password'])) {
            $payload = [
                'id' => $user['id'],
                'correo' => $user['correo'],
                'nombre' => $user['nombre'],
                'idRol' => $user['idRol'],
                'foto' => $user['foto'],
                'exp' => time() + 3600
            ];
            $jwt = createJWT($payload, $secretKey);
            echo json_encode([
                "success" => "Inicio de sesión exitoso", 
                "token" => $jwt,
                "id" => $user['id'],
                "idRol" => $user['idRol'],
                "nombre" => $user['nombre'],
                "foto" => $user['foto']
            ]);
        } else {
            http_response_code(401);
            echo json_encode(["error" => "Credenciales inválidas"]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error interno del servidor"]);
    }
}
?>

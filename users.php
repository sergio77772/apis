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

if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'login') {
    $data = json_decode(file_get_contents("php://input"), true);
    if (empty($data['correo']) || empty($data['password'])) {
        http_response_code(400);
        echo json_encode(["error" => "Correo y contraseña son obligatorios"]);
        exit;
    }
    
    try {
        $sql = "SELECT id, correo, password, nombre, foto, idrol FROM users_web WHERE correo = :correo";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':correo' => $data['correo']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($data['password'], $user['password'])) {
            $token = createJWT(['id' => $user['id'], 'correo' => $user['correo']], $secretKey);
            http_response_code(200);
            echo json_encode([
                "success" => "Login exitoso",
                "token" => $token,
                "id" => $user['id'],
                "nombre" => $user['nombre'],
                "foto" => $user['foto'],
                "idrol" => $user['idrol']
            ]);
        } else {
            http_response_code(401);
            echo json_encode(["error" => "Credenciales inválidas"]);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error en el servidor"]);
    }
}

if ($method === 'POST' && isset($_POST['method']) && $_POST['method'] === 'PUT') {
    if (empty($_POST['id']) || empty($_POST['nombre']) || empty($_POST['correo'])) {
        http_response_code(400);
        echo json_encode(["error" => "ID, correo y nombre son obligatorios"]);
        exit;
    }

    $foto = isset($_FILES['imagen']) ? uploadImage($_FILES['imagen']) : null;

    try {
        $sql = "UPDATE users_web SET correo = :correo, nombre = :nombre, foto = COALESCE(:foto, foto) WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':correo' => $_POST['correo'],
            ':nombre' => $_POST['nombre'],
            ':foto' => $foto,
            ':id' => $_POST['id']
        ]);
        http_response_code(200);
        echo json_encode(["success" => "Usuario actualizado"]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error interno del servidor"]);
    }
}

if ($method === 'DELETE') {
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(["error" => "ID requerido"]);
        exit;
    }
    
    try {
        $sql = "DELETE FROM users_web WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $_GET['id']]);
        http_response_code(200);
        echo json_encode(["success" => "Usuario eliminado"]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error en el servidor"]);
    }
}

if ($method === 'GET') {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $offset = ($page - 1) * $limit;

    try {
        $countSql = "SELECT COUNT(*) as total FROM users_web WHERE nombre LIKE :search OR correo LIKE :search";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([':search' => '%' . $search . '%']);
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        
        $sql = "SELECT id, correo, nombre, direccion,foto,idrol FROM users_web WHERE nombre LIKE :search OR correo LIKE :search LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode(["total" => $total, "page" => $page, "limit" => $limit, "data" => $users]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error interno del servidor"]);
    }
}
?>

<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, token');  // Permitir el encabezado 'token'
header('Access-Control-Allow-Credentials: true'); 
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, token');  // Permitir el encabezado 'token'
    header('Access-Control-Allow-Credentials: true'); 
    http_response_code(200);
    exit;
}

require 'db.php'; 
$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true);

// Manejo de conexión PDO
global $pdo;

// Función para generar JWT
function createJWT($payload, $secretKey) {
    // Header
    $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
    $headerBase64 = base64UrlEncode($header);

    // Payload
    $payloadBase64 = base64UrlEncode(json_encode($payload));

    // Signature
    $signature = hash_hmac('sha256', "$headerBase64.$payloadBase64", $secretKey, true);
    $signatureBase64 = base64UrlEncode($signature);

    // JWT
    return "$headerBase64.$payloadBase64.$signatureBase64";
}

function base64UrlEncode($data) {
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
}

function base64UrlDecode($data) {
    $data = str_replace(['-', '_'], ['+', '/'], $data);
    return base64_decode($data);
}

// Función para validar JWT
function validateJWT($jwt, $secretKey) {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) {
        return false;
    }

    [$headerBase64, $payloadBase64, $signatureBase64] = $parts;

    // Recalcular la firma
    $signatureCheck = hash_hmac('sha256', "$headerBase64.$payloadBase64", $secretKey, true);
    $signatureCheckBase64 = base64UrlEncode($signatureCheck);

    // Verificar que la firma coincida
    if (!hash_equals($signatureCheckBase64, $signatureBase64)) {
        return false;
    }

    // Decodificar el payload y verificar expiración
    $payload = json_decode(base64UrlDecode($payloadBase64), true);
    if (isset($payload['exp']) && time() > $payload['exp']) {
        return false;
    }

    return $payload; // Token válido, devolver datos
}

if ($method === 'POST') {
    if (isset($_GET['action']) && $_GET['action'] === 'register') {
        if (empty($data['correo']) || empty($data['nombre']) || empty($data['password'])) {
            http_response_code(400);
            echo json_encode(["error" => "Campos obligatorios faltantes"]);
            exit;
        }

        $correo = $data['correo'];
        $nombre = $data['nombre'];
        $password = password_hash($data['password'], PASSWORD_BCRYPT);
        $direccion = isset($data['direccion']) ? $data['direccion'] : null;

        try {
            $sql = "INSERT INTO users_web (correo, nombre, password, direccion) 
                    VALUES (:correo, :nombre, :password, :direccion)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([ 
                ':correo' => $correo, 
                ':nombre' => $nombre, 
                ':password' => $password, 
                ':direccion' => $direccion 
            ]);
            http_response_code(201);
            echo json_encode(["success" => "Usuario registrado"]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["error" => "Error interno del servidor"]);
        }

    } elseif (isset($_GET['action']) && $_GET['action'] === 'login') {
        if (empty($data['correo']) || empty($data['password'])) {
            http_response_code(400);
            echo json_encode(["error" => "Correo y contraseña son obligatorios"]);
            exit;
        }

        $correo = $data['correo'];
        $password = $data['password'];

        try {
            $sql = "SELECT * FROM users_web WHERE correo = :correo";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':correo' => $correo]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // Crear el JWT con datos del usuario
                $payload = [
                    'iss' => 'your_domain.com',
                    'aud' => 'your_domain.com',
                    'iat' => time(),
                    'exp' => time() + 3600, // Expira en 1 hora
                    'data' => [
                        'id' => $user['id'],
                        'correo' => $user['correo'],
                        'nombre' => $user['nombre']
                    ]
                ];

                $jwt = createJWT($payload, 'your_secret_key');  // Usar una clave secreta para firmar el token

                http_response_code(200);
                echo json_encode(["success" => "Inicio de sesión exitoso", "token" => $jwt]);
            } else {
                http_response_code(401);
                echo json_encode(["error" => "Credenciales inválidas"]);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["error" => "Error interno del servidor"]);
        }
    }
} elseif ($method === 'GET') {
    // Valores predeterminados para paginación y búsqueda
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    $offset = ($page - 1) * $limit;

    try {
        // Contar el total de usuarios para paginación
        $countSql = "SELECT COUNT(*) as total FROM users_web WHERE nombre LIKE :search OR correo LIKE :search";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute([':search' => '%' . $search . '%']);
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Obtener usuarios con límite, offset y búsqueda
        $sql = "SELECT id, correo, nombre, direccion 
                FROM users_web 
                WHERE nombre LIKE :search OR correo LIKE :search 
                LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Respuesta con datos paginados
        http_response_code(200);
        echo json_encode([
            "total" => $total,
            "page" => $page,
            "limit" => $limit,
            "data" => $users
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Error interno del servidor"]);
    }
} elseif ($method === 'PUT' || $method === 'DELETE') {
    // Validar el token JWT
    $authHeader = isset($_SERVER['HTTP_TOKEN']) ? $_SERVER['HTTP_TOKEN'] : '';


    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $jwt = $matches[1];

        $decoded = validateJWT($jwt, 'your_secret_key');
        if ($decoded) {
            // El token es válido, proceder con la operación
            if ($method === 'PUT') {
                if (empty($data['id']) || empty($data['nombre']) || empty($data['correo'])) {
                    http_response_code(400);
                    echo json_encode(["error" => "ID, correo y nombre son obligatorios"]);
                    exit;
                }

                $id = $data['id'];
                $correo = $data['correo'];
                $nombre = $data['nombre'];
                $direccion = isset($data['direccion']) ? $data['direccion'] : null;

                try {
                    $sql = "UPDATE users_web SET correo = :correo, nombre = :nombre, direccion = :direccion 
                            WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        ':correo' => $correo,
                        ':nombre' => $nombre,
                        ':direccion' => $direccion,
                        ':id' => $id
                    ]);
                    http_response_code(200);
                    echo json_encode(["success" => "Usuario actualizado"]);
                } catch (PDOException $e) {
                    http_response_code(500);
                    echo json_encode(["error" => "Error interno del servidor"]);
                }
            } elseif ($method === 'DELETE') {
                if (!isset($_GET['id'])) {
                    http_response_code(400);
                    echo json_encode(["error" => "ID requerido"]);
                    exit;
                }

                $id = $_GET['id'];

                try {
                    $sql = "DELETE FROM users_web WHERE id = :id";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([':id' => $id]);
                    http_response_code(200);
                    echo json_encode(["success" => "Usuario eliminado"]);
                } catch (PDOException $e) {
                    http_response_code(500);
                    echo json_encode(["error" => "Error interno del servidor"]);
                }
            }
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Token inválido o expirado']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Token no proporcionado']);
    }
}
?>

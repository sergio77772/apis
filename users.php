<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require 'db.php'; // Asegúrate de que la ruta es correcta
$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true);

// Manejo de conexión PDO
global $pdo;

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
                http_response_code(200);
                echo json_encode(["success" => "Inicio de sesión exitoso", "user" => $user]);
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
} elseif ($method === 'PUT') {
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
} else {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido"]);
}

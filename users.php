<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
require 'db.php';
$method = $_SERVER['REQUEST_METHOD'];
$data = json_decode(file_get_contents("php://input"), true);

if ($method === 'POST') {
    if (isset($_GET['action']) && $_GET['action'] === 'register') {
        if (empty($data['correo']) || empty($data['nombre']) || empty($data['password'])) {
            echo json_encode(["error" => "Campos obligatorios faltantes"]);
            exit;
        }

        $correo = $data['correo'];
        $nombre = $data['nombre'];
        $password = password_hash($data['password'], PASSWORD_BCRYPT);
        $direccion = isset($data['direccion']) ? $data['direccion'] : null;

        try {
            $sql = "INSERT INTO users_web (correo, nombre, password, direccion) VALUES (:correo, :nombre, :password, :direccion)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':correo' => $correo,
                ':nombre' => $nombre,
                ':password' => $password,
                ':direccion' => $direccion
            ]);
            echo json_encode(["success" => "Usuario registrado"]);
        } catch (PDOException $e) {
            echo json_encode(["error" => $e->getMessage()]);
        }

    } elseif (isset($_GET['action']) && $_GET['action'] === 'login') {
        if (empty($data['correo']) || empty($data['password'])) {
            echo json_encode(["error" => "Correo y contraseña son obligatorios"]);
            exit;
        }

        $correo = $data['correo'];
        $password = $data['password'];

        try {
            $sql = "SELECT * FROM users_web WHERE correo = :correo";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':correo' => $correo]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                echo json_encode(["success" => "Inicio de sesión exitoso", "user" => $user]);
            } else {
                echo json_encode(["error" => "Credenciales inválidas"]);
            }
        } catch (PDOException $e) {
            echo json_encode(["error" => $e->getMessage()]);
        }
    }

} elseif ($method === 'GET') {
    try {
        $sql = "SELECT id, correo, nombre, direccion FROM users_web";
        $stmt = $conn->query($sql);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($users);
    } catch (PDOException $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }

} elseif ($method === 'PUT') {
    if (empty($data['id']) || empty($data['nombre']) || empty($data['correo'])) {
        echo json_encode(["error" => "ID, correo y nombre son obligatorios"]);
        exit;
    }

    $id = $data['id'];
    $correo = $data['correo'];
    $nombre = $data['nombre'];
    $direccion = isset($data['direccion']) ? $data['direccion'] : null;

    try {
        $sql = "UPDATE users_web SET correo = :correo, nombre = :nombre, direccion = :direccion WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':correo' => $correo,
            ':nombre' => $nombre,
            ':direccion' => $direccion,
            ':id' => $id
        ]);
        echo json_encode(["success" => "Usuario actualizado"]);
    } catch (PDOException $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }

} elseif ($method === 'DELETE') {
    if (!isset($_GET['id'])) {
        echo json_encode(["error" => "ID requerido"]);
        exit;
    }

    $id = $_GET['id'];

    try {
        $sql = "DELETE FROM users_web WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        echo json_encode(["success" => "Usuario eliminado"]);
    } catch (PDOException $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }

} else {
    echo json_encode(["error" => "Método no permitido"]);
}
?>

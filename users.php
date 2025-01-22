<?php
header("Content-Type: application/json");
include 'db.php';

$method = $_SERVER['REQUEST_METHOD'];

// Obtener datos JSON del cuerpo de la solicitud
$data = json_decode(file_get_contents("php://input"), true);

switch ($method) {
    case 'POST':
        // Crear usuario
        if (isset($_GET['action']) && $_GET['action'] === 'register') {
            registerUser($data);
        } elseif (isset($_GET['action']) && $_GET['action'] === 'login') {
            loginUser($data);
        }
        break;

    case 'GET':
        // Obtener usuarios
        getUsers();
        break;

    case 'PUT':
        // Actualizar usuario
        updateUser($data);
        break;

    case 'DELETE':
        // Eliminar usuario
        if (isset($_GET['id'])) {
            deleteUser($_GET['id']);
        } else {
            echo json_encode(["error" => "ID requerido"]);
        }
        break;

    default:
        echo json_encode(["error" => "Método no permitido"]);
        break;
}

// Función para registrar un usuario
function registerUser($data) {
    global $conn;

    if (empty($data['correo']) || empty($data['nombre']) || empty($data['password'])) {
        echo json_encode(["error" => "Campos obligatorios faltantes"]);
        return;
    }

    $correo = $data['correo'];
    $nombre = $data['nombre'];
    $password = password_hash($data['password'], PASSWORD_BCRYPT);
    $direccion = isset($data['direccion']) ? $data['direccion'] : null;

    try {
        $sql = "INSERT INTO users (correo, nombre, password, direccion) VALUES (:correo, :nombre, :password, :direccion)";
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
}

// Función para iniciar sesión
function loginUser($data) {
    global $conn;

    if (empty($data['correo']) || empty($data['password'])) {
        echo json_encode(["error" => "Correo y contraseña son obligatorios"]);
        return;
    }

    $correo = $data['correo'];
    $password = $data['password'];

    try {
        $sql = "SELECT * FROM users WHERE correo = :correo";
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

// Función para obtener usuarios
function getUsers() {
    global $conn;

    try {
        $sql = "SELECT id, correo, nombre, direccion FROM users";
        $stmt = $conn->query($sql);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($users);
    } catch (PDOException $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
}

// Función para actualizar usuario
function updateUser($data) {
    global $conn;

    if (empty($data['id']) || empty($data['nombre']) || empty($data['correo'])) {
        echo json_encode(["error" => "ID, correo y nombre son obligatorios"]);
        return;
    }

    $id = $data['id'];
    $correo = $data['correo'];
    $nombre = $data['nombre'];
    $direccion = isset($data['direccion']) ? $data['direccion'] : null;

    try {
        $sql = "UPDATE users SET correo = :correo, nombre = :nombre, direccion = :direccion WHERE id = :id";
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
}

// Función para eliminar usuario
function deleteUser($id) {
    global $conn;

    try {
        $sql = "DELETE FROM users WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->execute([':id' => $id]);
        echo json_encode(["success" => "Usuario eliminado"]);
    } catch (PDOException $e) {
        echo json_encode(["error" => $e->getMessage()]);
    }
}
?>

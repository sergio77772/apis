<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$endpoint = $_GET['endpoint'] ?? '';
$page = $_GET['page'] ?? 1;
$limit = $_GET['limit'] ?? 10;
$offset = ($page - 1) * $limit;

switch ($method) {
    case 'GET': // Obtener frases con búsqueda y paginación
        if ($endpoint === 'phrases') {
            $search = $_GET['search'] ?? '';

            // Contar el total de registros
            $countSql = "SELECT COUNT(*) as total FROM phrases WHERE texto LIKE :search";
            $countStmt = $pdo->prepare($countSql);
            $countStmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
            $countStmt->execute();
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Obtener frases
            $sql = "SELECT * FROM phrases WHERE texto LIKE :search LIMIT :limit OFFSET :offset";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
            $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
            $stmt->execute();
            $phrases = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(["total" => $total, "phrases" => $phrases]);
        }
        break;

    case 'POST': // Agregar una nueva frase
        if ($endpoint === 'phrases') {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!isset($data['texto']) || empty($data['texto'])) {
                echo json_encode(["error" => "El texto es obligatorio"]);
                exit;
            }

            $sql = "INSERT INTO phrases (texto) VALUES (:texto)";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':texto', $data['texto'], PDO::PARAM_STR);
            $stmt->execute();

            echo json_encode(["message" => "Frase agregada correctamente", "id" => $pdo->lastInsertId()]);
        }
        break;

    case 'PUT': // Actualizar una frase
        if ($endpoint === 'phrases') {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!isset($data['id']) || !isset($data['texto'])) {
                echo json_encode(["error" => "ID y texto son obligatorios"]);
                exit;
            }

            $sql = "UPDATE phrases SET texto = :texto WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':texto', $data['texto'], PDO::PARAM_STR);
            $stmt->bindValue(':id', (int) $data['id'], PDO::PARAM_INT);
            $stmt->execute();

            echo json_encode(["message" => "Frase actualizada correctamente"]);
        }
        break;

    case 'DELETE': // Eliminar una frase
        if ($endpoint === 'phrases') {
            $data = json_decode(file_get_contents("php://input"), true);
            if (!isset($data['id'])) {
                echo json_encode(["error" => "ID es obligatorio"]);
                exit;
            }

            $sql = "DELETE FROM phrases WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':id', (int) $data['id'], PDO::PARAM_INT);
            $stmt->execute();

            echo json_encode(["message" => "Frase eliminada correctamente"]);
        }
        break;

    default:
        echo json_encode(["error" => "Método no permitido"]);
        break;
}
?>

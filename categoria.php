<?php
header('Content-Type: application/json');
require 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$endpoint = $_GET['endpoint'] ?? '';
$page = $_GET['page'] ?? 1;
$limit = $_GET['limit'] ?? 10;

// Paginación
$offset = ($page - 1) * $limit;

switch ($method) {
    case 'GET':
        if ($endpoint === 'categoria_web') {
            // Búsqueda
            $search = $_GET['search'] ?? '';
            $sql = "SELECT * FROM categoria_web WHERE categoriaweb LIKE :search LIMIT :limit OFFSET :offset";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':search', "%$search%");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['data' => $result]);
        }
        break;

    case 'POST':
        if ($endpoint === 'categoria_web') {
            // Alta
            $data = json_decode(file_get_contents('php://input'), true);
            $sql = "INSERT INTO categoria_web (categoriaweb, estado, imagen)
                    VALUES (:categoriaweb, :estado, :imagen)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);
            echo json_encode(['message' => 'Categoría creada exitosamente']);
        }
        break;

    case 'PUT':
        if ($endpoint === 'categoria_web') {
            // Modificación
            $id = $_GET['id'] ?? null;
            if ($id) {
                $data = json_decode(file_get_contents('php://input'), true);
                $sql = "UPDATE categoria_web SET categoriaweb = :categoriaweb, estado = :estado, imagen = :imagen WHERE idcategoriaweb = :idcategoriaweb";
                $data['idcategoriaweb'] = $id;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($data);
                echo json_encode(['message' => 'Categoría actualizada exitosamente']);
            } else {
                echo json_encode(['error' => 'ID no proporcionado']);
            }
        }
        break;

    case 'DELETE':
        if ($endpoint === 'categoria_web') {
            // Baja
            $id = $_GET['id'] ?? null;
            if ($id) {
                $sql = "DELETE FROM categoria_web WHERE idcategoriaweb = :idcategoriaweb";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['idcategoriaweb' => $id]);
                echo json_encode(['message' => 'Categoría eliminada exitosamente']);
            } else {
                echo json_encode(['error' => 'ID no proporcionado']);
            }
        }
        break;

    default:
        echo json_encode(['error' => 'Método no soportado']);
        break;
}
?>

<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permite solicitudes desde cualquier dominio
require 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$endpoint = $_GET['endpoint'] ?? '';
$page = $_GET['page'] ?? 1;
$limit = $_GET['limit'] ?? 10;

// Paginación
$offset = ($page - 1) * $limit;

switch ($method) {
    case 'GET':
        if ($endpoint === 'categoria') {
            // Búsqueda
            $search = $_GET['search'] ?? '';

            // Obtener el total de registros que coinciden
            $countSql = "SELECT COUNT(*) as total FROM categoria_web WHERE nombre LIKE :search";
            $countStmt = $pdo->prepare($countSql);
            $countStmt->bindValue(':search', "%$search%");
            $countStmt->execute();
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Calcular el número total de páginas
            $totalPages = ceil($total / $limit);

            // Obtener los registros con límite y desplazamiento
            $sql = "SELECT idcategoriaweb, nombre, estado, imagen, descripcion 
                    FROM categoria_web 
                    WHERE nombre LIKE :search 
                    LIMIT :limit OFFSET :offset";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':search', "%$search%");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Enviar respuesta con datos y paginación
            echo json_encode([
                'categories' => $result,       // Datos de las categorías
                'total' => $total,            // Total de categorías
                'totalPages' => $totalPages,  // Total de páginas
                'currentPage' => (int)$page,  // Página actual
            ]);
        }
        break;

    case 'POST':
        if ($endpoint === 'categoria') {
            // Alta
            $data = json_decode(file_get_contents('php://input'), true);
            $sql = "INSERT INTO categoria_web (nombre, estado, imagen, descripcion)
                    VALUES (:nombre, :estado, :imagen, :descripcion)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);
            echo json_encode(['message' => 'Categoría creada exitosamente']);
        }
        break;

    case 'PUT':
        if ($endpoint === 'categoria') {
            // Modificación
            $id = $_GET['id'] ?? null;
            if ($id) {
                $data = json_decode(file_get_contents('php://input'), true);
                $sql = "UPDATE categoria_web 
                        SET nombre = :nombre, estado = :estado, imagen = :imagen, descripcion = :descripcion
                        WHERE idcategoriaweb = :idcategoriaweb";
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
        if ($endpoint === 'categoria') {
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
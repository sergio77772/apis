<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require 'db.php'; // Archivo que contiene la conexión a la base de datos

// Método HTTP y parámetros
$method = $_SERVER['REQUEST_METHOD'];
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;

switch ($method) {
    case 'GET':
        if ($endpoint === 'productos') {
            // Paginación
            $countQuery = "SELECT COUNT(*) as total FROM productos";
            $countResult = $conn->query($countQuery);
            $total = $countResult->fetch_assoc()['total'];

            $query = "SELECT * FROM productos LIMIT $limit OFFSET $offset";
            $result = $conn->query($query);

            $productos = [];
            while ($row = $result->fetch_assoc()) {
                $productos[] = $row;
            }

            echo json_encode([
                'productos' => $productos,
                'pagina' => $page,
                'totalPaginas' => ceil($total / $limit),
                'totalRegistros' => $total
            ]);
        }
        break;

    case 'POST':
        if ($endpoint === 'productos') {
            $data = json_decode(file_get_contents('php://input'), true);

            $campos = implode(", ", array_keys($data));
            $placeholders = "'" . implode("', '", array_map([$conn, 'real_escape_string'], $data)) . "'";

            $sql = "INSERT INTO productos ($campos) VALUES ($placeholders)";
            if ($conn->query($sql)) {
                echo json_encode(['message' => 'Producto creado con éxito']);
            } else {
                echo json_encode(['error' => 'Error al crear producto: ' . $conn->error]);
            }
        }
        break;

    case 'PUT':
        if ($endpoint === 'productos') {
            $codigo = isset($_GET['codigo']) ? $conn->real_escape_string($_GET['codigo']) : null;
            if (!$codigo) {
                echo json_encode(['error' => 'Código del producto no proporcionado']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            $updates = [];
            foreach ($data as $key => $value) {
                $updates[] = sprintf("%s = '%s'", $conn->real_escape_string($key), $conn->real_escape_string($value));
            }

            $sql = "UPDATE productos SET " . implode(", ", $updates) . " WHERE CODIGOARTICULO = '$codigo'";
            if ($conn->query($sql)) {
                echo json_encode(['message' => 'Producto actualizado con éxito']);
            } else {
                echo json_encode(['error' => 'Error al actualizar producto: ' . $conn->error]);
            }
        }
        break;

    case 'DELETE':
        if ($endpoint === 'productos') {
            $codigo = isset($_GET['codigo']) ? $conn->real_escape_string($_GET['codigo']) : null;
            if (!$codigo) {
                echo json_encode(['error' => 'Código del producto no proporcionado']);
                exit;
            }

            $sql = "DELETE FROM productos WHERE CODIGOARTICULO = '$codigo'";
            if ($conn->query($sql)) {
                echo json_encode(['message' => 'Producto eliminado con éxito']);
            } else {
                echo json_encode(['error' => 'Error al eliminar producto: ' . $conn->error]);
            }
        }
        break;

    default:
        echo json_encode(['error' => 'Método no soportado']);
        break;
}

$conn->close();

<?php
header('Content-Type: application/json');
require 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$endpoint = isset($_GET['endpoint']) ? $_GET['endpoint'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;

switch ($method) {
    case 'GET':
        if ($endpoint === 'clientes') {
            $search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
            
            // Obtener el total de registros
            $countQuery = "SELECT COUNT(*) AS total FROM clientes WHERE NOMBREAPELLIDO LIKE '%$search%' OR DNICUIT LIKE '%$search%'";
            $countResult = $conn->query($countQuery);
            $total = $countResult->fetch_assoc()['total'];

            if ($total === 0) {
                echo json_encode(['data' => [], 'message' => 'No hay clientes que coincidan']);
                exit;
            }

            // Consulta principal con paginación
            $query = "SELECT * FROM clientes WHERE NOMBREAPELLIDO LIKE '%$search%' OR DNICUIT LIKE '%$search%' LIMIT $limit OFFSET $offset";
            $result = $conn->query($query);

            $clientes = [];
            while ($row = $result->fetch_assoc()) {
                $clientes[] = $row;
            }

            echo json_encode([
                'data' => $clientes,
                'total' => $total,
                'page' => $page,
                'limit' => $limit
            ]);
        }
        break;

    case 'POST':
        if ($endpoint === 'clientes') {
            $data = json_decode(file_get_contents('php://input'), true);

            // Validar campos requeridos
            if (!isset($data['DNICUIT']) || !isset($data['NOMBREAPELLIDO'])) {
                echo json_encode(['error' => 'Faltan campos requeridos']);
                exit;
            }

            $stmt = $conn->prepare(
                "INSERT INTO clientes (DNICUIT, CONDICIONIVA, NOMBREAPELLIDO, CUIL, DIRECCION, LOCALIDAD, PROVINCIA, NACIONALIDAD, TELEFONO, FECHANACIMIENTO, ESTADOCIVIL, FOTO, FECHAALTA, ESTADO, EMAIL, CUENTACORRIENTE, LIMITECUENTACORRIENTE, COLORCALIFICACION) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param(
                "ssssssssssssssdids",
                $data['DNICUIT'], $data['CONDICIONIVA'], $data['NOMBREAPELLIDO'], $data['CUIL'], $data['DIRECCION'],
                $data['LOCALIDAD'], $data['PROVINCIA'], $data['NACIONALIDAD'], $data['TELEFONO'], $data['FECHANACIMIENTO'],
                $data['ESTADOCIVIL'], $data['FOTO'], $data['FECHAALTA'], $data['ESTADO'], $data['EMAIL'], 
                $data['CUENTACORRIENTE'], $data['LIMITECUENTACORRIENTE'], $data['COLORCALIFICACION']
            );

            if ($stmt->execute()) {
                echo json_encode(['message' => 'Cliente creado exitosamente']);
            } else {
                echo json_encode(['error' => 'Error al crear cliente: ' . $stmt->error]);
            }
            $stmt->close();
        }
        break;

    case 'PUT':
        if ($endpoint === 'clientes') {
            $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

            if (!$id) {
                echo json_encode(['error' => 'ID no proporcionado']);
                exit;
            }

            $data = json_decode(file_get_contents('php://input'), true);
            if (empty($data)) {
                echo json_encode(['error' => 'Datos vacíos']);
                exit;
            }

            $updates = [];
            foreach ($data as $key => $value) {
                $updates[] = sprintf("%s = '%s'", $conn->real_escape_string($key), $conn->real_escape_string($value));
            }

            $sql = sprintf("UPDATE clientes SET %s WHERE IDCLIENTE = %d", implode(', ', $updates), $id);

            if ($conn->query($sql) === TRUE) {
                echo json_encode(['message' => 'Cliente actualizado exitosamente']);
            } else {
                echo json_encode(['error' => 'Error al actualizar cliente: ' . $conn->error]);
            }
        }
        break;

    case 'DELETE':
        if ($endpoint === 'clientes') {
            $id = isset($_GET['id']) ? (int)$_GET['id'] : null;

            if (!$id) {
                echo json_encode(['error' => 'ID no proporcionado']);
                exit;
            }

            $sql = "DELETE FROM clientes WHERE IDCLIENTE = $id";
            if ($conn->query($sql) === TRUE) {
                echo json_encode(['message' => 'Cliente eliminado exitosamente']);
            } else {
                echo json_encode(['error' => 'Error al eliminar cliente: ' . $conn->error]);
            }
        }
        break;

    default:
        echo json_encode(['error' => 'Método no soportado']);
        break;
}

$conn->close();
?>

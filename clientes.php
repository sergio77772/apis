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
        if ($endpoint === 'clientes') {
            // Búsqueda
            $search = $_GET['search'] ?? '';
            $sql = "SELECT * FROM clientes WHERE NOMBREAPELLIDO LIKE :search OR DNICUIT LIKE :search LIMIT :limit OFFSET :offset";
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
        if ($endpoint === 'clientes') {
            // Alta
            $data = json_decode(file_get_contents('php://input'), true);
            $sql = "INSERT INTO clientes (DNICUIT, CONDICIONIVA, NOMBREAPELLIDO, CUIL, DIRECCION, LOCALIDAD, PROVINCIA, NACIONALIDAD, TELEFONO, FECHANACIMIENTO, ESTADOCIVIL, FOTO, FECHAALTA, ESTADO, EMAIL, CUENTACORRIENTE, LIMITECUENTACORRIENTE, COLORCALIFICACION)
                    VALUES (:DNICUIT, :CONDICIONIVA, :NOMBREAPELLIDO, :CUIL, :DIRECCION, :LOCALIDAD, :PROVINCIA, :NACIONALIDAD, :TELEFONO, :FECHANACIMIENTO, :ESTADOCIVIL, :FOTO, :FECHAALTA, :ESTADO, :EMAIL, :CUENTACORRIENTE, :LIMITECUENTACORRIENTE, :COLORCALIFICACION)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);
            echo json_encode(['message' => 'Cliente creado exitosamente']);
        }
        break;

    case 'PUT':
        if ($endpoint === 'clientes') {
            // Modificación
            $id = $_GET['id'] ?? null;
            if ($id) {
                $data = json_decode(file_get_contents('php://input'), true);
                $sql = "UPDATE clientes SET DNICUIT = :DNICUIT, CONDICIONIVA = :CONDICIONIVA, NOMBREAPELLIDO = :NOMBREAPELLIDO, CUIL = :CUIL, DIRECCION = :DIRECCION, LOCALIDAD = :LOCALIDAD, PROVINCIA = :PROVINCIA, NACIONALIDAD = :NACIONALIDAD, TELEFONO = :TELEFONO, FECHANACIMIENTO = :FECHANACIMIENTO, ESTADOCIVIL = :ESTADOCIVIL, FOTO = :FOTO, FECHAALTA = :FECHAALTA, ESTADO = :ESTADO, EMAIL = :EMAIL, CUENTACORRIENTE = :CUENTACORRIENTE, LIMITECUENTACORRIENTE = :LIMITECUENTACORRIENTE, COLORCALIFICACION = :COLORCALIFICACION WHERE IDCLIENTE = :IDCLIENTE";
                $data['IDCLIENTE'] = $id;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($data);
                echo json_encode(['message' => 'Cliente actualizado exitosamente']);
            } else {
                echo json_encode(['error' => 'ID no proporcionado']);
            }
        }
        break;

    case 'DELETE':
        if ($endpoint === 'clientes') {
            // Baja
            $id = $_GET['id'] ?? null;
            if ($id) {
                $sql = "DELETE FROM clientes WHERE IDCLIENTE = :IDCLIENTE";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['IDCLIENTE' => $id]);
                echo json_encode(['message' => 'Cliente eliminado exitosamente']);
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

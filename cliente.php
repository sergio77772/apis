<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permite solicitudes desde cualquier dominio
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
require 'db.php';

$method = $_SERVER['REQUEST_METHOD'];
$endpoint = $_GET['endpoint'] ?? '';
$page = $_GET['page'] ?? 1;
$limit = $_GET['limit'] ?? 10;

$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/img/'; // Directorio en la raíz del servidor

// Paginación
$offset = ($page - 1) * $limit;

switch ($method) {
    case 'GET':
        if ($endpoint === 'cliente') {
            $search = $_GET['search'] ?? '';

            // Obtener el total de registros
            $countSql = "SELECT COUNT(*) as total FROM clientes_web WHERE nombreapellido LIKE :search";
            $countStmt = $pdo->prepare($countSql);
            $countStmt->bindValue(':search', "%$search%");
            $countStmt->execute();
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Calcular páginas totales
            $totalPages = ceil($total / $limit);

            // Obtener los registros
            $sql = "SELECT idcliente,dnicuit,condicioniva,nombreapellido,cuil,direccion,localidad,provincia,nacionalidad,telefono,email,fechanacimiento,estadocivil,cuentacorriente,limitecuentacorriente,calificacion,estado,fechaalta,foto
                    FROM clientes_web 
                    WHERE nombreapellido LIKE :search 
                      ORDER BY nombreapellido
                    LIMIT :limit OFFSET :offset";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':search', "%$search%");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Respuesta JSON
            echo json_encode([
                'Cliente' => $result,
                'total' => $total,
                'totalPages' => $totalPages,
                'currentPage' => (int)$page,
            ]);
        }
        break;

    case 'POST':
        if ($endpoint === 'cliente') {
            $data = json_decode(file_get_contents('php://input'), true);
            $sql = "INSERT INTO clientes_web (dnicuit,  condicioniva, nombreapellido, cuil, direccion, localidad, provincia, nacionalidad, telefono, email, fechanacimiento, estadocivil ,cuentacorriente, limitecuentacorriente, calificacion, estado, fechaalta, foto ) VALUES (:dnicuit , :condicioniva, :nombreapellido, :cuil, :direccion, :localidad, :provincia, :nacionalidad, :telefono, :email, :fechanacimiento, :estadocivil, :cuentacorriente, :limitecuentacorriente, :calificacion, :estado, :fechaalta, :foto)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);
            echo json_encode(['message' => 'Cliente creada exitosamente']);
        } elseif ($endpoint === 'upload') {
            // Subida de imagen
            if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['foto'];
                $fileName = basename($file['name']);
                $targetFilePath = $uploadDir . $fileName;

                // Crear directorio si no existe
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                // Validar el tipo de archivo
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (in_array($file['type'], $allowedTypes)) {
                    if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
                        echo json_encode(['message' => 'Imagen subida exitosamente', 'filePath' => "/img/$fileName"]);
                    } else {
                        echo json_encode(['error' => 'Error al mover el archivo']);
                    }
                } else {
                    echo json_encode(['error' => 'Tipo de archivo no permitido']);
                }
            } else {
                echo json_encode(['error' => 'No se recibió un archivo válido']);
            }
        }
        break;

    case 'PUT':
        if ($endpoint === 'cliente') {
            $id = $_GET['id'] ?? null;
            if ($id) {
                $data = json_decode(file_get_contents('php://input'), true);
                $sql = "UPDATE clientes_web 
                        SET dnicuit = :dnicuit,condicioniva = :condicioniva,nombreapellido = :nombreapellido,cuil = :cuil,direccion = :direccion,localidad = :localidad,provincia = :provincia,nacionalidad = :nacionalidad,telefono = :telefono,email = :email,fechanacimiento = :fechanacimiento,estadocivil = :estadocivil,cuentacorriente = :cuentacorriente, limitecuentacorriente= :limitecuentacorriente,calificacion = :calificacion,estado = :estado,fechaalta = :fechaalta,foto = :foto
                        WHERE idcliente = :idcliente";
                        
                $data['idcliente'] = $id;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($data);
                echo json_encode(['message' => 'Cliente actualizada exitosamente']);
            } else {
                echo json_encode(['error' => 'ID no proporcionado']);
            }
        }
        break;

    case 'DELETE':
        if ($endpoint === 'cliente') {
            $id = $_GET['id'] ?? null;
            if ($id) {
                $sql = "DELETE FROM clientes_web WHERE idcliente = :idcliente";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['idcliente' => $id]);
                echo json_encode(['message' => 'Cliente eliminada exitosamente']);
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

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
        if ($endpoint === 'venta') {
            $search = $_GET['search'] ?? '';

            // Obtener el total de registros
            $countSql = "SELECT COUNT(*) as total FROM ventas_web WHERE descripcion LIKE :search";
            $countStmt = $pdo->prepare($countSql);
            $countStmt->bindValue(':search', "%$search%");
            $countStmt->execute();
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Calcular páginas totales
            $totalPages = ceil($total / $limit);

            // Obtener los registros
            $sql = "SELECT idventa,preciocosto, precioventa, unidadmedida,cantidad,importe,descripcion,codigoArticulo,idfactura,imagen
                    FROM ventas_web 
                    WHERE descripcion LIKE :search 
                    LIMIT :limit OFFSET :offset";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':search', "%$search%");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Respuesta JSON
            echo json_encode([
                'venta' => $result,
                'total' => $total,
                'totalPages' => $totalPages,
                'currentPage' => (int)$page,
            ]);





        }
        break;

    case 'POST':
        if ($endpoint === 'venta') {
            $data = json_decode(file_get_contents('php://input'), true);
            $sql = "INSERT INTO categoria_web (preciocosto, precioventa, unidadmedida,cantidad,importe,descripcion,codigoArticulo,idfactura,imagen)
            VALUES (:preciocosto, :precioventa, :unidadmedida,:cantidad,:importe,:descripcion,:codigoArticulo,:idfactura,:imagen)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);
  
            echo json_encode(['message' => 'Ventas creada exitosamente']);





        } elseif ($endpoint === 'upload') {
            // Subida de imagen
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['image'];
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
        if ($endpoint === 'venta') {
            $id = $_GET['id'] ?? null;
            if ($id) {
                $data = json_decode(file_get_contents('php://input'), true);
                $sql = "UPDATE ventas_web 
                        SET preciocosto = :preciocosto, precioventa = :precioventa, unidadmedida = :unidadmedida,cantidad = :cantidad,importe = :importe,descripcion =:descripcion,codigoArticulo = :codigoArticulo,idfactura = :idfactura,imagen = :imagen)
                        WHERE idventa = :idventa";
                $data['idventa'] = $id;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($data);

           



                echo json_encode(['message' => 'Categoría actualizada exitosamente']);
            } else {
                echo json_encode(['error' => 'ID no proporcionado']);
            }
        }
        break;

    case 'DELETE':
        if ($endpoint === 'venta') {
            $id = $_GET['id'] ?? null;
            if ($id) {
                $sql = "DELETE FROM ventas_web WHERE idventa = :idventa";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['idventa' => $id]);

  



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

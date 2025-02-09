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
        if ($endpoint === 'producto') {
            $search = $_GET['search'] ?? '';

            // Obtener el total de registros
            $countSql = "SELECT COUNT(*) as total FROM productos_web WHERE descripcion LIKE :search";
            $countStmt = $pdo->prepare($countSql);
            $countStmt->bindValue(':search', "%$search%");
            $countStmt->execute();
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            // Calcular páginas totales
            $totalPages = ceil($total / $limit);

            // Obtener los registros
            $sql = "SELECT idproducto,idcategoria,idsubcategoria,idproveedor, descripcion,precioventa,preciocosto,deposito,ubicacion,stockmin,stock,stockmax,descripcioncompleta,codigoArticulo, estado, nivel, imagen
                    FROM productos_web 
                    WHERE descripcion LIKE :search 
                    ORDER BY idcategoria desc , idsubcategoria desc ,codigoArticulo asc
                    LIMIT :limit OFFSET :offset";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':search', "%$search%");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Respuesta JSON
            echo json_encode([
                'producto' => $result,
                'total' => $total,
                'totalPages' => $totalPages,
                'currentPage' => (int)$page,
            ]);
      




        }
        break;

    case 'POST':
        if ($endpoint === 'producto') {
            $data = json_decode(file_get_contents('php://input'), true);
            $sql = "INSERT INTO productos_web (idcategoria,idsubcategoria,idproveedor,descripcion,precioventa,preciocosto,deposito,ubicacion,stockmin,stock,stockmax,descripcioncompleta,codigoArticulo, estado,nivel, imagen)
                    VALUES (:idcategoria,:idsubcategoria,:idproveedor,:descripcion,:precioventa,:preciocosto,:deposito,:ubicacion,:stockmin,:stock,:stockmax,:descripcioncompleta,:codigoArticulo, :estado, :nivel, :imagen)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);
 

            echo json_encode(['message' => 'Categoría creada exitosamente']);
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
        if ($endpoint === 'producto') {
            $id = $_GET['id'] ?? null;
            if ($id) {
                $data = json_decode(file_get_contents('php://input'), true);
                $sql = "UPDATE productos_web 
                        SET idcategoria = :idcategoria ,idsubcategoria=:idsubcategoria,idproveedor = :idproveedor,descripcion = :descripcion,precioventa=:precioventa,preciocosto =:preciocosto,deposito =:deposito,ubicacion =:ubicacion,stockmin=:stockmin,stock=:stock,stockmax=:stockmax,descripcioncompleta=:descripcioncompleta,codigoArticulo=:codigoArticulo ,estado = :estado, nivel = :nivel, imagen = :imagen
                        WHERE idproducto = :idproducto";
                $data['idproducto'] = $id;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($data);








                echo json_encode(['message' => 'Producto actualizada exitosamente']);
            } else {
                echo json_encode(['error' => 'ID no proporcionado']);
            }
        }
        break;

    case 'DELETE':
        if ($endpoint === 'producto') {
            $id = $_GET['id'] ?? null;
            if ($id) {
                $sql = "DELETE FROM productos_web WHERE idproducto = :idproducto";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['idproducto' => $id]);

 

                echo json_encode(['message' => 'Producto eliminada exitosamente']);
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

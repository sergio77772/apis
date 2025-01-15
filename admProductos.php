<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permite solicitudes desde cualquier dominio
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require 'db.php'; // Archivo que contiene la conexión a la base de datos

$method = $_SERVER['REQUEST_METHOD'];
$endpoint = $_GET['endpoint'] ?? '';
$page = $_GET['page'] ?? 1;
$limit = $_GET['limit'] ?? 10;

$uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/img/'; // Directorio donde se guardarán las imágenes

// Paginación
$offset = ($page - 1) * $limit;

switch ($method) {
    case 'GET':
        if ($endpoint === 'productos') {
            $search = $_GET['search'] ?? '';
            
            // Consulta para contar el total de productos
            $countSql = "SELECT COUNT(*) as total FROM productos WHERE CODIGOARTICULO LIKE :search OR DESCRIPCION LIKE :search";
            $countStmt = $pdo->prepare($countSql);
            $countStmt->bindValue(':search', "%$search%");
            $countStmt->execute();
            $totalProducts = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

            $totalPages = ceil($totalProducts / $limit);

            // Consulta para obtener productos
            $sql = "SELECT * FROM productos WHERE CODIGOARTICULO LIKE :search OR DESCRIPCION LIKE :search LIMIT :limit OFFSET :offset";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':search', "%$search%");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'products' => $products,
                'totalProducts' => $totalProducts,
                'totalPages' => $totalPages,
                'currentPage' => (int)$page
            ]);
        }
        break;

    case 'POST':
        if ($endpoint === 'productos') {
            $data = json_decode(file_get_contents('php://input'), true);
            $sql = "INSERT INTO productos (CODIGOARTICULO, CATEGORIA, SUBCATEGORIA, MARCA, FECHAREGISTRO, TAMANIO, COLOR, PRECIOMULTIPLE, MONEDA, PRECIODOLAR, PRECIOVENTAUNIDAD, PRECIOVENTAUNIDADDOS, PRECIOVENTAUNIDADTRES, DESCRIPCION, DEPOSITO, UBICACION, ESTADO, IVA, PRECIODECOSTO, STOCKDISPONIBLE, ULTIMOSTOCKCARGADO, UNIDADDEMEDIDAENTERO, MEDIDAPESOENTERO, PRECIOVENTA1KG1M, PRECIOVENTA100G50CM, UNIDADESVENDIDAS, METROSKILOSVENDIDOS, VENTAPOR, STOCKMINIMO, FECHAVENCIMIENTO, RUTAETIQUETAPRECIO, DESCRIPCIONCOMPLETA, IDFOTOCATALOGO)
                    VALUES (:CODIGOARTICULO, :CATEGORIA, :SUBCATEGORIA, :MARCA, :FECHAREGISTRO, :TAMANIO, :COLOR, :PRECIOMULTIPLE, :MONEDA, :PRECIODOLAR, :PRECIOVENTAUNIDAD, :PRECIOVENTAUNIDADDOS, :PRECIOVENTAUNIDADTRES, :DESCRIPCION, :DEPOSITO, :UBICACION, :ESTADO, :IVA, :PRECIODECOSTO, :STOCKDISPONIBLE, :ULTIMOSTOCKCARGADO, :UNIDADDEMEDIDAENTERO, :MEDIDAPESOENTERO, :PRECIOVENTA1KG1M, :PRECIOVENTA100G50CM, :UNIDADESVENDIDAS, :METROSKILOSVENDIDOS, :VENTAPOR, :STOCKMINIMO, :FECHAVENCIMIENTO, :RUTAETIQUETAPRECIO, :DESCRIPCIONCOMPLETA, :IDFOTOCATALOGO)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);
            echo json_encode(['message' => 'Producto creado exitosamente']);
        } elseif ($endpoint === 'upload') {
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['image'];
                $fileName = basename($file['name']);
                $targetFilePath = $uploadDir . $fileName;

                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (in_array($file['type'], $allowedTypes)) {
                    if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
                        echo json_encode([
                            'message' => 'Imagen subida exitosamente',
                            'filePath' => "/img/$fileName"
                        ]);
                    } else {
                        echo json_encode(['error' => 'Error al mover el archivo']);
                    }
                } else {
                    echo json_encode(['error' => 'Tipo de archivo no permitido']);
                }
            } else {
                echo json_encode(['error' => 'Error al subir la imagen']);
            }
        }
        break;

    case 'PUT':
        if ($endpoint === 'productos') {
            $id = $_GET['id'] ?? null;
            if ($id) {
                // Obtener datos del formulario
                parse_str(file_get_contents("php://input"), $_PUT);
                $data = $_PUT;

                // Verificar si hay una imagen cargada
                if (isset($_FILES['IMAGE']) && $_FILES['IMAGE']['error'] === UPLOAD_ERR_OK) {
                    $file = $_FILES['IMAGE'];
                    $fileName = basename($file['name']);
                    $targetFilePath = $uploadDir . $fileName;
                    if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
                        $data['IDFOTOCATALOGO'] = "/img/$fileName"; // Asignar ruta de la imagen
                    } else {
                        echo json_encode(['error' => 'Error al subir la imagen']);
                        exit;
                    }
                }

                $sql = "UPDATE productos SET CODIGOARTICULO = :CODIGOARTICULO, CATEGORIA = :CATEGORIA, SUBCATEGORIA = :SUBCATEGORIA, MARCA = :MARCA, FECHAREGISTRO = :FECHAREGISTRO, TAMANIO = :TAMANIO, COLOR = :COLOR, PRECIOMULTIPLE = :PRECIOMULTIPLE, MONEDA = :MONEDA, PRECIODOLAR = :PRECIODOLAR, PRECIOVENTAUNIDAD = :PRECIOVENTAUNIDAD, PRECIOVENTAUNIDADDOS = :PRECIOVENTAUNIDADDOS, PRECIOVENTAUNIDADTRES = :PRECIOVENTAUNIDADTRES, DESCRIPCION = :DESCRIPCION, DEPOSITO = :DEPOSITO, UBICACION = :UBICACION, ESTADO = :ESTADO, IVA = :IVA, PRECIODECOSTO = :PRECIODECOSTO, STOCKDISPONIBLE = :STOCKDISPONIBLE, ULTIMOSTOCKCARGADO = :ULTIMOSTOCKCARGADO, UNIDADDEMEDIDAENTERO = :UNIDADDEMEDIDAENTERO, MEDIDAPESOENTERO = :MEDIDAPESOENTERO, PRECIOVENTA1KG1M = :PRECIOVENTA1KG1M, PRECIOVENTA100G50CM = :PRECIOVENTA100G50CM, UNIDADESVENDIDAS = :UNIDADESVENDIDAS, METROSKILOSVENDIDOS = :METROSKILOSVENDIDOS, VENTAPOR = :VENTAPOR, STOCKMINIMO = :STOCKMINIMO, FECHAVENCIMIENTO = :FECHAVENCIMIENTO, RUTAETIQUETAPRECIO = :RUTAETIQUETAPRECIO, DESCRIPCIONCOMPLETA = :DESCRIPCIONCOMPLETA, IDFOTOCATALOGO = :IDFOTOCATALOGO WHERE idproducto = :idproducto";
                $data['idproducto'] = $id;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($data);
                echo json_encode(['message' => 'Producto actualizado exitosamente']);
            } else {
                echo json_encode(['error' => 'ID no proporcionado']);
            }
        }
        break;

    case 'DELETE':
        if ($endpoint === 'productos') {
            $id = $_GET['id'] ?? null;
            if ($id) {
                $sql = "DELETE FROM productos WHERE idproducto = :idproducto";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['idproducto' => $id]);
                echo json_encode(['message' => 'Producto eliminado exitosamente']);
            } else {
                echo json_encode(['error' => 'ID no proporcionado']);
            }
        }
        break;

    default:
        echo json_encode(['error' => 'Método no permitido']);
        break;
}
?>

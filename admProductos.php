<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
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
    case 'PUT':
        if ($endpoint === 'productos') {
            $id = $_GET['id'] ?? null;
            if ($id) {
                // Obtener los datos del cuerpo de la solicitud PUT
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

                // Definir las columnas que pueden ser actualizadas
                $columns = [
                    'CODIGOARTICULO', 'CATEGORIA', 'SUBCATEGORIA', 'MARCA', 'FECHAREGISTRO', 'TAMANIO', 
                    'COLOR', 'PRECIOMULTIPLE', 'MONEDA', 'PRECIODOLAR', 'PRECIOVENTAUNIDAD', 
                    'PRECIOVENTAUNIDADDOS', 'PRECIOVENTAUNIDADTRES', 'DESCRIPCION', 'DEPOSITO', 'UBICACION',
                    'ESTADO', 'IVA', 'PRECIODECOSTO', 'STOCKDISPONIBLE', 'ULTIMOSTOCKCARGADO', 
                    'UNIDADDEMEDIDAENTERO', 'MEDIDAPESOENTERO', 'PRECIOVENTA1KG1M', 'PRECIOVENTA100G50CM', 
                    'UNIDADESVENDIDAS', 'METROSKILOSVENDIDOS', 'VENTAPOR', 'STOCKMINIMO', 'FECHAVENCIMIENTO', 
                    'RUTAETIQUETAPRECIO', 'DESCRIPCIONCOMPLETA', 'IDFOTOCATALOGO'
                ];

                // Filtrar los datos recibidos para asegurarnos de que solo los campos válidos se actualicen
                $fieldsToUpdate = array_intersect_key($data, array_flip($columns));

                if (empty($fieldsToUpdate)) {
                    echo json_encode(['error' => 'No se han proporcionado datos válidos para actualizar']);
                    exit;
                }

                // Construir la parte de la consulta SQL con los campos a actualizar
                $setClauses = [];
                foreach ($fieldsToUpdate as $key => $value) {
                    $setClauses[] = "$key = :$key";
                }
                $setSql = implode(", ", $setClauses);

                // Construir la consulta SQL final
                $sql = "UPDATE productos SET $setSql WHERE idproducto = :idproducto";
                $stmt = $pdo->prepare($sql);

                // Bind de todos los parámetros para la ejecución de la consulta
                $fieldsToUpdate['idproducto'] = $id;
                foreach ($fieldsToUpdate as $key => $value) {
                    $stmt->bindValue(":$key", $value);
                }

                // Ejecutar la actualización
                if ($stmt->execute()) {
                    echo json_encode(['message' => 'Producto actualizado exitosamente']);
                } else {
                    echo json_encode(['error' => 'Error al actualizar el producto']);
                }
            } else {
                echo json_encode(['error' => 'ID no proporcionado']);
            }
        }
        break;

    case 'GET':
        if ($endpoint === 'productos') {
            // Consulta de productos (con paginación)
            $sql = "SELECT * FROM productos LIMIT :limit OFFSET :offset";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($productos);
        }
        break;

    case 'POST':
        if ($endpoint === 'productos') {
            // Crear un nuevo producto
            parse_str(file_get_contents("php://input"), $_POST);
            $data = $_POST;

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

            // Inserción del nuevo producto
            $columns = implode(", ", array_keys($data));
            $values = ":" . implode(", :", array_keys($data));

            $sql = "INSERT INTO productos ($columns) VALUES ($values)";
            $stmt = $pdo->prepare($sql);

            foreach ($data as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }

            if ($stmt->execute()) {
                echo json_encode(['message' => 'Producto creado exitosamente']);
            } else {
                echo json_encode(['error' => 'Error al crear el producto']);
            }
        }
        break;

    case 'DELETE':
        if ($endpoint === 'productos') {
            $id = $_GET['id'] ?? null;
            if ($id) {
                // Eliminar el producto
                $sql = "DELETE FROM productos WHERE idproducto = :idproducto";
                $stmt = $pdo->prepare($sql);
                $stmt->bindValue(':idproducto', $id, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    echo json_encode(['message' => 'Producto eliminado exitosamente']);
                } else {
                    echo json_encode(['error' => 'Error al eliminar el producto']);
                }
            } else {
                echo json_encode(['error' => 'ID no proporcionado']);
            }
        }
        break;

    default:
        echo json_encode(['error' => 'Método no permitido']);
        break;
}

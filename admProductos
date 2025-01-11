<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permite solicitudes desde cualquier dominio

require 'db.php'; // Archivo que contiene la conexión a la base de datos

$method = $_SERVER['REQUEST_METHOD'];
$endpoint = $_GET['endpoint'] ?? '';
$page = $_GET['page'] ?? 1;
$limit = $_GET['limit'] ?? 10;

// Paginación
$offset = ($page - 1) * $limit;

switch ($method) {
    case 'GET':
        if ($endpoint === 'productos') {
            // Búsqueda de productos
            $search = $_GET['search'] ?? '';
            $sql = "SELECT * FROM productos WHERE CODIGOARTICULO LIKE :search OR DESCRIPCION LIKE :search LIMIT :limit OFFSET :offset";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':search', "%$search%");
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
           echo json_encode($result);
        }
        break;

    case 'POST':
        if ($endpoint === 'productos') {
            // Alta de producto
            $data = json_decode(file_get_contents('php://input'), true);
            $sql = "INSERT INTO productos (CODIGOARTICULO, CATEGORIA, SUBCATEGORIA, MARCA, FECHAREGISTRO, TAMANIO, COLOR, PRECIOMULTIPLE, MONEDA, PRECIODOLAR, PRECIOVENTAUNIDAD, PRECIOVENTAUNIDADDOS, PRECIOVENTAUNIDADTRES, DESCRIPCION, DEPOSITO, UBICACION, ESTADO, IVA, PRECIODECOSTO, STOCKDISPONIBLE, ULTIMOSTOCKCARGADO, UNIDADDEMEDIDAENTERO, MEDIDAPESOENTERO, PRECIOVENTA1KG1M, PRECIOVENTA100G50CM, UNIDADESVENDIDAS, METROSKILOSVENDIDOS, VENTAPOR, STOCKMINIMO, FECHAVENCIMIENTO, RUTAETIQUETAPRECIO, DESCRIPCIONCOMPLETA, IDFOTOCATALOGO)
                    VALUES (:CODIGOARTICULO, :CATEGORIA, :SUBCATEGORIA, :MARCA, :FECHAREGISTRO, :TAMANIO, :COLOR, :PRECIOMULTIPLE, :MONEDA, :PRECIODOLAR, :PRECIOVENTAUNIDAD, :PRECIOVENTAUNIDADDOS, :PRECIOVENTAUNIDADTRES, :DESCRIPCION, :DEPOSITO, :UBICACION, :ESTADO, :IVA, :PRECIODECOSTO, :STOCKDISPONIBLE, :ULTIMOSTOCKCARGADO, :UNIDADDEMEDIDAENTERO, :MEDIDAPESOENTERO, :PRECIOVENTA1KG1M, :PRECIOVENTA100G50CM, :UNIDADESVENDIDAS, :METROSKILOSVENDIDOS, :VENTAPOR, :STOCKMINIMO, :FECHAVENCIMIENTO, :RUTAETIQUETAPRECIO, :DESCRIPCIONCOMPLETA, :IDFOTOCATALOGO)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($data);
            echo json_encode(['message' => 'Producto creado exitosamente']);
        }
        break;

    case 'PUT':
        if ($endpoint === 'productos') {
            // Modificación de producto
            $id = $_GET['id'] ?? null;
            if ($id) {
                $data = json_decode(file_get_contents('php://input'), true);
                $sql = "UPDATE productos SET CODIGOARTICULO = :CODIGOARTICULO, CATEGORIA = :CATEGORIA, SUBCATEGORIA = :SUBCATEGORIA, MARCA = :MARCA, FECHAREGISTRO = :FECHAREGISTRO, TAMANIO = :TAMANIO, COLOR = :COLOR, PRECIOMULTIPLE = :PRECIOMULTIPLE, MONEDA = :MONEDA, PRECIODOLAR = :PRECIODOLAR, PRECIOVENTAUNIDAD = :PRECIOVENTAUNIDAD, PRECIOVENTAUNIDADDOS = :PRECIOVENTAUNIDADDOS, PRECIOVENTAUNIDADTRES = :PRECIOVENTAUNIDADTRES, DESCRIPCION = :DESCRIPCION, DEPOSITO = :DEPOSITO, UBICACION = :UBICACION, ESTADO = :ESTADO, IVA = :IVA, PRECIODECOSTO = :PRECIODECOSTO, STOCKDISPONIBLE = :STOCKDISPONIBLE, ULTIMOSTOCKCARGADO = :ULTIMOSTOCKCARGADO, UNIDADDEMEDIDAENTERO = :UNIDADDEMEDIDAENTERO, MEDIDAPESOENTERO = :MEDIDAPESOENTERO, PRECIOVENTA1KG1M = :PRECIOVENTA1KG1M, PRECIOVENTA100G50CM = :PRECIOVENTA100G50CM, UNIDADESVENDIDAS = :UNIDADESVENDIDAS, METROSKILOSVENDIDOS = :METROSKILOSVENDIDOS, VENTAPOR = :VENTAPOR, STOCKMINIMO = :STOCKMINIMO, FECHAVENCIMIENTO = :FECHAVENCIMIENTO, RUTAETIQUETAPRECIO = :RUTAETIQUETAPRECIO, DESCRIPCIONCOMPLETA = :DESCRIPCIONCOMPLETA, IDFOTOCATALOGO = :IDFOTOCATALOGO WHERE IDPRODUCTO = :IDPRODUCTO";
                $data['IDPRODUCTO'] = $id;
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
            // Baja de producto
            $id = $_GET['id'] ?? null;
            if ($id) {
                $sql = "DELETE FROM productos WHERE IDPRODUCTO = :IDPRODUCTO";
                $stmt = $pdo->prepare($sql);
                $stmt->execute(['IDPRODUCTO' => $id]);
                echo json_encode(['message' => 'Producto eliminado exitosamente']);
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

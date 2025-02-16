<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require 'db.php';
$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = new Database();
    $pdo = $db->connect();

    // GUARDAR ORDEN
    if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'guardar_orden') {
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['usuario_id']) || empty($data['productos']) || !is_array($data['productos'])) {
            http_response_code(400);
            echo json_encode(["error" => "Faltan datos obligatorios"]);
            exit;
        }

        // Iniciar transacción
        $pdo->beginTransaction();

        // Insertar la orden con estado inicial "En proceso" (estado_id = 1)
        $sqlOrden = "INSERT INTO ordenes (usuario_id, estado_id, fecha) VALUES (:usuario_id, 1, NOW())";
        $stmt = $pdo->prepare($sqlOrden);
        $stmt->execute([":usuario_id" => $data['usuario_id']]);
        $orden_id = $pdo->lastInsertId();

        // Insertar productos en la orden
        $sqlProducto = "INSERT INTO ordenes_productos (orden_id, producto_id, cantidad, precio_unitario) 
                        SELECT :orden_id, id, :cantidad, precio FROM productos WHERE id = :producto_id";
        $stmtProducto = $pdo->prepare($sqlProducto);

        foreach ($data['productos'] as $producto) {
            if (!isset($producto['producto_id']) || !isset($producto['cantidad'])) {
                throw new Exception("Formato de producto inválido");
            }
            $stmtProducto->execute([
                ":orden_id" => $orden_id,
                ":cantidad" => $producto['cantidad'],
                ":producto_id" => $producto['producto_id']
            ]);
        }

        // Confirmar transacción
        $pdo->commit();

        http_response_code(201);
        echo json_encode(["success" => "Orden guardada exitosamente", "orden_id" => $orden_id]);
        exit;
    }

    http_response_code(400);
    echo json_encode(["error" => "Acción no válida"]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo json_encode(["error" => "Error interno del servidor: " . $e->getMessage()]);
}
?>

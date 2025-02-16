<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require 'db.php'; // Conexión a la base de datos

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'guardar_orden') {
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['user_id']) || empty($data['productos']) || !is_array($data['productos'])) {
            http_response_code(400);
            echo json_encode(["error" => "Faltan datos obligatorios"]);
            exit;
        }

        // Iniciar transacción
        $pdo->beginTransaction();

        // Insertar la orden con estado inicial "En proceso" (status_id = 1)
        $sqlOrden = "INSERT INTO orders (user_id, status_id, created_at) VALUES (:user_id, 1, NOW())";
        $stmt = $pdo->prepare($sqlOrden);
        $stmt->execute([":user_id" => $data['user_id']]);
        $order_id = $pdo->lastInsertId();

        // Insertar productos en la orden
        $sqlItem = "INSERT INTO order_items (order_id, product_id, cantidad) 
                    VALUES (:order_id, :product_id, :cantidad)";
        $stmtItem = $pdo->prepare($sqlItem);

        foreach ($data['productos'] as $producto) {
            if (!isset($producto['product_id']) || !isset($producto['cantidad'])) {
                throw new Exception("Formato de producto inválido");
            }
            $stmtItem->execute([
                ":order_id" => $order_id,
                ":product_id" => $producto['product_id'],
                ":cantidad" => $producto['cantidad']
            ]);
        }

        // Confirmar transacción
        $pdo->commit();

        http_response_code(201);
        echo json_encode(["success" => "Orden guardada exitosamente", "order_id" => $order_id]);
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

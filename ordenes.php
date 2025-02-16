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
    // Guardar orden (ya implementado)
    if ($method === 'POST' && isset($_GET['action']) && $_GET['action'] === 'guardar_orden') {
        $data = json_decode(file_get_contents("php://input"), true);

        if (empty($data['user_id']) || empty($data['productos']) || !is_array($data['productos'])) {
            http_response_code(400);
            echo json_encode(["error" => "Faltan datos obligatorios"]);
            exit;
        }

        $pdo->beginTransaction();

        $sqlOrden = "INSERT INTO orders (user_id, status_id, created_at) VALUES (:user_id, 1, NOW())";
        $stmt = $pdo->prepare($sqlOrden);
        $stmt->execute([":user_id" => $data['user_id']]);
        $order_id = $pdo->lastInsertId();

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

        $pdo->commit();
        http_response_code(201);
        echo json_encode(["success" => "Orden guardada exitosamente", "order_id" => $order_id]);
        exit;
    }

    // Obtener una orden por ID
    if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'obtener_orden' && isset($_GET['id'])) {
        $order_id = intval($_GET['id']);

        $sql = "SELECT o.id, o.user_id, os.status_name AS estado, o.created_at
                FROM orders o
                JOIN order_status os ON o.status_id = os.id
                WHERE o.id = :order_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([":order_id" => $order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            http_response_code(404);
            echo json_encode(["error" => "Orden no encontrada"]);
            exit;
        }

        $sqlItems = "SELECT oi.product_id, p.nombre AS producto, oi.cantidad, p.precio
                     FROM order_items oi
                     JOIN products p ON oi.product_id = p.id
                     WHERE oi.order_id = :order_id";
        $stmtItems = $pdo->prepare($sqlItems);
        $stmtItems->execute([":order_id" => $order_id]);
        $order['productos'] = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode($order);
        exit;
    }

    // Obtener todas las órdenes de un usuario
    if ($method === 'GET' && isset($_GET['action']) && $_GET['action'] === 'ordenes_usuario' && isset($_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);

        $sql = "SELECT o.id, os.status_name AS estado, o.created_at
                FROM orders o
                JOIN order_status os ON o.status_id = os.id
                WHERE o.user_id = :user_id
                ORDER BY o.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([":user_id" => $user_id]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode($orders);
        exit;
    }

    http_response_code(400);
    echo json_encode(["error" => "Acción no válida"]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Error interno del servidor: " . $e->getMessage()]);
}
?>

<?php

// Configuración de encabezados CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Content-Type: application/json");

// Incluir Mercado Pago SDK manualmente (sin Composer)
require_once 'src/MercadoPago/SDK.php';

// Configurar el Access Token
MercadoPago\SDK::setAccessToken("TEST-6302837217948837-013118-3c81ca4cc0839f8862b7037e8f379b29-154099748");

// Obtener la ruta solicitada
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

switch ($path) {
    case '':
    case '/':
        require __DIR__ . '/../../client/html-js/index.html';
        break;

    case '/create_payment':
        // Solo permitir POST
        if ($_SERVER["REQUEST_METHOD"] !== "POST") {
            http_response_code(405);
            echo json_encode(["error" => "Método no permitido"]);
            exit;
        }

        // Obtener datos JSON de la solicitud
        $json = file_get_contents("php://input");
        $data = json_decode($json, true);

        // Validar datos recibidos
        if (!isset($data["transaction_amount"]) || !isset($data["token"]) || !isset($data["payment_method_id"]) || !isset($data["payer"]["email"])) {
            http_response_code(400);
            echo json_encode(["error" => "Datos incompletos"]);
            exit;
        }

        // Crear el pago
        $payment = new MercadoPago\Payment();
        $payment->transaction_amount = (float) $data["transaction_amount"];
        $payment->token = $data["token"];
        $payment->description = $data["description"] ?? "Pago sin descripción";
        $payment->payment_method_id = $data["payment_method_id"];
        $payment->payer = ["email" => $data["payer"]["email"]];

        // Guardar el pago en Mercado Pago
        $payment->save();

        // Enviar la respuesta
        echo json_encode([
            "status" => $payment->status,
            "status_detail" => $payment->status_detail,
            "id" => $payment->id
        ]);
        break;

    case '/feedback':
        // Manejar respuesta de Mercado Pago
        $respuesta = [
            'Payment' => $_GET['payment_id'] ?? null,
            'Status' => $_GET['status'] ?? null,
            'MerchantOrder' => $_GET['merchant_order_id'] ?? null
        ];
        echo json_encode($respuesta);
        break;

    // Manejo de archivos estáticos
    default:
        $file = __DIR__ . '/../../client' . $path;
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $content = 'text/html';

        switch ($extension) {
            case 'js': $content = 'application/javascript'; break;
            case 'css': $content = 'text/css'; break;
            case 'png': $content = 'image/png'; break;
        }

        if (file_exists($file)) {
            header('Content-Type: ' . $content);
            readfile($file);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Archivo no encontrado"]);
        }
}


?>

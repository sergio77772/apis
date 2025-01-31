<?php

// Permitir solicitudes desde Postman (CORS)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

// Incluir Mercado Pago SDK
require_once 'src/MercadoPago/SDK.php';

// Configurar el Access Token
MercadoPago\SDK::setAccessToken("TEST-6302837217948837-013118-3c81ca4cc0839f8862b7037e8f379b29-154099748");

// Verificar si la solicitud es POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Obtener datos JSON del cuerpo de la solicitud
    $json = file_get_contents("php://input");
    $data = json_decode($json, true);

    // Validar datos recibidos
    if (!isset($data["transaction_amount"]) || !isset($data["token"]) || !isset($data["payment_method_id"]) || !isset($data["payer"]["email"])) {
        http_response_code(400);
        echo json_encode(["error" => "Datos incompletos"]);
        exit;
    }

    // Crear un pago
    $payment = new MercadoPago\Payment();
    $payment->transaction_amount = (float) $data["transaction_amount"];
    $payment->token = $data["token"];
    $payment->description = $data["description"] ?? "Pago sin descripción";
    $payment->payment_method_id = $data["payment_method_id"];
    $payment->payer = ["email" => $data["payer"]["email"]];

    // Guardar el pago
    $payment->save();

    // Responder con el estado del pago
    echo json_encode([
        "status" => $payment->status,
        "status_detail" => $payment->status_detail,
        "id" => $payment->id
    ]);
} else {
    http_response_code(405);
    echo json_encode(["error" => "Método no permitido"]);
}

?>

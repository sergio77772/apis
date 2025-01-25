<?php
// Incluye la clase PHPMailer manualmente
require __DIR__ . '/emails/PHPMailer.php';
require __DIR__ . '/emails/SMTP.php';
require __DIR__ . '/emails/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Verifica el método HTTP
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lee los datos enviados
    $input = json_decode(file_get_contents('php://input'), true);

    // Valida los datos requeridos
    if (!isset($input['name']) || !isset($input['email']) || !isset($input['message'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Faltan parámetros requeridos: name, email, message']);
        exit;
    }

    // Captura los datos del formulario
    $name = htmlspecialchars($input['name']);
    $email = htmlspecialchars($input['email']);
    $message = htmlspecialchars($input['message']);

    // Configura PHPMailer
    $mail = new PHPMailer(true);
    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host = 'c2651511.ferozo.com'; // Servidor SMTP
        $mail->SMTPAuth = true;
        $mail->Username = 'info@distribuidoraassefperico.com.ar'; // Usuario
        $mail->Password = 'Ly@or558vG'; // Contraseña
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465; // Puerto SMTP

        // Configuración del remitente y destinatario
        $mail->setFrom($email, $name); // Correo del remitente (quien envía el formulario)
        $mail->addAddress('info@distribuidoraassefperico.com.ar', 'Distribuidora Assef Perico'); // Casilla donde llegarán los correos

        // Configuración del contenido del correo
        $mail->isHTML(true);
        $mail->Subject = "Nuevo mensaje del formulario: $name";
        $mail->Body = "
            <h1>Nuevo mensaje desde el formulario</h1>
            <p><strong>Nombre:</strong> $name</p>
            <p><strong>Email:</strong> $email</p>
            <p><strong>Mensaje:</strong></p>
            <p>$message</p>
        ";

        // Enviar el correo
        $mail->send();
        http_response_code(200);
        echo json_encode(['message' => 'Correo recibido exitosamente. Gracias por contactarnos.']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'No se pudo enviar el correo: ' . $mail->ErrorInfo]);
    }
} else {
    // Respuesta para métodos distintos de POST
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido. Usa POST.']);
}
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
    if (!isset($input['to']) || !isset($input['subject']) || !isset($input['body'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Faltan parámetros requeridos: to, subject, body']);
        exit;
    }

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
        $mail->setFrom('info@distribuidoraassefperico.com.ar', 'Distribuidora Assef Perico');
        $mail->addAddress($input['to']); // Destinatario

        // Configuración del contenido del correo
        $mail->isHTML(true);
        $mail->Subject = $input['subject'];
        $mail->Body = $input['body'];

        // Enviar el correo
        $mail->send();
        http_response_code(200);
        echo json_encode(['message' => 'Correo enviado exitosamente']);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'No se pudo enviar el correo: ' . $mail->ErrorInfo]);
    }
} else {
    // Respuesta para métodos distintos de POST
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido. Usa POST.']);
}

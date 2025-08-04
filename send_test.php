<?php
// Permitir CORS para todas las solicitudes
header("Access-Control-Allow-Origin: http://127.0.0.1:5500");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Si es una petición OPTIONS (preflight), responder 200 y salir
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


// ==================== CONFIGURACIÓN SMTP ====================
define('SMTP_USER', 'Esteban.Corrales@serviceone.cl');
define('SMTP_PASS', 'Unli1401');
define('SMTP_HOST', 'smtp.office365.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');

// ==================== CARGA DE PHPMailer ====================
require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

// ==================== MANEJO DE PETICIONES ====================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['success' => false, 'error' => 'Método no permitido']));
}

// Leer datos JSON del cuerpo de la petición
$json = file_get_contents('php://input');
$data = json_decode($json, true);

try {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    // Configuración SMTP
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->Port       = SMTP_PORT;
    $mail->CharSet    = 'UTF-8';

    // Remitente y destinatario
    $mail->setFrom(SMTP_USER, 'Formulario de Retiro');
    $mail->addAddress(SMTP_USER); // Enviar a tu propio correo
    $mail->addReplyTo($data['correo'] ?? 'no-reply@serviceone.cl', $data['nombre'] ?? 'Cliente');

    // Contenido del correo
    $mail->isHTML(true);
    $mail->Subject = 'Solicitud de Retiro: ' . ($data['empresa'] ?? 'Sin empresa');
    
    // Construir cuerpo del mensaje
    $mail->Body = "<h1>Nueva solicitud de retiro</h1><table style='border-collapse: collapse; width: 100%;'>";
    foreach ($data as $key => $value) {
    $label = ucfirst(str_replace('_', ' ', $key));
    $mail->Body .= "
    <tr>
        <td style='padding: 8px; border: 1px solid #ddd;'><strong>$label</strong></td>
        <td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($value ?? 'No especificado') . "</td>
    </tr>";
    }
    $mail->Body .= "</table>";


    $mail->send();
    echo json_encode(['success' => true, 'message' => 'Correo enviado correctamente']);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error al enviar el correo',
        'debug' => $e->getMessage() // Solo para desarrollo
    ]);
}
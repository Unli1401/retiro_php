<?php
// ==================== CONFIGURACIÓN INICIAL ====================
header('Content-Type: application/json');
//header("Access-Control-Allow-Origin: https://serviceone.cl"); // Cambiar en producción
$allowedOrigins = [
    'https://serviceone.cl',
    'http://127.0.0.1:5500',
    'http://localhost:5500'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: $origin");
}
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
header("Access-Control-Allow-Credentials: true");
// ==================== PROCESAMIENTO DE DATOS ====================
$input = file_get_contents('php://input');
$data = json_decode($input, true) ?? [];

// Validar campos obligatorios
$requiredFields = [
    'empresa', 'nombre', 'correo', 'direccion', 'telefono',
    'tipo_retiro', 'cantidad', 'fecha_retiro', 'horario_retiro'
];

$missingFields = [];
foreach ($requiredFields as $field) {
    if (empty($data[$field])) {
        $missingFields[] = $field;
    }
}

if (!empty($missingFields)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Faltan campos obligatorios',
        'missing_fields' => $missingFields
    ]);
    exit;
}

// ==================== CONFIGURACIÓN PHPMailer ====================
require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

$mail = new PHPMailer\PHPMailer\PHPMailer(true);

try {
    // ==================== CONFIGURACIÓN SMTP ====================
    $mail->isSMTP();
    $mail->Host = 'smtp.office365.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'esteban.corrales@serviceone.cl'; // Cambiar en producción
    $mail->Password = 'Unli1401'; // Usar variables de entorno en producción
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;
    $mail->CharSet = 'UTF-8';
    $mail->Timeout = 30;

    // ==================== CONFIGURACIÓN CORREO ====================
    $mail->setFrom('esteban.corrales@serviceone.cl', 'ServiceOne Notificaciones');
    $mail->addAddress('esteban.corrales@serviceone.cl'); // Correo principal
    $mail->addCC('esteban.corrales@serviceone.cl'); // Copia para supervisión
    $mail->addReplyTo($data['correo'], $data['nombre']);
    
    // ==================== CONSTRUIR CONTENIDO ====================
    //$mail->Subject = 'Solicitud de Retiro #' . uniqid() . ' - ' . $data['empresa'];
    $mail->Subject = 'Solicitud de Retiro' . ' - ' . $data['empresa'];
    $mail->isHTML(true);
    
    // Variables auxiliares
    $contactoBackup = $data['nombre_backup'] ? 
        "{$data['nombre_backup']} (Tel: {$data['telefono_backup']}, Email: {$data['correo_backup']})" : 
        'No especificado';
    
    $destinoFinal = ($data['destino'] === 'otro') ? $data['otro_destino'] : $data['destino'];
    $horarioRetiro = ($data['horario_retiro'] === 'am') ? 'Mañana (9:00 - 12:00)' : 'Tarde (13:00 - 17:00)';
    
    $alturaMaximaTexto = '';
    if (!empty($data['altura_maxima'])) {
        $alturaMaximaTexto = '(Altura máx: ' . $data['altura_maxima'] . 'm)';
    }
    
    // Plantilla HTML profesional
    $mail->Body = <<<HTML
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: auto; border: 1px solid #e1e1e1;">
        <!-- Encabezado -->
        <div style="background-color: #333; padding: 20px; text-align: center;">
            <a href="https://serviceone.cl" target="_blank">
                <img src="https://serviceone.cl/img/tienda-logo-1678508457.jpg" 
                     style="height: 40px;" alt="ServiceOne Logo">
            </a>
            
        </div>
            <h2 style="color: #fff; margin: 10px 0 0;">Solicitud de Retiro de Equipos</h2>
        <!-- Cuerpo -->
        <div style="padding: 20px;">
            <h3 style="color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                Detalles de la Solicitud
            </h3>
            
            <table style="width: 100%; border-collapse: collapse; margin: 15px 0;">
                <tr>
                    <td style="padding: 8px; border: 1px solid #eee; width: 40%; font-weight: bold;">Empresa:</td>
                    <td style="padding: 8px; border: 1px solid #eee;">{$data['empresa']}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border: 1px solid #eee; font-weight: bold;">Solicitante:</td>
                    <td style="padding: 8px; border: 1px solid #eee;">
                        {$data['nombre']}<br>
                        {$data['correo']}<br>
                        {$data['telefono']}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px; border: 1px solid #eee; font-weight: bold;">Dirección:</td>
                    <td style="padding: 8px; border: 1px solid #eee;">{$data['direccion']}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border: 1px solid #eee; font-weight: bold;">Contacto Backup:</td>
                    <td style="padding: 8px; border: 1px solid #eee;">{$contactoBackup}</td>
                </tr>
            </table>
            
            <h3 style="color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top: 20px;">
                Detalles del Retiro
            </h3>
            
            <table style="width: 100%; border-collapse: collapse; margin: 15px 0;">
                <tr>
                    <td style="padding: 8px; border: 1px solid #eee; width: 40%; font-weight: bold;">Tipo de Retiro:</td>
                    <td style="padding: 8px; border: 1px solid #eee;">{$data['tipo_retiro']}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border: 1px solid #eee; font-weight: bold;">Cantidad:</td>
                    <td style="padding: 8px; border: 1px solid #eee;">{$data['cantidad']}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border: 1px solid #eee; font-weight: bold;">Fecha/Horario:</td>
                    <td style="padding: 8px; border: 1px solid #eee;">
                        {$data['fecha_retiro']} - {$horarioRetiro}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px; border: 1px solid #eee; font-weight: bold;">Modelos/Series:</td>
                    <td style="padding: 8px; border: 1px solid #eee;">{$data['modelos_series']}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border: 1px solid #eee; font-weight: bold;">Estado:</td>
                    <td style="padding: 8px; border: 1px solid #eee;">{$data['estado']}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border: 1px solid #eee; font-weight: bold;">Accesorios:</td>
                    <td style="padding: 8px; border: 1px solid #eee;">
                        {$data['tiene_accesorios']}: {$data['detalle_accesorios']}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px; border: 1px solid #eee; font-weight: bold;">Embalado:</td>
                    <td style="padding: 8px; border: 1px solid #eee;">{$data['embalado']}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border: 1px solid #eee; font-weight: bold;">Mantención:</td>
                    <td style="padding: 8px; border: 1px solid #eee;">{$data['mantencion']}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border: 1px solid #eee; font-weight: bold;">Retiro Masivo:</td>
                    <td style="padding: 8px; border: 1px solid #eee;">
                        {$data['retiro_masivo']} | 
                        Desconectados: {$data['desconectados']} | 
                        Apilados: {$data['apilados']}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px; border: 1px solid #eee; font-weight: bold;">Destino:</td>
                    <td style="padding: 8px; border: 1px solid #eee;">{$destinoFinal}</td>
                </tr>
            </table>
            
            <h3 style="color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top: 20px;">
                Requisitos de Acceso
            </h3>
            
            <table style="width: 100%; border-collapse: collapse; margin: 15px 0;">
                <tr>
                    <td style="padding: 8px; border: 1px solid #eee; width: 40%; font-weight: bold;">EPP Requerido:</td>
                    <td style="padding: 8px; border: 1px solid #eee;">
                        {$data['requiere_epp']}: {$data['detalle_epp']}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px; border: 1px solid #eee; font-weight: bold;">Tipo Transporte:</td>
                    <td style="padding: 8px; border: 1px solid #eee;">{$data['tipo_transporte']}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border: 1px solid #eee; font-weight: bold;">Requisitos Seguridad:</td>
                    <td style="padding: 8px; border: 1px solid #eee;">{$data['requisitos_seguridad']}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border: 1px solid #eee; font-weight: bold;">Estacionamiento:</td>
                    <td style="padding: 8px; border: 1px solid #eee;">
                        {$data['estacionamiento']}
                        <!-- CORRECCIÓN: No se puede usar PHP dentro de una cadena heredoc. Prepara la variable antes -->
                        {$alturaMaximaTexto}
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px; border: 1px solid #eee; font-weight: bold;">Ascensor/Montacarga:</td>
                    <td style="padding: 8px; border: 1px solid #eee;">{$data['ascensor']}</td>
                </tr>
                <tr>
                    <td style="padding: 8px; border: 1px solid #eee; font-weight: bold;">Distancia Maniobra:</td>
                    <td style="padding: 8px; border: 1px solid #eee;">{$data['distancia_maniobra']} metros</td>
                </tr>
            </table>
            
            <h3 style="color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top: 20px;">
                Información Adicional
            </h3>
            
            <p style="background-color: #f9f9f9; padding: 10px; border-radius: 4px;">
                <strong>Documentación Requerida:</strong><br>
                {$data['documentacion_requerida']}
            </p>
            
            <p style="background-color: #f9f9f9; padding: 10px; border-radius: 4px;">
                <strong>Observaciones:</strong><br>
                {$data['observaciones']}
            </p>
        </div>
        
        <!-- Pie de página -->
        <div style="background-color: #f5f5f5; padding: 15px; text-align: center; font-size: 12px; color: #777;">
            <p>
                Este correo fue generado automáticamente. Por favor no responder directamente.<br>
                © ServiceOne {$data['fecha_retiro']}. Todos los derechos reservados.
            </p>
        </div>
    </div>
HTML;

    // Versión texto plano para clientes de correo alternativos
    $mail->AltBody = construirVersionTextoPlano($data);

    // ==================== ENVÍO Y RESPUESTA ====================
    $mail->send();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al procesar la solicitud',
        'debug' => $e->getMessage() // Remover en producción
    ]);
}

// Función para construir versión texto plano
function construirVersionTextoPlano($data) {
    $text = "SOLICITUD DE RETIRO DE EQUIPOS\n";
    $text .= str_repeat("=", 50) . "\n\n";
    
    $text .= "INFORMACIÓN PRINCIPAL\n";
    $text .= str_repeat("-", 50) . "\n";
    $text .= "Empresa: {$data['empresa']}\n";
    $text .= "Solicitante: {$data['nombre']}\n";
    $text .= "Contacto: {$data['correo']} | {$data['telefono']}\n";
    $text .= "Dirección: {$data['direccion']}\n";
    $text .= "Contacto Backup: " . ($data['nombre_backup'] ? "{$data['nombre_backup']} ({$data['telefono_backup']}, {$data['correo_backup']})" : "No especificado") . "\n\n";
    
    $text .= "DETALLES DEL RETIRO\n";
    $text .= str_repeat("-", 50) . "\n";
    $text .= "Tipo: {$data['tipo_retiro']}\n";
    $text .= "Cantidad: {$data['cantidad']}\n";
    $text .= "Fecha: {$data['fecha_retiro']}\n";
    $text .= "Horario: " . (($data['horario_retiro'] === 'am') ? 'Mañana (9:00 - 12:00)' : 'Tarde (13:00 - 17:00)') . "\n";
    $text .= "Modelos/Series: {$data['modelos_series']}\n";
    $text .= "Estado: {$data['estado']}\n";
    $text .= "Accesorios: {$data['tiene_accesorios']}: {$data['detalle_accesorios']}\n";
    $text .= "Embalado: {$data['embalado']}\n";
    $text .= "Mantención: {$data['mantencion']}\n";
    $text .= "Retiro Masivo: {$data['retiro_masivo']}\n";
    $text .= "  - Desconectados: {$data['desconectados']}\n";
    $text .= "  - Apilados: {$data['apilados']}\n";
    $text .= "Destino: " . (($data['destino'] === 'otro') ? $data['otro_destino'] : $data['destino']) . "\n\n";
    
    $text .= "REQUISITOS DE ACCESO\n";
    $text .= str_repeat("-", 50) . "\n";
    $text .= "EPP Requerido: {$data['requiere_epp']}: {$data['detalle_epp']}\n";
    $text .= "Tipo Transporte: {$data['tipo_transporte']}\n";
    $text .= "Requisitos Seguridad: {$data['requisitos_seguridad']}\n";
    $text .= "Estacionamiento: {$data['estacionamiento']}\n";
    $text .= ($data['altura_maxima'] ? "Altura Máxima: {$data['altura_maxima']}m\n" : "");
    $text .= "Ascensor/Montacarga: {$data['ascensor']}\n";
    $text .= "Distancia Maniobra: {$data['distancia_maniobra']} metros\n\n";
    
    $text .= "INFORMACIÓN ADICIONAL\n";
    $text .= str_repeat("-", 50) . "\n";
    $text .= "Documentación Requerida:\n{$data['documentacion_requerida']}\n\n";
    $text .= "Observaciones:\n{$data['observaciones']}\n\n";
    
    $text .= str_repeat("=", 50) . "\n";
    $text .= "Este correo fue generado automáticamente el " . date('d/m/Y H:i:s') . "\n";
    $text .= "© ServiceOne. Todos los derechos reservados.\n";
    
    return $text;
}
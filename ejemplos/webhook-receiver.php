<?php
/**
 * EJEMPLO DE RECEPTOR DE WEBHOOKS
 *
 * Este archivo muestra cómo recibir y validar webhooks desde el sistema
 * de facturación electrónica.
 *
 * INSTALACIÓN:
 * 1. Coloca este archivo en un servidor web accesible públicamente
 * 2. Asegúrate de tener SSL (HTTPS) configurado
 * 3. Configura la URL en el webhook: https://tu-dominio.com/webhook-receiver.php
 */

// ============================================
// CONFIGURACIÓN
// ============================================
define('WEBHOOK_SECRET', 'whsec_mi_secreto_para_firmar'); // El mismo secret configurado en el webhook
define('LOG_FILE', __DIR__ . '/webhook-logs.txt');

// ============================================
// OBTENER EL PAYLOAD
// ============================================
$payload = file_get_contents('php://input');
$headers = getallheaders();

// Log de la petición recibida
logWebhook("=== NUEVA PETICIÓN WEBHOOK ===");
logWebhook("Timestamp: " . date('Y-m-d H:i:s'));
logWebhook("Headers: " . json_encode($headers, JSON_PRETTY_PRINT));
logWebhook("Payload Raw: " . $payload);

// ============================================
// VALIDAR FIRMA HMAC SHA256
// ============================================
$signature = $headers['X-Webhook-Signature'] ?? '';
$event = $headers['X-Webhook-Event'] ?? '';

if (empty($signature)) {
    logWebhook("ERROR: No se recibió firma en el header X-Webhook-Signature");
    http_response_code(401);
    echo json_encode(['error' => 'Missing signature']);
    exit;
}

// Generar firma esperada
$expectedSignature = hash_hmac('sha256', $payload, WEBHOOK_SECRET);

// Comparación segura (timing-safe)
if (!hash_equals($expectedSignature, $signature)) {
    logWebhook("ERROR: Firma inválida");
    logWebhook("Firma recibida: " . $signature);
    logWebhook("Firma esperada: " . $expectedSignature);
    http_response_code(401);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

logWebhook("✓ Firma validada correctamente");

// ============================================
// PROCESAR EL WEBHOOK
// ============================================
$data = json_decode($payload, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    logWebhook("ERROR: Payload no es JSON válido");
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

logWebhook("Evento: " . $event);
logWebhook("Datos: " . json_encode($data, JSON_PRETTY_PRINT));

// ============================================
// LÓGICA DE NEGOCIO SEGÚN EL EVENTO
// ============================================
switch ($event) {
    case 'invoice.accepted':
        processInvoiceAccepted($data['data']);
        break;

    case 'invoice.rejected':
        processInvoiceRejected($data['data']);
        break;

    case 'boleta.accepted':
        processBoletaAccepted($data['data']);
        break;

    case 'credit_note.accepted':
        processCreditNoteAccepted($data['data']);
        break;

    case 'webhook.test':
        logWebhook("Webhook de prueba recibido correctamente");
        break;

    default:
        logWebhook("ADVERTENCIA: Evento no manejado: " . $event);
}

// ============================================
// RESPONDER CON ÉXITO
// ============================================
http_response_code(200);
echo json_encode([
    'success' => true,
    'message' => 'Webhook procesado correctamente',
    'event' => $event,
    'received_at' => date('c')
]);

logWebhook("✓ Webhook procesado exitosamente\n");

// ============================================
// FUNCIONES DE PROCESAMIENTO
// ============================================

function processInvoiceAccepted($data) {
    logWebhook("📄 PROCESANDO FACTURA ACEPTADA");
    logWebhook("  - Número: " . $data['numero']);
    logWebhook("  - Monto: " . $data['moneda'] . ' ' . $data['monto']);
    logWebhook("  - Cliente: " . $data['client']['razon_social']);
    logWebhook("  - Estado SUNAT: " . $data['estado_sunat']);

    // AQUÍ VA TU LÓGICA DE NEGOCIO
    // Por ejemplo:
    // - Actualizar estado en tu ERP
    // - Enviar email al cliente
    // - Generar asiento contable
    // - Marcar como pagado en sistema de ventas

    // Ejemplo: guardar en base de datos
    /*
    $pdo = new PDO('mysql:host=localhost;dbname=mi_db', 'user', 'pass');
    $stmt = $pdo->prepare("UPDATE ventas SET estado_sunat = :estado,
                                             numero_comprobante = :numero
                          WHERE id = :id");
    $stmt->execute([
        'estado' => $data['estado_sunat'],
        'numero' => $data['numero'],
        'id' => $data['document_id']
    ]);
    */
}

function processInvoiceRejected($data) {
    logWebhook("❌ PROCESANDO FACTURA RECHAZADA");
    logWebhook("  - Número: " . $data['numero']);
    logWebhook("  - Cliente: " . $data['client']['razon_social']);
    logWebhook("  - Resultado: " . json_encode($data['result']));

    // AQUÍ VA TU LÓGICA DE NEGOCIO
    // Por ejemplo:
    // - Notificar al vendedor
    // - Revertir la venta
    // - Registrar el error
}

function processBoletaAccepted($data) {
    logWebhook("🧾 PROCESANDO BOLETA ACEPTADA");
    logWebhook("  - Número: " . $data['numero']);
    logWebhook("  - Monto: " . $data['moneda'] . ' ' . $data['monto']);
}

function processCreditNoteAccepted($data) {
    logWebhook("📝 PROCESANDO NOTA DE CRÉDITO ACEPTADA");
    logWebhook("  - Número: " . $data['numero']);
    logWebhook("  - Monto: " . $data['moneda'] . ' ' . $data['monto']);
}

// ============================================
// FUNCIÓN DE LOGGING
// ============================================
function logWebhook($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}\n";

    // Escribir en archivo
    file_put_contents(LOG_FILE, $logMessage, FILE_APPEND);

    // También imprimir en consola para debugging
    error_log($logMessage);
}

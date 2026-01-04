<?php
/**
 * SCRIPT PARA CREAR UN WEBHOOK DE PRUEBA
 *
 * Este script crea automáticamente un webhook configurado con webhook.site
 * para que puedas empezar a probar inmediatamente.
 *
 * USO:
 * php crear-webhook-prueba.php
 *
 * O con opciones personalizadas:
 * php crear-webhook-prueba.php --company=1 --url=https://mi-url.com/webhook
 */

// ============================================
// CONFIGURACIÓN
// ============================================
$config = [
    'base_url' => 'http://localhost:8000/api/v1',
    'token' => '', // Pon tu token aquí o pásalo como argumento
    'company_id' => 1,
    'webhook_url' => '', // Se generará automáticamente con webhook.site si está vacío
];

// ============================================
// PROCESAR ARGUMENTOS DE LÍNEA DE COMANDOS
// ============================================
$options = getopt('', [
    'company:',
    'url:',
    'token:',
    'base-url:',
    'help'
]);

if (isset($options['help'])) {
    showHelp();
    exit(0);
}

if (isset($options['company'])) {
    $config['company_id'] = (int) $options['company'];
}

if (isset($options['url'])) {
    $config['webhook_url'] = $options['url'];
}

if (isset($options['token'])) {
    $config['token'] = $options['token'];
}

if (isset($options['base-url'])) {
    $config['base_url'] = rtrim($options['base-url'], '/') . '/api/v1';
}

// ============================================
// VALIDACIONES
// ============================================
if (empty($config['token'])) {
    echo "❌ ERROR: Se requiere un token de autenticación\n";
    echo "Opciones:\n";
    echo "  1. Edita este archivo y agrega tu token en la configuración\n";
    echo "  2. Pásalo como argumento: --token=tu_token_aqui\n\n";
    exit(1);
}

// ============================================
// GENERAR URL DE WEBHOOK.SITE SI NO SE PROPORCIONÓ
// ============================================
if (empty($config['webhook_url'])) {
    echo "🌐 Generando URL de prueba con webhook.site...\n";
    $webhookSiteUrl = generateWebhookSiteUrl();

    if ($webhookSiteUrl) {
        $config['webhook_url'] = $webhookSiteUrl;
        echo "✓ URL generada: {$webhookSiteUrl}\n";
        echo "🔗 Abre en tu navegador: {$webhookSiteUrl}\n\n";
    } else {
        echo "⚠️  No se pudo generar URL automáticamente\n";
        echo "Por favor, ve a https://webhook.site y copia tu URL única\n";
        echo "Luego ejecuta: php crear-webhook-prueba.php --url=TU_URL_AQUI\n\n";
        exit(1);
    }
}

// ============================================
// PREPARAR DATOS DEL WEBHOOK
// ============================================
$webhookData = [
    'company_id' => $config['company_id'],
    'name' => 'Webhook de Prueba - ' . date('Y-m-d H:i:s'),
    'url' => $config['webhook_url'],
    'method' => 'POST',
    'events' => [
        'invoice.accepted',
        'invoice.rejected',
        'boleta.accepted',
        'credit_note.accepted',
        'debit_note.accepted',
    ],
    'headers' => [
        'X-Test' => 'true',
        'X-Created-By' => 'crear-webhook-prueba.php',
    ],
    'timeout' => 30,
    'max_retries' => 3,
    'retry_delay' => 60,
    'active' => true,
];

echo "📋 DATOS DEL WEBHOOK:\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Empresa ID: {$webhookData['company_id']}\n";
echo "Nombre: {$webhookData['name']}\n";
echo "URL: {$webhookData['url']}\n";
echo "Eventos: " . implode(', ', $webhookData['events']) . "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// ============================================
// CREAR EL WEBHOOK
// ============================================
echo "🚀 Creando webhook...\n";

$response = makeRequest(
    'POST',
    $config['base_url'] . '/webhooks',
    $config['token'],
    $webhookData
);

if ($response['success']) {
    $webhook = $response['data'];

    echo "\n✅ ¡WEBHOOK CREADO EXITOSAMENTE!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "ID: {$webhook['id']}\n";
    echo "Nombre: {$webhook['name']}\n";
    echo "URL: {$webhook['url']}\n";
    echo "Secret: {$webhook['secret']}\n";
    echo "Estado: " . ($webhook['active'] ? '✓ ACTIVO' : '✗ INACTIVO') . "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

    // ============================================
    // ENVIAR WEBHOOK DE PRUEBA
    // ============================================
    echo "🧪 Enviando webhook de prueba...\n";

    $testResponse = makeRequest(
        'POST',
        $config['base_url'] . '/webhooks/' . $webhook['id'] . '/test',
        $config['token']
    );

    if ($testResponse['success']) {
        echo "✓ Webhook de prueba enviado exitosamente\n";

        if (isset($testResponse['data']['test_result'])) {
            $result = $testResponse['data']['test_result'];
            echo "  - Status Code: {$result['status_code']}\n";
            echo "  - Response Time: " . number_format($result['response_time'], 3) . "s\n";
        }

        echo "\n📱 PRÓXIMOS PASOS:\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "1. Abre webhook.site en tu navegador:\n";
        echo "   {$webhook['url']}\n\n";
        echo "2. Deberías ver la petición del webhook de prueba\n\n";
        echo "3. Envía una factura real a SUNAT para disparar el evento 'invoice.accepted'\n\n";
        echo "4. Ver estadísticas:\n";
        echo "   GET {$config['base_url']}/webhooks/{$webhook['id']}/statistics\n\n";
        echo "5. Ver historial de entregas:\n";
        echo "   GET {$config['base_url']}/webhooks/{$webhook['id']}/deliveries\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        // Guardar información en archivo
        saveWebhookInfo($webhook, $config['webhook_url']);

    } else {
        echo "⚠️  Advertencia: No se pudo enviar el webhook de prueba\n";
        echo "Error: {$testResponse['error']}\n\n";
    }

} else {
    echo "\n❌ ERROR AL CREAR WEBHOOK\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Mensaje: {$response['error']}\n";

    if (isset($response['details'])) {
        echo "\nDetalles:\n";
        print_r($response['details']);
    }

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    exit(1);
}

// ============================================
// FUNCIONES AUXILIARES
// ============================================

function makeRequest($method, $url, $token, $data = null) {
    $ch = curl_init($url);

    $headers = [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
        'Accept: application/json',
    ];

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    curl_close($ch);

    if ($error) {
        return [
            'success' => false,
            'error' => $error,
        ];
    }

    $responseData = json_decode($response, true);

    if ($statusCode >= 200 && $statusCode < 300) {
        return [
            'success' => true,
            'data' => $responseData,
        ];
    } else {
        return [
            'success' => false,
            'error' => $responseData['message'] ?? 'Error desconocido',
            'details' => $responseData,
        ];
    }
}

function generateWebhookSiteUrl() {
    // Intentar crear una nueva URL en webhook.site
    $ch = curl_init('https://webhook.site/token');

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'default_status' => 200,
        'default_content' => '{"success":true}',
        'default_content_type' => 'application/json',
    ]));

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return null;
    }

    $data = json_decode($response, true);

    if (isset($data['uuid'])) {
        return 'https://webhook.site/' . $data['uuid'];
    }

    return null;
}

function saveWebhookInfo($webhook, $url) {
    $filename = __DIR__ . '/webhook-info-' . $webhook['id'] . '.txt';

    $content = "INFORMACIÓN DEL WEBHOOK\n";
    $content .= "═══════════════════════\n\n";
    $content .= "ID: {$webhook['id']}\n";
    $content .= "Nombre: {$webhook['name']}\n";
    $content .= "URL: {$webhook['url']}\n";
    $content .= "Secret: {$webhook['secret']}\n";
    $content .= "Creado: " . date('Y-m-d H:i:s') . "\n\n";
    $content .= "VER PETICIONES:\n";
    $content .= "{$url}\n\n";
    $content .= "VALIDAR FIRMA HMAC:\n";
    $content .= "php -r \"echo hash_hmac('sha256', file_get_contents('payload.json'), '{$webhook['secret']}');\"";

    file_put_contents($filename, $content);

    echo "💾 Información guardada en: {$filename}\n\n";
}

function showHelp() {
    echo <<<HELP

🔧 SCRIPT PARA CREAR WEBHOOKS DE PRUEBA
═════════════════════════════════════════

USO:
  php crear-webhook-prueba.php [opciones]

OPCIONES:
  --company=ID          ID de la empresa (default: 1)
  --url=URL            URL del webhook (default: auto-genera con webhook.site)
  --token=TOKEN        Token de autenticación (REQUERIDO)
  --base-url=URL       URL base de la API (default: http://localhost:8000/api/v1)
  --help               Mostrar esta ayuda

EJEMPLOS:

  1. Crear webhook con webhook.site (automático):
     php crear-webhook-prueba.php --token=abc123

  2. Crear webhook con URL personalizada:
     php crear-webhook-prueba.php --token=abc123 --url=https://mi-servidor.com/webhook

  3. Crear webhook para empresa específica:
     php crear-webhook-prueba.php --token=abc123 --company=5

  4. Configurar API en otro servidor:
     php crear-webhook-prueba.php --token=abc123 --base-url=https://api.produccion.com

HELP;
}

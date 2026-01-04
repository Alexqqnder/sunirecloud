/**
 * EJEMPLO DE RECEPTOR DE WEBHOOKS EN NODE.JS/EXPRESS
 *
 * INSTALACIÓN:
 * npm install express body-parser crypto
 *
 * EJECUTAR:
 * node webhook-receiver-nodejs.js
 *
 * TESTING LOCAL CON NGROK:
 * 1. Instalar ngrok: https://ngrok.com/download
 * 2. Ejecutar: ngrok http 3000
 * 3. Copiar la URL HTTPS que te da ngrok
 * 4. Usar esa URL en la configuración del webhook
 */

const express = require('express');
const bodyParser = require('body-parser');
const crypto = require('crypto');
const fs = require('fs');

// ============================================
// CONFIGURACIÓN
// ============================================
const PORT = 3000;
const WEBHOOK_SECRET = 'whsec_mi_secreto_para_firmar'; // El mismo configurado en el webhook
const LOG_FILE = './webhook-logs.txt';

const app = express();

// Middleware para obtener el raw body (necesario para validar firma)
app.use(bodyParser.json({
    verify: (req, res, buf) => {
        req.rawBody = buf.toString('utf8');
    }
}));

// ============================================
// ENDPOINT RECEPTOR DE WEBHOOKS
// ============================================
app.post('/webhook', (req, res) => {
    console.log('\n=== NUEVA PETICIÓN WEBHOOK ===');
    console.log('Timestamp:', new Date().toISOString());

    // Obtener headers
    const signature = req.headers['x-webhook-signature'];
    const event = req.headers['x-webhook-event'];

    console.log('Evento:', event);
    console.log('Firma recibida:', signature);

    // ============================================
    // VALIDAR FIRMA HMAC SHA256
    // ============================================
    if (!signature) {
        console.error('❌ ERROR: No se recibió firma');
        return res.status(401).json({ error: 'Missing signature' });
    }

    // Generar firma esperada usando el raw body
    const expectedSignature = crypto
        .createHmac('sha256', WEBHOOK_SECRET)
        .update(req.rawBody)
        .digest('hex');

    console.log('Firma esperada:', expectedSignature);

    // Comparación segura (timing-safe)
    if (!crypto.timingSafeEqual(Buffer.from(signature), Buffer.from(expectedSignature))) {
        console.error('❌ ERROR: Firma inválida');
        logToFile(`ERROR: Firma inválida - Evento: ${event}`);
        return res.status(401).json({ error: 'Invalid signature' });
    }

    console.log('✓ Firma validada correctamente');

    // ============================================
    // PROCESAR EL WEBHOOK
    // ============================================
    const data = req.body;
    console.log('Datos recibidos:', JSON.stringify(data, null, 2));

    // Log a archivo
    logToFile(`=== ${event} ===`);
    logToFile(JSON.stringify(data, null, 2));

    // ============================================
    // LÓGICA DE NEGOCIO SEGÚN EL EVENTO
    // ============================================
    try {
        switch (event) {
            case 'invoice.accepted':
                processInvoiceAccepted(data.data);
                break;

            case 'invoice.rejected':
                processInvoiceRejected(data.data);
                break;

            case 'boleta.accepted':
                processBoletaAccepted(data.data);
                break;

            case 'credit_note.accepted':
                processCreditNoteAccepted(data.data);
                break;

            case 'webhook.test':
                console.log('🧪 Webhook de prueba recibido correctamente');
                break;

            default:
                console.warn('⚠️ Evento no manejado:', event);
        }

        // ============================================
        // RESPONDER CON ÉXITO
        // ============================================
        res.status(200).json({
            success: true,
            message: 'Webhook procesado correctamente',
            event: event,
            received_at: new Date().toISOString()
        });

        console.log('✓ Webhook procesado exitosamente\n');
        logToFile('✓ Procesado exitosamente\n');

    } catch (error) {
        console.error('❌ Error al procesar webhook:', error);
        logToFile(`ERROR: ${error.message}`);
        res.status(500).json({ error: 'Internal server error' });
    }
});

// ============================================
// ENDPOINT DE SALUD (HEALTH CHECK)
// ============================================
app.get('/health', (req, res) => {
    res.json({
        status: 'ok',
        timestamp: new Date().toISOString(),
        uptime: process.uptime()
    });
});

// ============================================
// FUNCIONES DE PROCESAMIENTO
// ============================================
function processInvoiceAccepted(data) {
    console.log('📄 PROCESANDO FACTURA ACEPTADA');
    console.log('  - Número:', data.numero);
    console.log('  - Monto:', data.moneda, data.monto);
    console.log('  - Cliente:', data.client.razon_social);
    console.log('  - Estado SUNAT:', data.estado_sunat);

    // AQUÍ VA TU LÓGICA DE NEGOCIO
    // Por ejemplo:
    // - Actualizar base de datos
    // - Enviar email al cliente
    // - Notificar a Slack/Discord
    // - Sincronizar con ERP

    // Ejemplo con base de datos (requiere mysql2 o pg)
    /*
    const mysql = require('mysql2/promise');
    const connection = await mysql.createConnection({
        host: 'localhost',
        user: 'user',
        password: 'password',
        database: 'mi_db'
    });

    await connection.execute(
        'UPDATE ventas SET estado_sunat = ?, numero_comprobante = ? WHERE id = ?',
        [data.estado_sunat, data.numero, data.document_id]
    );
    */

    // Ejemplo con Slack
    /*
    const axios = require('axios');
    await axios.post('https://hooks.slack.com/services/YOUR/WEBHOOK/URL', {
        text: `✅ Factura ${data.numero} aceptada por SUNAT - ${data.moneda} ${data.monto}`
    });
    */
}

function processInvoiceRejected(data) {
    console.log('❌ PROCESANDO FACTURA RECHAZADA');
    console.log('  - Número:', data.numero);
    console.log('  - Cliente:', data.client.razon_social);
    console.log('  - Resultado:', data.result);

    // AQUÍ VA TU LÓGICA DE NEGOCIO
    // - Notificar al vendedor
    // - Revertir estado en el sistema
    // - Registrar error para análisis
}

function processBoletaAccepted(data) {
    console.log('🧾 PROCESANDO BOLETA ACEPTADA');
    console.log('  - Número:', data.numero);
    console.log('  - Monto:', data.moneda, data.monto);
}

function processCreditNoteAccepted(data) {
    console.log('📝 PROCESANDO NOTA DE CRÉDITO ACEPTADA');
    console.log('  - Número:', data.numero);
    console.log('  - Monto:', data.moneda, data.monto);
}

// ============================================
// FUNCIÓN DE LOGGING
// ============================================
function logToFile(message) {
    const timestamp = new Date().toISOString();
    const logMessage = `[${timestamp}] ${message}\n`;
    fs.appendFileSync(LOG_FILE, logMessage);
}

// ============================================
// INICIAR SERVIDOR
// ============================================
app.listen(PORT, () => {
    console.log('🚀 Servidor de webhooks iniciado');
    console.log(`📡 Escuchando en http://localhost:${PORT}/webhook`);
    console.log(`🔐 Secret configurado: ${WEBHOOK_SECRET}`);
    console.log('\n💡 Para testing local con NGROK:');
    console.log('   1. Ejecuta: ngrok http 3000');
    console.log('   2. Copia la URL HTTPS que te da ngrok');
    console.log('   3. Usa esa URL + /webhook en la configuración\n');
});

// Manejo de cierre graceful
process.on('SIGTERM', () => {
    console.log('👋 Cerrando servidor...');
    process.exit(0);
});

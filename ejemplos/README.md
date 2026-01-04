# 📚 EJEMPLOS DE WEBHOOKS

Esta carpeta contiene ejemplos completos y guías paso a paso para implementar y probar webhooks en el sistema de facturación electrónica SUNAT.

---

## 📂 Archivos Disponibles

### 🚀 Para Empezar (Testing Rápido)

| Archivo | Descripción | Tiempo |
|---------|-------------|--------|
| **[TESTING-RAPIDO-WEBHOOK-SITE.md](./TESTING-RAPIDO-WEBHOOK-SITE.md)** | Guía para probar webhooks en **5 minutos** sin programar nada | 5 min |
| **[crear-webhook-prueba.php](./crear-webhook-prueba.php)** | Script automático para crear un webhook de prueba | 2 min |

### 💻 Implementaciones de Servidor Receptor

| Archivo | Lenguaje | Descripción |
|---------|----------|-------------|
| **[webhook-receiver.php](./webhook-receiver.php)** | PHP | Receptor completo con validación HMAC y logging |
| **[webhook-receiver-nodejs.js](./webhook-receiver-nodejs.js)** | Node.js | Receptor con Express y validación de firma |

### 📖 Documentación y Referencia

| Archivo | Descripción |
|---------|-------------|
| **[WEBHOOKS-EJEMPLOS-POSTMAN.md](./WEBHOOKS-EJEMPLOS-POSTMAN.md)** | Colección completa de endpoints con ejemplos cURL y Postman |

---

## 🎯 ¿Por Dónde Empezar?

### Si quieres probar AHORA (5 minutos):
```
1. Lee: TESTING-RAPIDO-WEBHOOK-SITE.md
2. Ejecuta: php crear-webhook-prueba.php --token=TU_TOKEN
3. Abre webhook.site en tu navegador
4. ¡Listo! Verás las notificaciones en tiempo real
```

### Si quieres implementar tu propio receptor:

**Para PHP:**
```bash
# 1. Copia el archivo a tu servidor
cp webhook-receiver.php /ruta/a/tu/servidor/

# 2. Edita el secret en el archivo
nano webhook-receiver.php
# Cambia: define('WEBHOOK_SECRET', 'tu_secret_aqui');

# 3. Crea el webhook apuntando a tu servidor
# URL: https://tu-dominio.com/webhook-receiver.php
```

**Para Node.js:**
```bash
# 1. Instalar dependencias
npm install express body-parser crypto

# 2. Editar configuración
nano webhook-receiver-nodejs.js
# Cambia: const WEBHOOK_SECRET = 'tu_secret_aqui';

# 3. Ejecutar servidor
node webhook-receiver-nodejs.js

# 4. Exponer con ngrok (para testing local)
ngrok http 3000
```

---

## 📊 Eventos Disponibles

Tu webhook puede suscribirse a estos eventos:

| Evento | Cuándo se Dispara |
|--------|-------------------|
| `invoice.created` | Al crear una factura |
| `invoice.accepted` | Cuando SUNAT acepta la factura |
| `invoice.rejected` | Cuando SUNAT rechaza la factura |
| `invoice.voided` | Al anular una factura |
| `boleta.created` | Al crear una boleta |
| `boleta.accepted` | Cuando SUNAT acepta el resumen diario |
| `boleta.rejected` | Cuando SUNAT rechaza el resumen diario |
| `credit_note.created` | Al crear una nota de crédito |
| `credit_note.accepted` | Cuando SUNAT acepta la NC |
| `debit_note.created` | Al crear una nota de débito |
| `debit_note.accepted` | Cuando SUNAT acepta la ND |

---

## 🔐 Seguridad - Validación de Firma HMAC

**Todos los webhooks incluyen una firma HMAC SHA256 en el header `X-Webhook-Signature`.**

### Validar en PHP:
```php
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'];
$secret = 'tu_secret_configurado';

$expected = hash_hmac('sha256', $payload, $secret);

if (hash_equals($expected, $signature)) {
    // ✅ Firma válida - webhook auténtico
} else {
    // ❌ Firma inválida - posible ataque
    http_response_code(401);
}
```

### Validar en Node.js:
```javascript
const crypto = require('crypto');

const signature = req.headers['x-webhook-signature'];
const secret = 'tu_secret_configurado';

const expected = crypto
    .createHmac('sha256', secret)
    .update(req.rawBody)
    .digest('hex');

if (crypto.timingSafeEqual(Buffer.from(signature), Buffer.from(expected))) {
    // ✅ Firma válida
} else {
    // ❌ Firma inválida
    res.status(401).send('Invalid signature');
}
```

---

## 📡 Estructura del Payload

Todos los webhooks envían este formato:

```json
{
  "event": "invoice.accepted",
  "timestamp": "2025-12-23T10:30:00.000Z",
  "data": {
    "document_id": 123,
    "document_type": "invoice",
    "numero": "F001-00000123",
    "company_id": 1,
    "client": {
      "tipo_documento": "6",
      "numero_documento": "20123456789",
      "razon_social": "EMPRESA EJEMPLO SAC"
    },
    "monto": 1500.00,
    "moneda": "PEN",
    "fecha_emision": "2025-12-23T10:00:00.000Z",
    "estado_sunat": "ACEPTADO",
    "result": {
      "success": true,
      "sunat_response": { ... }
    }
  }
}
```

---

## 🧪 Testing Local con ngrok

Para probar webhooks en tu máquina local:

```bash
# 1. Instalar ngrok
# Descargar de: https://ngrok.com/download

# 2. Ejecutar tu servidor local
php -S localhost:8080 webhook-receiver.php

# 3. Exponer con ngrok
ngrok http 8080

# 4. Copiar la URL HTTPS
# Ejemplo: https://abc123.ngrok.io

# 5. Crear webhook con esa URL
POST /api/v1/webhooks
{
  "url": "https://abc123.ngrok.io/webhook-receiver.php",
  ...
}
```

---

## 🔄 Sistema de Reintentos

El sistema reintenta automáticamente las entregas fallidas:

| Intento | Delay | Estado |
|---------|-------|--------|
| 1 | 0s (inmediato) | pending |
| 2 | +60s | pending |
| 3 | +120s | pending |
| 4 | +180s | **FAILED** |

**Configuración:**
```json
{
  "max_retries": 3,
  "retry_delay": 60
}
```

**Cron Job (Para procesamiento automático):**
```bash
*/5 * * * * cd /path/to/project && php artisan webhooks:process
```

---

## 📝 Endpoints Principales

### Gestión de Webhooks

```http
# Crear webhook
POST /api/v1/webhooks

# Listar webhooks
GET /api/v1/webhooks?company_id=1

# Ver detalle
GET /api/v1/webhooks/{id}

# Actualizar
PUT /api/v1/webhooks/{id}

# Eliminar
DELETE /api/v1/webhooks/{id}

# Probar webhook
POST /api/v1/webhooks/{id}/test
```

### Monitoreo

```http
# Ver estadísticas
GET /api/v1/webhooks/{id}/statistics

# Ver historial de entregas
GET /api/v1/webhooks/{id}/deliveries

# Reintentar entrega fallida
POST /api/v1/webhooks/deliveries/{deliveryId}/retry
```

---

## 💡 Casos de Uso Comunes

### 1. Notificación por Email
```php
function processInvoiceAccepted($data) {
    mail(
        'ventas@miempresa.com',
        'Factura Aceptada por SUNAT',
        "La factura {$data['numero']} fue aceptada"
    );
}
```

### 2. Actualizar ERP/CRM
```php
function processInvoiceAccepted($data) {
    $pdo = new PDO('mysql:host=localhost;dbname=erp', 'user', 'pass');
    $stmt = $pdo->prepare("UPDATE ventas SET estado_sunat = ? WHERE id = ?");
    $stmt->execute(['ACEPTADO', $data['document_id']]);
}
```

### 3. Notificación a Slack
```javascript
function processInvoiceAccepted(data) {
    axios.post('https://hooks.slack.com/services/YOUR/WEBHOOK', {
        text: `✅ Factura ${data.numero} aceptada - ${data.moneda} ${data.monto}`
    });
}
```

### 4. Sincronización con Servicio Externo
```javascript
function processInvoiceAccepted(data) {
    axios.post('https://api.miservicio.com/facturas', {
        numero: data.numero,
        cliente_ruc: data.client.numero_documento,
        total: data.monto,
        estado: 'aceptado'
    });
}
```

---

## 🛠️ Debugging

### Ver Logs del Sistema
```bash
# Logs de webhooks
tail -f storage/logs/audit.log

# Logs de errores críticos
tail -f storage/logs/critical.log

# Filtrar solo webhooks
grep "webhook" storage/logs/audit.log
```

### Verificar Estado de un Webhook
```http
GET /api/v1/webhooks/{id}/statistics

# Respuesta:
{
  "total_deliveries": 150,
  "successful": 148,
  "failed": 2,
  "success_rate": 98.67
}
```

### Revisar Entregas Fallidas
```http
GET /api/v1/webhooks/{id}/deliveries?status=failed

# Ver detalles del error
{
  "error_message": "HTTP 500: Internal Server Error",
  "response_code": 500,
  "attempts": 3
}
```

---

## ❓ FAQ

**P: ¿Los webhooks se envían en tiempo real?**
R: Sí, se disparan inmediatamente cuando ocurre el evento.

**P: ¿Qué pasa si mi servidor está caído?**
R: El sistema reintentará automáticamente según la configuración de `max_retries` y `retry_delay`.

**P: ¿Puedo tener múltiples webhooks para la misma empresa?**
R: Sí, puedes crear tantos webhooks como necesites.

**P: ¿Cómo sé si un webhook falló?**
R: Revisa las estadísticas (`/statistics`) o el historial de entregas (`/deliveries`).

**P: ¿Puedo reintentar manualmente una entrega fallida?**
R: Sí, usa el endpoint `POST /webhooks/deliveries/{id}/retry`.

**P: ¿Es obligatorio validar la firma HMAC?**
R: Altamente recomendado para seguridad, pero no es obligatorio técnicamente.

---

## 📞 Soporte

Si encuentras problemas:

1. Revisa los logs en `storage/logs/`
2. Verifica las estadísticas del webhook
3. Prueba con webhook.site para descartar problemas en tu servidor
4. Revisa que la firma HMAC se esté validando correctamente

---

## 🎓 Recursos Adicionales

- **Documentación Completa**: `../documentacion/webhooks.md`
- **Código Fuente**:
  - Servicio: `../app/Services/WebhookService.php`
  - Controlador: `../app/Http/Controllers/Api/WebhookController.php`
  - Modelo: `../app/Models/Webhook.php`

---

**¡Listo para empezar! Comienza con [TESTING-RAPIDO-WEBHOOK-SITE.md](./TESTING-RAPIDO-WEBHOOK-SITE.md) 🚀**

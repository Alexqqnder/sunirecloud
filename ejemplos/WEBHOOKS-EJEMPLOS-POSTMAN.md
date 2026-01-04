# EJEMPLOS DE WEBHOOKS - POSTMAN/CURL

## 📋 Índice
1. [Crear Webhook](#1-crear-webhook)
2. [Listar Webhooks](#2-listar-webhooks)
3. [Ver Detalle de Webhook](#3-ver-detalle-de-webhook)
4. [Actualizar Webhook](#4-actualizar-webhook)
5. [Probar Webhook (Test)](#5-probar-webhook-test)
6. [Ver Estadísticas](#6-ver-estadísticas)
7. [Ver Historial de Entregas](#7-ver-historial-de-entregas)
8. [Reintentar Entrega Fallida](#8-reintentar-entrega-fallida)
9. [Eliminar Webhook](#9-eliminar-webhook)

---

## Configuración Inicial

**Variables de entorno (Postman):**
```
base_url = http://localhost:8000
token = tu_bearer_token_aqui
company_id = 1
webhook_id = 1
delivery_id = 1
```

**Headers comunes para todas las peticiones:**
```
Authorization: Bearer {{token}}
Content-Type: application/json
Accept: application/json
```

---

## 1. CREAR WEBHOOK

### Ejemplo Básico

```http
POST {{base_url}}/api/v1/webhooks
Content-Type: application/json
Authorization: Bearer {{token}}

{
  "company_id": {{company_id}},
  "name": "Notificaciones Facturas",
  "url": "https://mi-sistema.com/api/webhook",
  "method": "POST",
  "events": [
    "invoice.accepted",
    "invoice.rejected"
  ]
}
```

### Ejemplo Completo con Todas las Opciones

```http
POST {{base_url}}/api/v1/webhooks
Content-Type: application/json
Authorization: Bearer {{token}}

{
  "company_id": {{company_id}},
  "name": "Webhook ERP Principal",
  "url": "https://erp.miempresa.com/api/v1/webhooks/receiver",
  "method": "POST",
  "events": [
    "invoice.created",
    "invoice.accepted",
    "invoice.rejected",
    "invoice.voided",
    "boleta.accepted",
    "boleta.rejected",
    "credit_note.accepted",
    "debit_note.accepted"
  ],
  "headers": {
    "X-API-Key": "mi-api-key-secreta-123",
    "X-System-ID": "ERP-PROD-001",
    "X-Environment": "production"
  },
  "secret": "whsec_mi_secreto_personalizado_12345",
  "timeout": 30,
  "max_retries": 5,
  "retry_delay": 120,
  "active": true
}
```

### cURL

```bash
curl -X POST "{{base_url}}/api/v1/webhooks" \
  -H "Authorization: Bearer {{token}}" \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": 1,
    "name": "Notificaciones Facturas",
    "url": "https://mi-sistema.com/api/webhook",
    "method": "POST",
    "events": ["invoice.accepted", "invoice.rejected"]
  }'
```

### Respuesta Exitosa (201 Created)

```json
{
  "id": 1,
  "company_id": 1,
  "name": "Notificaciones Facturas",
  "url": "https://mi-sistema.com/api/webhook",
  "method": "POST",
  "events": ["invoice.accepted", "invoice.rejected"],
  "headers": {},
  "secret": "whsec_auto_generated_secret_40_chars",
  "active": true,
  "timeout": 30,
  "max_retries": 3,
  "retry_delay": 60,
  "success_count": 0,
  "failure_count": 0,
  "last_triggered_at": null,
  "last_status": null,
  "created_at": "2025-12-23T10:00:00.000Z",
  "updated_at": "2025-12-23T10:00:00.000Z"
}
```

---

## 2. LISTAR WEBHOOKS

```http
GET {{base_url}}/api/v1/webhooks?company_id={{company_id}}
Authorization: Bearer {{token}}
```

### cURL

```bash
curl -X GET "{{base_url}}/api/v1/webhooks?company_id=1" \
  -H "Authorization: Bearer {{token}}"
```

### Respuesta

```json
{
  "data": [
    {
      "id": 1,
      "name": "Notificaciones Facturas",
      "url": "https://mi-sistema.com/api/webhook",
      "events": ["invoice.accepted", "invoice.rejected"],
      "active": true,
      "success_count": 150,
      "failure_count": 3,
      "last_triggered_at": "2025-12-23T09:45:00.000Z",
      "last_status": "success"
    },
    {
      "id": 2,
      "name": "Webhook Slack",
      "url": "https://hooks.slack.com/services/XXX/YYY/ZZZ",
      "events": ["invoice.rejected"],
      "active": true,
      "success_count": 5,
      "failure_count": 0,
      "last_triggered_at": "2025-12-22T14:30:00.000Z",
      "last_status": "success"
    }
  ],
  "meta": {
    "total": 2,
    "per_page": 15,
    "current_page": 1
  }
}
```

---

## 3. VER DETALLE DE WEBHOOK

```http
GET {{base_url}}/api/v1/webhooks/{{webhook_id}}
Authorization: Bearer {{token}}
```

### Respuesta

```json
{
  "id": 1,
  "company_id": 1,
  "name": "Notificaciones Facturas",
  "url": "https://mi-sistema.com/api/webhook",
  "method": "POST",
  "events": ["invoice.accepted", "invoice.rejected"],
  "headers": {
    "X-API-Key": "mi-api-key-secreta-123"
  },
  "active": true,
  "timeout": 30,
  "max_retries": 3,
  "retry_delay": 60,
  "success_count": 150,
  "failure_count": 3,
  "last_triggered_at": "2025-12-23T09:45:00.000Z",
  "last_status": "success",
  "recent_deliveries": [
    {
      "id": 523,
      "event": "invoice.accepted",
      "status": "success",
      "attempts": 1,
      "response_code": 200,
      "delivered_at": "2025-12-23T09:45:00.000Z"
    },
    {
      "id": 522,
      "event": "invoice.accepted",
      "status": "success",
      "attempts": 1,
      "response_code": 200,
      "delivered_at": "2025-12-23T09:30:15.000Z"
    }
  ]
}
```

---

## 4. ACTUALIZAR WEBHOOK

```http
PUT {{base_url}}/api/v1/webhooks/{{webhook_id}}
Content-Type: application/json
Authorization: Bearer {{token}}

{
  "name": "Notificaciones Facturas ACTUALIZADO",
  "url": "https://nuevo-sistema.com/webhook",
  "events": [
    "invoice.accepted",
    "invoice.rejected",
    "boleta.accepted"
  ],
  "active": true,
  "timeout": 45,
  "max_retries": 5
}
```

### Desactivar un Webhook

```http
PUT {{base_url}}/api/v1/webhooks/{{webhook_id}}
Content-Type: application/json
Authorization: Bearer {{token}}

{
  "active": false
}
```

---

## 5. PROBAR WEBHOOK (TEST)

**Envía un webhook de prueba inmediatamente**

```http
POST {{base_url}}/api/v1/webhooks/{{webhook_id}}/test
Authorization: Bearer {{token}}
```

### Respuesta Exitosa

```json
{
  "success": true,
  "message": "Webhook de prueba enviado exitosamente",
  "test_result": {
    "status_code": 200,
    "response_body": "{\"success\":true,\"message\":\"Webhook de prueba recibido\"}",
    "response_time": 0.234
  }
}
```

### Respuesta con Error

```json
{
  "success": false,
  "message": "Error al enviar webhook de prueba",
  "test_result": {
    "status_code": 500,
    "response_body": "Internal Server Error",
    "response_time": 1.456,
    "error": "HTTP 500: Internal Server Error"
  }
}
```

---

## 6. VER ESTADÍSTICAS

```http
GET {{base_url}}/api/v1/webhooks/{{webhook_id}}/statistics
Authorization: Bearer {{token}}
```

### Respuesta

```json
{
  "webhook_id": 1,
  "total_deliveries": 1520,
  "successful": 1498,
  "failed": 22,
  "pending": 0,
  "success_rate": 98.55,
  "failure_rate": 1.45,
  "average_response_time": null,
  "last_triggered_at": "2025-12-23T09:45:00.000Z",
  "last_status": "success",
  "events_breakdown": {
    "invoice.accepted": 850,
    "invoice.rejected": 45,
    "boleta.accepted": 625
  }
}
```

---

## 7. VER HISTORIAL DE ENTREGAS

```http
GET {{base_url}}/api/v1/webhooks/{{webhook_id}}/deliveries?page=1&per_page=20
Authorization: Bearer {{token}}
```

### Con Filtros

```http
GET {{base_url}}/api/v1/webhooks/{{webhook_id}}/deliveries?status=failed&event=invoice.rejected
Authorization: Bearer {{token}}
```

### Respuesta

```json
{
  "data": [
    {
      "id": 523,
      "webhook_id": 1,
      "event": "invoice.accepted",
      "status": "success",
      "attempts": 1,
      "response_code": 200,
      "response_body": "{\"success\":true}",
      "delivered_at": "2025-12-23T09:45:00.000Z",
      "created_at": "2025-12-23T09:45:00.000Z",
      "payload": {
        "event": "invoice.accepted",
        "timestamp": "2025-12-23T09:45:00.000Z",
        "data": {
          "document_id": 123,
          "numero": "F001-00000123",
          "monto": 1500.00,
          "moneda": "PEN"
        }
      }
    },
    {
      "id": 520,
      "webhook_id": 1,
      "event": "invoice.rejected",
      "status": "failed",
      "attempts": 3,
      "response_code": 500,
      "error_message": "HTTP 500: Internal Server Error",
      "next_retry_at": null,
      "created_at": "2025-12-23T08:30:00.000Z"
    }
  ],
  "meta": {
    "total": 1520,
    "per_page": 20,
    "current_page": 1,
    "last_page": 76
  }
}
```

---

## 8. REINTENTAR ENTREGA FALLIDA

**Reintenta manualmente una entrega fallida**

```http
POST {{base_url}}/api/v1/webhooks/deliveries/{{delivery_id}}/retry
Authorization: Bearer {{token}}
```

### Respuesta Exitosa

```json
{
  "success": true,
  "message": "Entrega reintentada exitosamente",
  "delivery": {
    "id": 520,
    "status": "success",
    "attempts": 4,
    "response_code": 200,
    "delivered_at": "2025-12-23T10:15:00.000Z"
  }
}
```

---

## 9. ELIMINAR WEBHOOK

```http
DELETE {{base_url}}/api/v1/webhooks/{{webhook_id}}
Authorization: Bearer {{token}}
```

### Respuesta

```json
{
  "success": true,
  "message": "Webhook eliminado correctamente"
}
```

---

## 📝 PAYLOAD QUE RECIBIRÁS EN TU WEBHOOK

Cuando ocurra un evento, tu URL recibirá un POST con este formato:

### Headers

```
POST /webhook HTTP/1.1
Host: tu-sistema.com
Content-Type: application/json
User-Agent: FacturacionElectronica/1.0
X-Webhook-Signature: a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6
X-Webhook-Event: invoice.accepted
X-API-Key: mi-api-key-secreta-123
```

### Body - Ejemplo: invoice.accepted

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
      "sunat_response": {
        "cdr": "<?xml version=\"1.0\" encoding=\"UTF-8\"?>...",
        "ticket": "1234567890",
        "code": "0",
        "description": "La Factura numero F001-00000123, ha sido aceptada"
      }
    }
  }
}
```

### Body - Ejemplo: invoice.rejected

```json
{
  "event": "invoice.rejected",
  "timestamp": "2025-12-23T10:35:00.000Z",
  "data": {
    "document_id": 124,
    "document_type": "invoice",
    "numero": "F001-00000124",
    "company_id": 1,
    "client": {
      "tipo_documento": "6",
      "numero_documento": "20987654321",
      "razon_social": "CLIENTE TEST SAC"
    },
    "monto": 2500.00,
    "moneda": "PEN",
    "fecha_emision": "2025-12-23T10:30:00.000Z",
    "estado_sunat": "RECHAZADO",
    "result": {
      "success": false,
      "error": "El RUC del cliente no existe en SUNAT",
      "code": "2324"
    }
  }
}
```

---

## 🔐 VALIDAR FIRMA HMAC

**En tu servidor receptor, SIEMPRE valida la firma:**

### PHP
```php
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'];
$secret = 'whsec_mi_secreto_para_firmar';

$expectedSignature = hash_hmac('sha256', $payload, $secret);

if (hash_equals($expectedSignature, $signature)) {
    // Webhook auténtico
} else {
    http_response_code(401);
    exit;
}
```

### Node.js
```javascript
const crypto = require('crypto');

const signature = req.headers['x-webhook-signature'];
const secret = 'whsec_mi_secreto_para_firmar';

const expectedSignature = crypto
    .createHmac('sha256', secret)
    .update(req.rawBody)
    .digest('hex');

if (crypto.timingSafeEqual(Buffer.from(signature), Buffer.from(expectedSignature))) {
    // Webhook auténtico
} else {
    res.status(401).send('Invalid signature');
}
```

---

## 🧪 TESTING LOCAL CON NGROK

1. **Instalar ngrok:** https://ngrok.com/download

2. **Ejecutar tu servidor local:**
   ```bash
   php -S localhost:8080 webhook-receiver.php
   # o
   node webhook-receiver-nodejs.js
   ```

3. **Exponer con ngrok:**
   ```bash
   ngrok http 8080
   ```

4. **Copiar la URL HTTPS que te da ngrok:**
   ```
   Forwarding: https://abc123.ngrok.io -> http://localhost:8080
   ```

5. **Usar esa URL en tu webhook:**
   ```
   https://abc123.ngrok.io/webhook-receiver.php
   # o
   https://abc123.ngrok.io/webhook
   ```

---

## 📊 EVENTOS DISPONIBLES

| Evento | Descripción | Cuándo se dispara |
|--------|-------------|-------------------|
| `invoice.created` | Factura creada | Al crear la factura |
| `invoice.accepted` | Factura aceptada | SUNAT acepta la factura |
| `invoice.rejected` | Factura rechazada | SUNAT rechaza la factura |
| `invoice.voided` | Factura anulada | Al anular la factura |
| `boleta.created` | Boleta creada | Al crear la boleta |
| `boleta.accepted` | Boleta aceptada | SUNAT acepta el resumen diario |
| `boleta.rejected` | Boleta rechazada | SUNAT rechaza el resumen diario |
| `credit_note.created` | Nota de crédito creada | Al crear nota de crédito |
| `credit_note.accepted` | Nota de crédito aceptada | SUNAT acepta la NC |
| `debit_note.created` | Nota de débito creada | Al crear nota de débito |
| `debit_note.accepted` | Nota de débito aceptada | SUNAT acepta la ND |
| `webhook.test` | Webhook de prueba | Al usar el endpoint /test |

---

## ⚙️ CONFIGURACIÓN DEL CRON

Para que los reintentos automáticos funcionen, configura este cron job:

```bash
# Ejecutar cada 5 minutos
*/5 * * * * cd /path/to/project && php artisan webhooks:process --limit=100 >> /var/log/webhooks-cron.log 2>&1
```

O ejecutar manualmente:
```bash
php artisan webhooks:process --limit=100
```

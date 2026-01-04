# Sistema de Webhooks - Notificaciones en Tiempo Real

## Índice
1. [¿Qué son los Webhooks?](#qué-son-los-webhooks)
2. [¿Para qué sirven?](#para-qué-sirven)
3. [Arquitectura del Sistema](#arquitectura-del-sistema)
4. [Tablas de la Base de Datos](#tablas-de-la-base-de-datos)
5. [Eventos Disponibles](#eventos-disponibles)
6. [Flujo de Funcionamiento](#flujo-de-funcionamiento)
7. [API de Webhooks](#api-de-webhooks)
8. [Seguridad y Firmas](#seguridad-y-firmas)
9. [Sistema de Reintentos](#sistema-de-reintentos)
10. [Alternativas para Probar Webhooks](#alternativas-para-probar-webhooks)
11. [Ejemplos Prácticos Completos](#ejemplos-prácticos-completos)
12. [Monitoreo y Estadísticas](#monitoreo-y-estadísticas)
13. [Solución de Problemas](#solución-de-problemas)

---

## ¿Qué son los Webhooks?

Los **webhooks** son notificaciones HTTP automáticas que tu API envía a URLs externas cuando ocurren eventos específicos. Son llamadas HTTP tipo "push" (empuje) en lugar de "pull" (solicitud).

### Analogía Simple
Piensa en los webhooks como un **sistema de alertas telefónicas**:
- **Sin webhooks:** Tú llamas cada 5 minutos preguntando "¿ya pasó algo?"
- **Con webhooks:** El sistema te llama automáticamente cuando algo importante ocurre

### Diferencia con APIs Tradicionales

#### API Tradicional (Polling)
```
Cliente → [cada X segundos] → Servidor
  "¿Hay algo nuevo?"
  "¿Hay algo nuevo?"
  "¿Hay algo nuevo?"
```
**Problemas:**
- ❌ Desperdicio de recursos
- ❌ Latencia (demora en enterarse)
- ❌ Muchas peticiones innecesarias

#### Webhooks (Event-Driven)
```
Servidor → [cuando ocurre evento] → Cliente
  "¡Hey! Factura F001-123 fue ACEPTADA"
```
**Beneficios:**
- ✅ Eficiente (solo envía cuando hay cambios)
- ✅ Tiempo real (notificación inmediata)
- ✅ Menos carga en el servidor

---

## ¿Para qué sirven?

### Casos de Uso Reales

#### 1. Integración con ERP/Sistema de Ventas
```
Sistema de Ventas ← webhook ← API de Facturación
  Recibe: "Factura aceptada por SUNAT"
  Acción: Actualizar estado, imprimir, enviar email
```

#### 2. Notificaciones a Aplicaciones Móviles
```
App Móvil ← webhook ← API
  Recibe: "Boleta rechazada"
  Acción: Push notification al usuario
```

#### 3. Automatización de Procesos
```
Sistema de Reportes ← webhook ← API
  Recibe: "Nueva factura emitida"
  Acción: Generar reporte automático
```

#### 4. Sincronización con Sistemas Externos
```
Sistema Contable ← webhook ← API
  Recibe: "Nota de crédito aceptada"
  Acción: Registrar en contabilidad
```

#### 5. Monitoreo y Alertas
```
Sistema de Monitoreo ← webhook ← API
  Recibe: "Factura rechazada"
  Acción: Enviar alerta al equipo técnico
```

---

## Arquitectura del Sistema

### Componentes del Sistema de Webhooks

```
┌─────────────────────────────────────────────────────────────┐
│                    FLUJO DE WEBHOOKS                        │
└─────────────────────────────────────────────────────────────┘

1. EVENTO DISPARADOR
   │
   │  POST /api/v1/invoices/1/send-sunat
   │
   ├─► DocumentService::sendToSunat()
   │
   ├─► SUNAT responde: "ACEPTADO"
   │
   └─► event(new DocumentSentToSunat(...))
       │
       │
2. LISTENER DE EVENTOS
   │
   ├─► SendDocumentNotification::handle()
   │   │
   │   ├─► Envía email (si configurado)
   │   │
   │   └─► WebhookService::trigger()
   │
   │
3. CREACIÓN DE DELIVERY
   │
   ├─► Busca webhooks activos para el evento
   │
   ├─► Crea WebhookDelivery (status: pending)
   │
   └─► Guarda en tabla webhook_deliveries
       │
       │
4. ENVÍO DEL WEBHOOK
   │
   ├─► WebhookService::deliver()
   │   │
   │   ├─► Genera firma HMAC (seguridad)
   │   │
   │   ├─► HTTP POST a URL configurada
   │   │
   │   └─► Respuesta exitosa (200-299)?
   │       │
   │       ├─► SÍ: Marca como "success"
   │       │
   │       └─► NO: Marca como "pending" → reintento
   │
   │
5. SISTEMA DE REINTENTOS
   │
   ├─► ProcessPendingWebhooks (comando)
   │
   ├─► Procesa deliveries con status "pending"
   │
   └─► Máximo 3 reintentos con delay exponencial
```

### Archivos y Responsabilidades

| Archivo | Responsabilidad |
|---------|----------------|
| `app/Models/Webhook.php` | Modelo de configuración de webhooks |
| `app/Models/WebhookDelivery.php` | Modelo de registros de envíos |
| `app/Services/WebhookService.php` | Lógica de envío y procesamiento |
| `app/Http/Controllers/Api/WebhookController.php` | API REST para gestionar webhooks |
| `app/Listeners/SendDocumentNotification.php` | Escucha eventos y dispara webhooks |
| `app/Jobs/ProcessWebhook.php` | Job para procesar webhooks en cola |
| `app/Console/Commands/ProcessPendingWebhooks.php` | Comando para reintentos |
| `app/Events/DocumentSentToSunat.php` | Evento disparado al enviar a SUNAT |

---

## Tablas de la Base de Datos

### Tabla: `webhooks`
Almacena la configuración de webhooks registrados.

**Estructura:**
```sql
CREATE TABLE webhooks (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    company_id BIGINT NOT NULL,
    name VARCHAR(255) NOT NULL,
    url VARCHAR(255) NOT NULL,
    method VARCHAR(10) DEFAULT 'POST',
    events JSON NOT NULL,
    headers JSON NULL,
    secret VARCHAR(255) NULL,
    active BOOLEAN DEFAULT TRUE,
    timeout INT DEFAULT 30,
    max_retries INT DEFAULT 3,
    retry_delay INT DEFAULT 60,
    last_triggered_at TIMESTAMP NULL,
    last_status VARCHAR(50) NULL,
    last_error TEXT NULL,
    success_count INT DEFAULT 0,
    failure_count INT DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL,

    INDEX idx_company_active (company_id, active)
);
```

**Campos Importantes:**

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `company_id` | BIGINT | ID de la empresa dueña del webhook |
| `name` | VARCHAR | Nombre descriptivo del webhook |
| `url` | VARCHAR | URL destino donde se enviarán las notificaciones |
| `method` | VARCHAR | Método HTTP (POST, PUT, PATCH) |
| `events` | JSON | Array de eventos suscritos |
| `headers` | JSON | Headers HTTP personalizados |
| `secret` | VARCHAR | Clave secreta para firma HMAC SHA256 |
| `active` | BOOLEAN | Si el webhook está activo |
| `timeout` | INT | Timeout en segundos (5-120) |
| `max_retries` | INT | Máximo de reintentos (0-10) |
| `retry_delay` | INT | Segundos entre reintentos |
| `success_count` | INT | Contador de envíos exitosos |
| `failure_count` | INT | Contador de envíos fallidos |

**Ejemplo de Registro:**
```json
{
  "id": 1,
  "company_id": 1,
  "name": "Sistema de Ventas Principal",
  "url": "https://ventas.miempresa.com/api/webhooks/facturas",
  "method": "POST",
  "events": ["invoice.accepted", "invoice.rejected"],
  "headers": {
    "X-API-Key": "abc123",
    "X-System": "ERP"
  },
  "secret": "whsec_a1b2c3d4e5f6g7h8i9j0",
  "active": true,
  "timeout": 30,
  "max_retries": 3,
  "retry_delay": 60,
  "success_count": 245,
  "failure_count": 3
}
```

---

### Tabla: `webhook_deliveries`
Almacena el historial de cada envío de webhook (exitoso o fallido).

**Estructura:**
```sql
CREATE TABLE webhook_deliveries (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    webhook_id BIGINT NOT NULL,
    event VARCHAR(100) NOT NULL,
    payload JSON NOT NULL,
    status VARCHAR(20) NOT NULL,
    attempts INT DEFAULT 0,
    response_code INT NULL,
    response_body TEXT NULL,
    error_message TEXT NULL,
    delivered_at TIMESTAMP NULL,
    next_retry_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    INDEX idx_webhook_status (webhook_id, status),
    INDEX idx_status_retry (status, next_retry_at),
    FOREIGN KEY (webhook_id) REFERENCES webhooks(id) ON DELETE CASCADE
);
```

**Campos Importantes:**

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `webhook_id` | BIGINT | ID del webhook que disparó esta entrega |
| `event` | VARCHAR | Nombre del evento (ej: "invoice.accepted") |
| `payload` | JSON | Datos enviados en el webhook |
| `status` | VARCHAR | Estado: "pending", "success", "failed" |
| `attempts` | INT | Número de intentos realizados |
| `response_code` | INT | Código HTTP de respuesta (200, 500, etc.) |
| `response_body` | TEXT | Cuerpo de la respuesta del servidor destino |
| `error_message` | TEXT | Mensaje de error (si falló) |
| `delivered_at` | TIMESTAMP | Cuándo se entregó exitosamente |
| `next_retry_at` | TIMESTAMP | Cuándo se reintentará (si está pendiente) |

**Estados Posibles:**

| Estado | Descripción |
|--------|-------------|
| `pending` | Pendiente de envío o reintento |
| `success` | Entregado exitosamente (HTTP 2xx) |
| `failed` | Falló después de todos los reintentos |

**Ejemplo de Registro:**
```json
{
  "id": 1,
  "webhook_id": 1,
  "event": "invoice.accepted",
  "payload": {
    "event": "invoice.accepted",
    "timestamp": "2025-12-10T15:30:00Z",
    "data": {
      "document_id": 123,
      "numero": "F001-00000123",
      "monto": 1500.00
    }
  },
  "status": "success",
  "attempts": 1,
  "response_code": 200,
  "response_body": "{\"success\": true}",
  "delivered_at": "2025-12-10T15:30:02Z"
}
```

---

## Eventos Disponibles

### Lista Completa de Eventos

El sistema dispara webhooks para los siguientes eventos:

#### Facturas (Invoices)
| Evento | Descripción | Cuándo se dispara |
|--------|-------------|-------------------|
| `invoice.created` | Factura creada | Al crear una factura (POST /invoices) |
| `invoice.accepted` | Factura aceptada | Cuando SUNAT acepta la factura |
| `invoice.rejected` | Factura rechazada | Cuando SUNAT rechaza la factura |
| `invoice.voided` | Factura anulada | Cuando se anula una factura |

#### Boletas
| Evento | Descripción | Cuándo se dispara |
|--------|-------------|-------------------|
| `boleta.created` | Boleta creada | Al crear una boleta (POST /boletas) |
| `boleta.accepted` | Boleta aceptada | Cuando SUNAT acepta el resumen diario |
| `boleta.rejected` | Boleta rechazada | Cuando SUNAT rechaza el resumen diario |

#### Notas de Crédito
| Evento | Descripción | Cuándo se dispara |
|--------|-------------|-------------------|
| `credit_note.created` | NC creada | Al crear una nota de crédito |
| `credit_note.accepted` | NC aceptada | Cuando SUNAT acepta la NC |

#### Notas de Débito
| Evento | Descripción | Cuándo se dispara |
|--------|-------------|-------------------|
| `debit_note.created` | ND creada | Al crear una nota de débito |
| `debit_note.accepted` | ND aceptada | Cuando SUNAT acepta la ND |

### Estructura del Payload

Todos los webhooks envían un payload con esta estructura:

```json
{
  "event": "invoice.accepted",
  "timestamp": "2025-12-10T15:30:00.000Z",
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
    "fecha_emision": "2025-12-10T10:00:00.000Z",
    "estado_sunat": "ACEPTADO",
    "result": {
      "success": true,
      "sunat_response": {
        "cdr": "...",
        "ticket": "...",
        "code": "0"
      }
    }
  }
}
```

---

## Flujo de Funcionamiento

### Flujo Detallado Paso a Paso

#### 1. Registro de Webhook (Una sola vez)

```http
POST /api/v1/webhooks
Content-Type: application/json
Authorization: Bearer {token}

{
  "company_id": 1,
  "name": "Sistema de Ventas",
  "url": "https://ventas.com/api/webhook",
  "events": ["invoice.accepted", "invoice.rejected"]
}
```

**Resultado:** Se crea registro en tabla `webhooks`

#### 2. Emisión de Documento

```http
POST /api/v1/invoices
{
  "company_id": 1,
  "branch_id": 1,
  "client": {...},
  "items": [...]
}
```

**Resultado:** Se crea la factura (NO dispara webhook todavía)

#### 3. Envío a SUNAT

```http
POST /api/v1/invoices/123/send-sunat
```

**Proceso interno:**
```php
// 1. DocumentService envía a SUNAT
$result = DocumentService::sendToSunat($invoice);

// 2. SUNAT responde
$result = [
    'success' => true,
    'sunat_response' => [...],
    'estado_sunat' => 'ACEPTADO'
];

// 3. Se dispara evento
event(new DocumentSentToSunat($invoice, 'invoice', $result));
```

#### 4. Listener Captura Evento

**Archivo:** `app/Listeners/SendDocumentNotification.php`

```php
public function handle(DocumentSentToSunat $event): void
{
    // 1. Enviar email (si configurado)
    $document->company->notify(new DocumentAcceptedBySunat(...));

    // 2. Disparar webhook
    $this->triggerWebhook($document, 'invoice', 'accepted', $result);
}

protected function triggerWebhook(...): void
{
    $event = "invoice.accepted"; // invoice + accepted

    $payload = [
        'document_id' => $invoice->id,
        'numero' => 'F001-00000123',
        // ...más datos
    ];

    $this->webhookService->trigger($companyId, $event, $payload);
}
```

#### 5. WebhookService Procesa

**Archivo:** `app/Services/WebhookService.php`

```php
public function trigger(int $companyId, string $event, array $payload): void
{
    // 1. Buscar webhooks activos de esta empresa
    $webhooks = Webhook::where('company_id', $companyId)
        ->where('active', true)
        ->get()
        ->filter(fn($webhook) => $webhook->handlesEvent($event));

    // 2. Crear delivery para cada webhook
    foreach ($webhooks as $webhook) {
        $this->createDelivery($webhook, $event, $payload);
    }
}

protected function createDelivery(...): WebhookDelivery
{
    return WebhookDelivery::create([
        'webhook_id' => $webhook->id,
        'event' => $event,
        'payload' => $this->preparePayload($payload, $event),
        'status' => 'pending',
        'attempts' => 0
    ]);
}
```

#### 6. Envío del Webhook

```php
public function deliver(WebhookDelivery $delivery): bool
{
    $webhook = $delivery->webhook;

    // 1. Generar firma de seguridad
    $signature = hash_hmac('sha256', json_encode($payload), $webhook->secret);

    // 2. Preparar headers
    $headers = [
        'X-Webhook-Signature' => $signature,
        'X-Webhook-Event' => $delivery->event,
        'User-Agent' => 'FacturacionElectronica/1.0'
    ];

    // 3. Enviar HTTP POST
    $response = Http::timeout($webhook->timeout)
        ->withHeaders($headers)
        ->post($webhook->url, $delivery->payload);

    // 4. Procesar respuesta
    if ($response->successful()) {
        $delivery->markAsSuccess($response->status(), $response->body());
        $webhook->recordSuccess();
        return true;
    } else {
        $error = "HTTP {$response->status()}: {$response->body()}";
        $delivery->markAsFailed($error, $response->status());
        $webhook->recordFailure($error);
        return false;
    }
}
```

#### 7. Sistema Receptor Recibe

**En tu sistema externo (ejemplo PHP):**

```php
<?php
// https://ventas.com/api/webhook

// 1. Recibir datos
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'];
$event = $_SERVER['HTTP_X_WEBHOOK_EVENT'];

// 2. Verificar firma (seguridad)
$secret = 'tu-secret-configurado';
$expectedSignature = hash_hmac('sha256', $payload, $secret);

if (!hash_equals($expectedSignature, $signature)) {
    http_response_code(401);
    die('Firma inválida');
}

// 3. Procesar evento
$data = json_decode($payload, true);

if ($data['event'] === 'invoice.accepted') {
    $numero = $data['data']['numero'];
    $monto = $data['data']['monto'];

    // Actualizar tu base de datos
    updateFacturaEstado($numero, 'ACEPTADO');

    // Enviar email al cliente
    enviarEmailCliente($numero);

    // Imprimir factura
    imprimirFactura($numero);
}

// 4. Responder éxito
http_response_code(200);
echo json_encode(['success' => true]);
```

---

## API de Webhooks

### Endpoints Disponibles

**Base URL:** `/api/v1/webhooks`

#### 1. Listar Webhooks

```http
GET /api/v1/webhooks?company_id=1
Authorization: Bearer {token}
```

**Respuesta:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "company_id": 1,
      "name": "Sistema de Ventas",
      "url": "https://ventas.com/webhook",
      "method": "POST",
      "events": ["invoice.accepted", "invoice.rejected"],
      "active": true,
      "success_count": 150,
      "failure_count": 2,
      "last_triggered_at": "2025-12-10T15:30:00Z",
      "last_status": "success"
    }
  ]
}
```

#### 2. Crear Webhook

```http
POST /api/v1/webhooks
Authorization: Bearer {token}
Content-Type: application/json

{
  "company_id": 1,
  "name": "Sistema ERP Principal",
  "url": "https://erp.miempresa.com/api/webhooks/facturas",
  "method": "POST",
  "events": [
    "invoice.accepted",
    "invoice.rejected",
    "boleta.accepted"
  ],
  "headers": {
    "X-API-Key": "mi-api-key-123",
    "X-System-ID": "ERP-001"
  },
  "secret": "mi-secret-super-seguro",
  "timeout": 30,
  "max_retries": 3,
  "retry_delay": 60
}
```

**Validaciones:**
- `company_id`: required, debe existir en tabla companies
- `name`: required, máximo 255 caracteres
- `url`: required, debe ser URL válida
- `method`: opcional, valores: POST, PUT, PATCH (default: POST)
- `events`: required, array, mínimo 1 evento
- `events.*`: debe ser uno de los eventos válidos
- `headers`: opcional, objeto JSON
- `secret`: opcional, string (se genera automático si no se provee)
- `timeout`: opcional, 5-120 segundos (default: 30)
- `max_retries`: opcional, 0-10 (default: 3)
- `retry_delay`: opcional, mínimo 10 segundos (default: 60)

**Respuesta exitosa (201):**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "company_id": 1,
    "name": "Sistema ERP Principal",
    "url": "https://erp.miempresa.com/api/webhooks/facturas",
    "secret": "whsec_generado_automaticamente",
    "active": true,
    "created_at": "2025-12-10T16:00:00Z"
  },
  "message": "Webhook creado correctamente"
}
```

#### 3. Ver Detalles de Webhook

```http
GET /api/v1/webhooks/{id}
Authorization: Bearer {token}
```

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "company_id": 1,
    "name": "Sistema de Ventas",
    "url": "https://ventas.com/webhook",
    "events": ["invoice.accepted"],
    "active": true,
    "success_count": 150,
    "failure_count": 2,
    "deliveries": [
      {
        "id": 1,
        "event": "invoice.accepted",
        "status": "success",
        "created_at": "2025-12-10T15:30:00Z"
      }
    ]
  }
}
```

#### 4. Actualizar Webhook

```http
PUT /api/v1/webhooks/{id}
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Sistema de Ventas v2",
  "url": "https://ventas-v2.com/webhook",
  "active": false,
  "events": ["invoice.accepted", "invoice.rejected", "boleta.accepted"]
}
```

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Sistema de Ventas v2",
    "url": "https://ventas-v2.com/webhook",
    "active": false
  },
  "message": "Webhook actualizado correctamente"
}
```

#### 5. Eliminar Webhook

```http
DELETE /api/v1/webhooks/{id}
Authorization: Bearer {token}
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Webhook eliminado correctamente"
}
```

**Nota:** Usa soft delete, no elimina físicamente el registro.

#### 6. Probar Webhook

```http
POST /api/v1/webhooks/{id}/test
Authorization: Bearer {token}
```

Envía un webhook de prueba con evento `webhook.test`.

**Payload enviado:**
```json
{
  "event": "webhook.test",
  "timestamp": "2025-12-10T16:30:00Z",
  "data": {
    "message": "Test webhook delivery",
    "webhook_id": 1,
    "webhook_name": "Sistema de Ventas"
  }
}
```

**Respuesta exitosa:**
```json
{
  "success": true,
  "data": {
    "success": true,
    "status_code": 200,
    "response_body": "{\"success\": true}",
    "response_time": 0.234
  },
  "message": "Webhook probado exitosamente"
}
```

**Respuesta fallida:**
```json
{
  "success": false,
  "data": {
    "success": false,
    "error": "Connection timeout"
  },
  "message": "Webhook falló la prueba"
}
```

#### 7. Ver Historial de Entregas

```http
GET /api/v1/webhooks/{id}/deliveries?per_page=15
Authorization: Bearer {token}
```

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "webhook_id": 1,
        "event": "invoice.accepted",
        "status": "success",
        "attempts": 1,
        "response_code": 200,
        "delivered_at": "2025-12-10T15:30:00Z",
        "created_at": "2025-12-10T15:30:00Z"
      },
      {
        "id": 2,
        "event": "invoice.rejected",
        "status": "failed",
        "attempts": 3,
        "response_code": 500,
        "error_message": "HTTP 500: Internal Server Error",
        "created_at": "2025-12-10T14:00:00Z"
      }
    ],
    "total": 150,
    "per_page": 15
  }
}
```

#### 8. Reintentar Entrega Fallida

```http
POST /api/v1/webhooks/deliveries/{deliveryId}/retry
Authorization: Bearer {token}
```

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "status": "success",
    "attempts": 4,
    "response_code": 200,
    "delivered_at": "2025-12-10T16:45:00Z"
  },
  "message": "Webhook reintentado exitosamente"
}
```

#### 9. Ver Estadísticas de Webhook

```http
GET /api/v1/webhooks/{id}/statistics
Authorization: Bearer {token}
```

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "total_deliveries": 152,
    "successful": 150,
    "failed": 2,
    "pending": 0,
    "success_rate": 98.68,
    "failure_rate": 1.32,
    "last_triggered_at": "2025-12-10T15:30:00Z",
    "last_status": "success"
  }
}
```

---

## Seguridad y Firmas

### Sistema de Firma HMAC SHA256

Para garantizar que los webhooks son auténticos y no han sido manipulados, el sistema implementa firma HMAC SHA256.

#### ¿Cómo Funciona?

1. **Al crear el webhook**, se genera (o provees) un `secret`:
   ```
   secret: "whsec_a1b2c3d4e5f6g7h8i9j0"
   ```

2. **Al enviar el webhook**, se genera una firma:
   ```php
   $signature = hash_hmac('sha256', json_encode($payload), $secret);
   ```

3. **Se envía en el header**:
   ```
   X-Webhook-Signature: abc123def456...
   ```

4. **Tu sistema receptor debe verificar**:
   ```php
   $receivedSignature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'];
   $expectedSignature = hash_hmac('sha256', $payload, $secret);

   if (hash_equals($expectedSignature, $receivedSignature)) {
       // ✅ Webhook auténtico
   } else {
       // ❌ Webhook falso o manipulado
   }
   ```

### Headers de Seguridad Enviados

Todos los webhooks incluyen estos headers:

| Header | Valor | Descripción |
|--------|-------|-------------|
| `X-Webhook-Signature` | `abc123...` | Firma HMAC SHA256 del payload |
| `X-Webhook-Event` | `invoice.accepted` | Tipo de evento |
| `User-Agent` | `FacturacionElectronica/1.0` | Identificador del sistema |
| `Content-Type` | `application/json` | Tipo de contenido |

Headers personalizados configurados también se incluyen.

### Implementación de Verificación

#### PHP
```php
<?php
function verificarWebhook($payload, $signatureRecibida, $secret) {
    $signatureEsperada = hash_hmac('sha256', $payload, $secret);

    // Usar hash_equals para prevenir timing attacks
    return hash_equals($signatureEsperada, $signatureRecibida);
}

// Uso
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'];
$secret = 'whsec_a1b2c3d4e5f6g7h8i9j0';

if (verificarWebhook($payload, $signature, $secret)) {
    // Procesar webhook
    $data = json_decode($payload, true);
    processEvent($data);

    http_response_code(200);
    echo json_encode(['success' => true]);
} else {
    http_response_code(401);
    echo json_encode(['error' => 'Firma inválida']);
}
```

#### Node.js (Express)
```javascript
const crypto = require('crypto');
const express = require('express');
const app = express();

app.post('/webhook', express.raw({type: 'application/json'}), (req, res) => {
    const signature = req.headers['x-webhook-signature'];
    const secret = 'whsec_a1b2c3d4e5f6g7h8i9j0';

    const expectedSignature = crypto
        .createHmac('sha256', secret)
        .update(req.body)
        .digest('hex');

    if (signature === expectedSignature) {
        const data = JSON.parse(req.body);
        // Procesar webhook
        processEvent(data);
        res.json({ success: true });
    } else {
        res.status(401).json({ error: 'Firma inválida' });
    }
});
```

#### Python (Flask)
```python
import hmac
import hashlib
from flask import Flask, request, jsonify

app = Flask(__name__)

@app.route('/webhook', methods=['POST'])
def webhook():
    signature = request.headers.get('X-Webhook-Signature')
    secret = b'whsec_a1b2c3d4e5f6g7h8i9j0'
    payload = request.get_data()

    expected_signature = hmac.new(
        secret,
        payload,
        hashlib.sha256
    ).hexdigest()

    if hmac.compare_digest(signature, expected_signature):
        data = request.get_json()
        # Procesar webhook
        process_event(data)
        return jsonify({'success': True}), 200
    else:
        return jsonify({'error': 'Firma inválida'}), 401
```

---

## Sistema de Reintentos

### Configuración de Reintentos

Cada webhook tiene configuración individual:

```json
{
  "max_retries": 3,
  "retry_delay": 60
}
```

### Estrategia de Reintentos

El sistema usa **exponential backoff** (delay exponencial):

| Intento | Delay | Cuándo se reintenta |
|---------|-------|---------------------|
| 1 | 0s | Inmediatamente |
| 2 | 60s | 1 minuto después |
| 3 | 120s | 2 minutos después |
| 4 | 180s | 3 minutos después |

**Fórmula:** `delay = retry_delay * attempts`

### Estados Durante Reintentos

```
┌──────────────────────────────────────────────────┐
│           CICLO DE VIDA DE UN DELIVERY           │
└──────────────────────────────────────────────────┘

CREACIÓN
  ↓
[status: pending, attempts: 0]
  ↓
INTENTO 1 (inmediato)
  ↓
  ├─► Éxito (HTTP 2xx)
  │   ↓
  │   [status: success, attempts: 1]
  │   ✅ FIN
  │
  └─► Fallo (HTTP 4xx/5xx o timeout)
      ↓
      [status: pending, attempts: 1, next_retry_at: +60s]
      ↓
      INTENTO 2 (60s después)
      ↓
      ├─► Éxito
      │   ↓
      │   [status: success, attempts: 2]
      │   ✅ FIN
      │
      └─► Fallo
          ↓
          [status: pending, attempts: 2, next_retry_at: +120s]
          ↓
          INTENTO 3 (120s después)
          ↓
          ├─► Éxito
          │   ↓
          │   [status: success, attempts: 3]
          │   ✅ FIN
          │
          └─► Fallo
              ↓
              [status: failed, attempts: 3]
              ❌ FIN (permanentemente fallido)
```

### Procesamiento de Reintentos

Los reintentos se procesan mediante comando programado:

```bash
php artisan webhooks:process --limit=100
```

**Configuración en Cron:**
```bash
# Procesar webhooks pendientes cada 5 minutos
*/5 * * * * cd /ruta/proyecto && php artisan webhooks:process
```

**Archivo:** `app/Console/Commands/ProcessPendingWebhooks.php`

```php
public function handle(WebhookService $webhookService): int
{
    $limit = (int) $this->option('limit');

    $processed = $webhookService->processPendingDeliveries($limit);

    $this->info("✅ Webhooks procesados: {$processed}");

    return Command::SUCCESS;
}
```

### Criterios para Reintentar

Un delivery se reintenta si:

1. ✅ `status === 'pending'`
2. ✅ `attempts < max_retries`
3. ✅ `next_retry_at` es NULL o ya pasó

```php
// WebhookDelivery.php
public function shouldRetry(): bool
{
    return $this->isPending()
        && $this->attempts < $this->webhook->max_retries
        && ($this->next_retry_at === null || $this->next_retry_at->isPast());
}
```

### Reintento Manual

Puedes forzar un reintento manualmente:

```http
POST /api/v1/webhooks/deliveries/{deliveryId}/retry
```

Esto resetea:
- `status` → `'pending'`
- `attempts` → `0`
- `next_retry_at` → `now()`
- `error_message` → `null`

---

## Alternativas para Probar Webhooks

### Opción 1: RequestBin (Pipedream) ⭐ RECOMENDADO

**URL:** https://requestbin.com

**Características:**
- ✅ 100% gratuito
- ✅ No requiere registro
- ✅ Muestra headers, body, timestamps
- ✅ Historial de requests
- ✅ URL válida por 48 horas

**Cómo usar:**

1. Ve a https://requestbin.com
2. Click en **"Create a RequestBin"**
3. Te genera URL: `https://eo1234abcd.x.pipedream.net`
4. Registra webhook:

```bash
curl -X POST http://localhost:8000/api/v1/webhooks \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": 1,
    "name": "Test RequestBin",
    "url": "https://eo1234abcd.x.pipedream.net",
    "events": ["invoice.accepted", "invoice.rejected"]
  }'
```

5. Envía una factura a SUNAT
6. Revisa RequestBin para ver el webhook

**Ejemplo de visualización:**

```
┌─────────────────────────────────────────┐
│ RequestBin - Request #1                 │
├─────────────────────────────────────────┤
│ POST /                                  │
│ 2025-12-10 16:30:45                    │
├─────────────────────────────────────────┤
│ Headers:                                │
│   X-Webhook-Signature: abc123...        │
│   X-Webhook-Event: invoice.accepted     │
│   Content-Type: application/json        │
├─────────────────────────────────────────┤
│ Body:                                   │
│ {                                       │
│   "event": "invoice.accepted",          │
│   "timestamp": "2025-12-10T16:30:45Z",  │
│   "data": {                             │
│     "document_id": 123,                 │
│     "numero": "F001-00000123"           │
│   }                                     │
│ }                                       │
└─────────────────────────────────────────┘
```

---

### Opción 2: Webhook.site

**URL:** https://webhook.site

**Características:**
- ✅ Gratuito para uso básico
- ✅ URL instantánea sin registro
- ✅ Interfaz en tiempo real
- ✅ Muestra JSON formateado

**Cómo usar:**

1. Ve a https://webhook.site
2. Copia tu URL única (aparece automáticamente)
3. Usa esa URL en tu webhook

**Nota:** La versión gratuita es suficiente para pruebas. NO necesitas pagar.

---

### Opción 3: Beeceptor

**URL:** https://beeceptor.com

**Características:**
- ✅ Gratuito
- ✅ URL personalizable
- ✅ Puedes simular respuestas
- ✅ Historial de requests

**Cómo usar:**

1. Ve a https://beeceptor.com
2. Elige un nombre: `mitest`
3. Te da: `https://mitest.free.beeceptor.com`
4. Usa esa URL

**Ventaja:** Puedes configurar reglas de respuesta personalizadas.

---

### Opción 4: Endpoint Local con Laragon 🚀 MEJOR PARA DESARROLLO

Crea tu propio receptor de webhooks localmente.

#### Paso 1: Crear Endpoint Receptor

**Archivo:** `C:\laragon\www\webhook-test\index.php`

```php
<?php
/**
 * Receptor de Webhooks Local
 * Para probar webhooks en desarrollo
 */

// Capturar información
$method = $_SERVER['REQUEST_METHOD'];
$headers = getallheaders();
$body = file_get_contents('php://input');
$timestamp = date('Y-m-d H:i:s');

// Archivo de log
$logFile = __DIR__ . '/webhook-logs.txt';

// Formatear entrada
$logEntry = "\n" . str_repeat("=", 100) . "\n";
$logEntry .= "WEBHOOK RECIBIDO: {$timestamp}\n";
$logEntry .= str_repeat("=", 100) . "\n\n";

$logEntry .= "METHOD: {$method}\n";
$logEntry .= "URL: {$_SERVER['REQUEST_URI']}\n";
$logEntry .= "IP: {$_SERVER['REMOTE_ADDR']}\n\n";

$logEntry .= "HEADERS:\n";
$logEntry .= str_repeat("-", 100) . "\n";
foreach ($headers as $key => $value) {
    $logEntry .= sprintf("%-30s: %s\n", $key, $value);
}

$logEntry .= "\nBODY (RAW):\n";
$logEntry .= str_repeat("-", 100) . "\n";
$logEntry .= $body . "\n\n";

// Intentar decodificar JSON
$data = json_decode($body, true);
if ($data !== null) {
    $logEntry .= "BODY (DECODED):\n";
    $logEntry .= str_repeat("-", 100) . "\n";
    $logEntry .= print_r($data, true) . "\n";

    // Extraer información importante
    if (isset($data['event'])) {
        $logEntry .= "\nEVENTO: {$data['event']}\n";
    }
    if (isset($data['data']['numero'])) {
        $logEntry .= "NÚMERO: {$data['data']['numero']}\n";
    }
    if (isset($data['data']['monto'])) {
        $logEntry .= "MONTO: {$data['data']['monto']}\n";
    }
}

// Guardar en archivo
file_put_contents($logFile, $logEntry, FILE_APPEND);

// Responder con éxito
http_response_code(200);
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Webhook recibido y registrado',
    'timestamp' => $timestamp,
    'webhook_file' => $logFile
]);
```

#### Paso 2: Crear Vista HTML para Monitoreo

**Archivo:** `C:\laragon\www\webhook-test\view.php`

```php
<!DOCTYPE html>
<html>
<head>
    <title>Webhook Test - Monitor</title>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="5">
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            margin: 0;
        }
        .header {
            background: #2d2d2d;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .log-container {
            background: #2d2d2d;
            padding: 20px;
            border-radius: 5px;
            white-space: pre-wrap;
            word-wrap: break-word;
            max-height: 80vh;
            overflow-y: auto;
        }
        .webhook-entry {
            border-left: 3px solid #4CAF50;
            padding-left: 10px;
            margin-bottom: 20px;
        }
        .event {
            color: #4CAF50;
            font-weight: bold;
        }
        .timestamp {
            color: #888;
        }
        .clear-btn {
            background: #f44336;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📡 Webhook Test Monitor</h1>
        <p>Auto-refresh cada 5 segundos</p>
        <p>URL: <code>http://localhost/webhook-test/index.php</code></p>
        <form method="POST" style="display:inline;">
            <button type="submit" name="clear" class="clear-btn">🗑️ Limpiar Logs</button>
        </form>
    </div>

    <div class="log-container">
        <?php
        $logFile = __DIR__ . '/webhook-logs.txt';

        if (isset($_POST['clear'])) {
            file_put_contents($logFile, '');
            echo "Logs limpiados.\n";
        }

        if (file_exists($logFile)) {
            $content = file_get_contents($logFile);
            if (!empty($content)) {
                echo htmlspecialchars($content);
            } else {
                echo "No hay webhooks recibidos todavía.\n";
                echo "Esperando webhooks...\n";
            }
        } else {
            echo "Archivo de log no existe. Se creará al recibir el primer webhook.\n";
        }
        ?>
    </div>
</body>
</html>
```

#### Paso 3: Configurar Webhook en la API

```bash
curl -X POST http://localhost:8000/api/v1/webhooks \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": 1,
    "name": "Test Local Laragon",
    "url": "http://localhost/webhook-test/index.php",
    "method": "POST",
    "events": [
      "invoice.accepted",
      "invoice.rejected",
      "boleta.accepted",
      "credit_note.accepted"
    ],
    "active": true
  }'
```

#### Paso 4: Monitorear en Tiempo Real

Abre en tu navegador:
```
http://localhost/webhook-test/view.php
```

Se auto-refrescará cada 5 segundos para mostrar nuevos webhooks.

#### Paso 5: Probar

```bash
# Enviar factura a SUNAT
curl -X POST http://localhost:8000/api/v1/invoices/1/send-sunat \
  -H "Authorization: Bearer {token}"
```

Verás en `view.php`:

```
====================================================================================================
WEBHOOK RECIBIDO: 2025-12-10 16:45:30
====================================================================================================

METHOD: POST
URL: /
IP: 127.0.0.1

HEADERS:
----------------------------------------------------------------------------------------------------
X-Webhook-Signature         : abc123def456...
X-Webhook-Event             : invoice.accepted
Content-Type                : application/json
User-Agent                  : FacturacionElectronica/1.0

BODY (RAW):
----------------------------------------------------------------------------------------------------
{"event":"invoice.accepted","timestamp":"2025-12-10T16:45:30Z","data":{"document_id":1,"numero":"F001-00000001"}}

BODY (DECODED):
----------------------------------------------------------------------------------------------------
Array
(
    [event] => invoice.accepted
    [timestamp] => 2025-12-10T16:45:30Z
    [data] => Array
        (
            [document_id] => 1
            [numero] => F001-00000001
            [monto] => 1500.00
        )
)

EVENTO: invoice.accepted
NÚMERO: F001-00000001
MONTO: 1500.00
```

---

### Opción 5: Postman Echo

**URL:** https://postman-echo.com/post

**Características:**
- ✅ Responde con lo que le envías
- ✅ No requiere configuración
- ✅ Ideal para pruebas rápidas

**Uso:**

```bash
curl -X POST http://localhost:8000/api/v1/webhooks \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": 1,
    "name": "Test Postman Echo",
    "url": "https://postman-echo.com/post",
    "events": ["invoice.accepted"]
  }'
```

---

### Opción 6: Webhook Tester (Servicio Dedicado)

**URL:** https://webhook-test.com

Similar a webhook.site pero con interfaz más limpia.

---

### Comparativa de Alternativas

| Servicio | Gratis | Registro | URL Pública | Historial | Tiempo Real | Mejor Para |
|----------|--------|----------|-------------|-----------|-------------|------------|
| **RequestBin** | ✅ | ❌ | ✅ | ✅ | ✅ | Testing rápido |
| **Webhook.site** | ✅ | ❌ | ✅ | ✅ | ✅ | Testing rápido |
| **Beeceptor** | ✅ | ❌ | ✅ | ✅ | ✅ | Respuestas custom |
| **Endpoint Local** | ✅ | ❌ | ❌ | ✅ | ✅ | Desarrollo local |
| **Postman Echo** | ✅ | ❌ | ✅ | ❌ | ❌ | Pruebas básicas |

**Mi recomendación:**

1. **Para pruebas rápidas:** RequestBin o Webhook.site
2. **Para desarrollo:** Endpoint local en Laragon
3. **Para CI/CD:** Postman Echo

---

## Ejemplos Prácticos Completos

### Ejemplo 1: Integración con Sistema de Ventas (PHP)

**Escenario:** Tu sistema de ventas necesita saber cuando una factura es aceptada por SUNAT.

#### 1. Registrar Webhook

```bash
curl -X POST https://api-facturacion.com/api/v1/webhooks \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": 1,
    "name": "Sistema de Ventas",
    "url": "https://ventas.miempresa.com/api/sunat-webhook",
    "events": ["invoice.accepted", "invoice.rejected"],
    "secret": "mi-secret-super-seguro-123"
  }'
```

#### 2. Crear Endpoint Receptor

**Archivo:** `ventas.miempresa.com/api/sunat-webhook.php`

```php
<?php
require_once '../config/database.php';

// 1. Recibir webhook
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '';
$event = $_SERVER['HTTP_X_WEBHOOK_EVENT'] ?? '';

// 2. Verificar firma
$secret = 'mi-secret-super-seguro-123';
$expectedSignature = hash_hmac('sha256', $payload, $secret);

if (!hash_equals($expectedSignature, $signature)) {
    http_response_code(401);
    die(json_encode(['error' => 'Firma inválida']));
}

// 3. Decodificar datos
$data = json_decode($payload, true);

// 4. Log del evento
file_put_contents(
    '/var/log/webhooks.log',
    date('Y-m-d H:i:s') . " - Evento: {$event} - " . $payload . "\n",
    FILE_APPEND
);

// 5. Procesar según evento
try {
    $db = getDbConnection();

    switch ($data['event']) {
        case 'invoice.accepted':
            // Actualizar estado en base de datos
            $stmt = $db->prepare("
                UPDATE facturas
                SET estado = 'ACEPTADO_SUNAT',
                    fecha_aceptacion = NOW(),
                    estado_sunat = :estado_sunat
                WHERE numero = :numero
            ");
            $stmt->execute([
                'estado_sunat' => $data['data']['estado_sunat'],
                'numero' => $data['data']['numero']
            ]);

            // Enviar email al cliente
            $clienteEmail = getClienteEmail($data['data']['client']['numero_documento']);
            if ($clienteEmail) {
                sendFacturaEmail($clienteEmail, $data['data']['numero']);
            }

            // Imprimir factura automáticamente
            printFactura($data['data']['document_id']);

            // Actualizar inventario
            updateInventory($data['data']['document_id']);

            break;

        case 'invoice.rejected':
            // Marcar como rechazada
            $stmt = $db->prepare("
                UPDATE facturas
                SET estado = 'RECHAZADO_SUNAT',
                    motivo_rechazo = :error,
                    fecha_rechazo = NOW()
                WHERE numero = :numero
            ");
            $stmt->execute([
                'error' => $data['data']['result']['error'] ?? 'Error desconocido',
                'numero' => $data['data']['numero']
            ]);

            // Notificar al equipo
            sendAdminAlert("Factura rechazada: {$data['data']['numero']}");

            break;
    }

    // 6. Responder éxito
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Webhook procesado correctamente'
    ]);

} catch (Exception $e) {
    // 7. Log de error
    file_put_contents(
        '/var/log/webhook-errors.log',
        date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n",
        FILE_APPEND
    );

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// Funciones auxiliares
function sendFacturaEmail($email, $numero) {
    // Implementar envío de email
}

function printFactura($documentId) {
    // Implementar impresión
}

function updateInventory($documentId) {
    // Implementar actualización de inventario
}

function sendAdminAlert($message) {
    // Implementar alerta a administradores
}
```

---

### Ejemplo 2: Integración con Node.js/Express

**Escenario:** API Node.js recibe notificaciones de facturas.

#### 1. Instalar Dependencias

```bash
npm install express body-parser
```

#### 2. Crear Servidor Webhook

**Archivo:** `server.js`

```javascript
const express = require('express');
const bodyParser = require('body-parser');
const crypto = require('crypto');

const app = express();

// Middleware para capturar body raw (necesario para verificar firma)
app.use('/webhook', bodyParser.raw({ type: 'application/json' }));

// Secret del webhook (debe coincidir con el registrado)
const WEBHOOK_SECRET = 'mi-secret-super-seguro-123';

// Endpoint receptor de webhooks
app.post('/webhook', async (req, res) => {
    try {
        // 1. Obtener firma y evento
        const signature = req.headers['x-webhook-signature'];
        const event = req.headers['x-webhook-event'];

        // 2. Verificar firma
        const expectedSignature = crypto
            .createHmac('sha256', WEBHOOK_SECRET)
            .update(req.body)
            .digest('hex');

        if (signature !== expectedSignature) {
            console.error('❌ Firma inválida');
            return res.status(401).json({ error: 'Firma inválida' });
        }

        // 3. Parsear datos
        const data = JSON.parse(req.body);

        console.log(`📩 Webhook recibido: ${event}`);
        console.log(JSON.stringify(data, null, 2));

        // 4. Procesar según evento
        switch (data.event) {
            case 'invoice.accepted':
                await handleInvoiceAccepted(data.data);
                break;

            case 'invoice.rejected':
                await handleInvoiceRejected(data.data);
                break;

            case 'boleta.accepted':
                await handleBoletaAccepted(data.data);
                break;

            default:
                console.log(`⚠️ Evento no manejado: ${data.event}`);
        }

        // 5. Responder éxito
        res.json({ success: true });

    } catch (error) {
        console.error('❌ Error procesando webhook:', error);
        res.status(500).json({ error: error.message });
    }
});

// Handlers para cada evento
async function handleInvoiceAccepted(data) {
    console.log(`✅ Factura aceptada: ${data.numero}`);

    // Actualizar base de datos
    await db.query(
        'UPDATE facturas SET estado = ? WHERE numero = ?',
        ['ACEPTADO', data.numero]
    );

    // Enviar notificación
    await sendNotification({
        title: 'Factura Aceptada',
        message: `La factura ${data.numero} fue aceptada por SUNAT`,
        amount: data.monto
    });
}

async function handleInvoiceRejected(data) {
    console.log(`❌ Factura rechazada: ${data.numero}`);

    await db.query(
        'UPDATE facturas SET estado = ?, error = ? WHERE numero = ?',
        ['RECHAZADO', data.result.error, data.numero]
    );

    // Alertar al equipo
    await sendAlert({
        type: 'error',
        message: `Factura ${data.numero} rechazada por SUNAT`
    });
}

async function handleBoletaAccepted(data) {
    console.log(`✅ Boleta aceptada: ${data.numero}`);
    // Implementar lógica
}

// Iniciar servidor
const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`🚀 Servidor webhook escuchando en puerto ${PORT}`);
});
```

#### 3. Registrar Webhook

```bash
curl -X POST https://api-facturacion.com/api/v1/webhooks \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": 1,
    "name": "API Node.js",
    "url": "https://mi-api.com/webhook",
    "events": ["invoice.accepted", "invoice.rejected", "boleta.accepted"]
  }'
```

---

### Ejemplo 3: Integración con Python/Flask

**Escenario:** Microservicio Python procesa webhooks.

#### 1. Instalar Flask

```bash
pip install flask
```

#### 2. Crear Receptor

**Archivo:** `webhook_receiver.py`

```python
from flask import Flask, request, jsonify
import hmac
import hashlib
import json
import logging

app = Flask(__name__)

# Configurar logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

WEBHOOK_SECRET = b'mi-secret-super-seguro-123'

@app.route('/webhook', methods=['POST'])
def webhook():
    try:
        # 1. Obtener headers
        signature = request.headers.get('X-Webhook-Signature')
        event = request.headers.get('X-Webhook-Event')

        # 2. Verificar firma
        payload = request.get_data()
        expected_signature = hmac.new(
            WEBHOOK_SECRET,
            payload,
            hashlib.sha256
        ).hexdigest()

        if not hmac.compare_digest(signature, expected_signature):
            logger.error('Firma inválida')
            return jsonify({'error': 'Firma inválida'}), 401

        # 3. Parsear datos
        data = request.get_json()

        logger.info(f'Webhook recibido: {event}')
        logger.info(f'Datos: {json.dumps(data, indent=2)}')

        # 4. Procesar según evento
        handlers = {
            'invoice.accepted': handle_invoice_accepted,
            'invoice.rejected': handle_invoice_rejected,
            'boleta.accepted': handle_boleta_accepted,
        }

        handler = handlers.get(data['event'])
        if handler:
            handler(data['data'])
        else:
            logger.warning(f'Evento no manejado: {data["event"]}')

        # 5. Responder éxito
        return jsonify({'success': True}), 200

    except Exception as e:
        logger.error(f'Error procesando webhook: {str(e)}')
        return jsonify({'error': str(e)}), 500

def handle_invoice_accepted(data):
    logger.info(f'✅ Factura aceptada: {data["numero"]}')

    # Actualizar base de datos
    update_invoice_status(data['document_id'], 'ACEPTADO')

    # Enviar email
    send_email_notification(data)

    # Generar reporte
    generate_report(data)

def handle_invoice_rejected(data):
    logger.error(f'❌ Factura rechazada: {data["numero"]}')

    update_invoice_status(data['document_id'], 'RECHAZADO')

    # Alertar equipo
    send_alert_to_team(data)

def handle_boleta_accepted(data):
    logger.info(f'✅ Boleta aceptada: {data["numero"]}')

    update_boleta_status(data['document_id'], 'ACEPTADO')

def update_invoice_status(document_id, status):
    # Implementar actualización en BD
    pass

def send_email_notification(data):
    # Implementar envío de email
    pass

def generate_report(data):
    # Implementar generación de reporte
    pass

def send_alert_to_team(data):
    # Implementar alerta
    pass

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000, debug=True)
```

#### 3. Ejecutar Servidor

```bash
python webhook_receiver.py
```

#### 4. Registrar Webhook

```bash
curl -X POST https://api-facturacion.com/api/v1/webhooks \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": 1,
    "name": "Microservicio Python",
    "url": "https://mi-servicio.com:5000/webhook",
    "events": ["invoice.accepted", "invoice.rejected"]
  }'
```

---

## Monitoreo y Estadísticas

### Dashboard de Webhooks

Puedes crear un dashboard para monitorear el estado de tus webhooks.

#### Consultar Estadísticas

```http
GET /api/v1/webhooks/{id}/statistics
```

**Respuesta:**
```json
{
  "total_deliveries": 1520,
  "successful": 1498,
  "failed": 22,
  "pending": 0,
  "success_rate": 98.55,
  "failure_rate": 1.45,
  "last_triggered_at": "2025-12-10T16:30:00Z",
  "last_status": "success"
}
```

### Monitoreo de Salud

```sql
-- Ver webhooks con alta tasa de fallo
SELECT
    id,
    name,
    url,
    success_count,
    failure_count,
    (failure_count * 100.0 / (success_count + failure_count)) as failure_rate
FROM webhooks
WHERE (success_count + failure_count) > 0
ORDER BY failure_rate DESC
LIMIT 10;
```

### Alertas Recomendadas

Configure alertas cuando:

1. **Failure rate > 5%**
   ```sql
   SELECT * FROM webhooks
   WHERE (failure_count * 100.0 / (success_count + failure_count)) > 5
   AND (success_count + failure_count) > 10;
   ```

2. **Webhook sin actividad en 24 horas**
   ```sql
   SELECT * FROM webhooks
   WHERE active = 1
   AND last_triggered_at < DATE_SUB(NOW(), INTERVAL 24 HOUR);
   ```

3. **Muchos deliveries pendientes**
   ```sql
   SELECT webhook_id, COUNT(*) as pending_count
   FROM webhook_deliveries
   WHERE status = 'pending'
   GROUP BY webhook_id
   HAVING pending_count > 10;
   ```

---

## Solución de Problemas

### Problema 1: Webhook no se dispara

**Síntomas:**
- Envías factura a SUNAT
- Factura es aceptada
- No llega webhook

**Diagnóstico:**

1. Verificar que el webhook está activo:
   ```sql
   SELECT * FROM webhooks WHERE id = 1;
   ```
   Debe tener `active = 1`

2. Verificar que el evento está configurado:
   ```sql
   SELECT events FROM webhooks WHERE id = 1;
   ```
   Debe incluir `"invoice.accepted"` en el array JSON

3. Verificar que el listener está registrado:
   ```php
   // app/Providers/AppServiceProvider.php
   Event::listen(
       DocumentSentToSunat::class,
       SendDocumentNotification::class
   );
   ```

4. Verificar que el evento se dispara:
   Revisar logs:
   ```bash
   tail -f storage/logs/laravel.log | grep "DocumentSentToSunat"
   ```

**Solución:**

Si el evento no se dispara, verificar en el controlador:

```php
// En DocumentService::sendToSunat()
event(new DocumentSentToSunat($document, $documentType, $result));
```

---

### Problema 2: Webhook se crea pero no se envía

**Síntomas:**
- Se crea registro en `webhook_deliveries`
- Estado queda en `pending`
- Nunca llega al destino

**Diagnóstico:**

1. Verificar que el comando de procesamiento se ejecuta:
   ```bash
   php artisan webhooks:process
   ```

2. Verificar configuración de cron:
   ```bash
   crontab -l | grep webhook
   ```

3. Ver logs de entregas:
   ```sql
   SELECT * FROM webhook_deliveries
   WHERE status = 'pending'
   ORDER BY created_at DESC
   LIMIT 10;
   ```

**Solución:**

Configurar cron job:
```bash
crontab -e

# Añadir:
*/5 * * * * cd /ruta/proyecto && php artisan webhooks:process >> /var/log/webhooks-cron.log 2>&1
```

O procesar manualmente:
```bash
php artisan webhooks:process --limit=100
```

---

### Problema 3: Webhook falla con timeout

**Síntomas:**
- Webhook se envía
- Error: "Connection timeout"
- `response_code` es NULL

**Diagnóstico:**

Ver configuración de timeout:
```sql
SELECT id, name, url, timeout FROM webhooks WHERE id = 1;
```

**Solución:**

1. Aumentar timeout:
   ```http
   PUT /api/v1/webhooks/1
   {
     "timeout": 60
   }
   ```

2. Verificar que la URL destino responde:
   ```bash
   curl -X POST https://tu-url.com/webhook \
     -H "Content-Type: application/json" \
     -d '{"test": true}' \
     --max-time 5
   ```

---

### Problema 4: Firma inválida

**Síntomas:**
- Webhook llega al destino
- Tu sistema rechaza con 401
- Error: "Firma inválida"

**Diagnóstico:**

1. Verificar que usas el mismo secret:
   ```sql
   SELECT secret FROM webhooks WHERE id = 1;
   ```

2. Verificar que calculas la firma correctamente:
   ```php
   // Tu sistema debe hacer:
   $expectedSignature = hash_hmac('sha256', $payload, $secret);

   // NO esto:
   $expectedSignature = hash_hmac('sha256', json_decode($payload), $secret);
   ```

**Solución:**

Implementar verificación correcta:

```php
// CORRECTO ✅
$payload = file_get_contents('php://input'); // Raw string
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'];
$expected = hash_hmac('sha256', $payload, $secret);

if (hash_equals($expected, $signature)) {
    // OK
}

// INCORRECTO ❌
$data = json_decode(file_get_contents('php://input'), true);
$expected = hash_hmac('sha256', json_encode($data), $secret);
```

---

### Problema 5: Webhooks duplicados

**Síntomas:**
- Recibes el mismo webhook múltiples veces
- Múltiples registros en `webhook_deliveries`

**Diagnóstico:**

```sql
SELECT event, payload, COUNT(*) as count
FROM webhook_deliveries
WHERE webhook_id = 1
GROUP BY event, payload
HAVING count > 1;
```

**Causas:**

1. Sistema de reintentos ejecutándose múltiples veces
2. Evento disparándose múltiples veces

**Solución:**

Implementar idempotencia en tu receptor:

```php
// Tu sistema debe trackear webhooks ya procesados
$webhookId = $data['data']['document_id'] . '_' . $data['event'];

// Verificar si ya fue procesado
if (wasAlreadyProcessed($webhookId)) {
    // Ya procesado, responder OK sin reprocessar
    http_response_code(200);
    echo json_encode(['success' => true, 'already_processed' => true]);
    exit;
}

// Procesar webhook
processWebhook($data);

// Marcar como procesado
markAsProcessed($webhookId);
```

---

## Conclusión

### Resumen del Sistema

El sistema de webhooks proporciona:

✅ **Notificaciones en tiempo real** cuando ocurren eventos
✅ **Seguridad mediante firma HMAC** SHA256
✅ **Sistema de reintentos automático** con exponential backoff
✅ **Historial completo** de todas las entregas
✅ **API REST completa** para gestión de webhooks
✅ **Múltiples eventos** disponibles (facturas, boletas, notas)
✅ **Monitoreo y estadísticas** integrados

### Casos de Uso Principales

1. **Integración con ERP/CRM**
2. **Notificaciones a aplicaciones móviles**
3. **Sincronización con sistemas contables**
4. **Automatización de procesos**
5. **Monitoreo y alertas en tiempo real**

### Próximos Pasos

1. Registra tu primer webhook
2. Prueba con una de las alternativas gratuitas (RequestBin, endpoint local)
3. Implementa el receptor en tu sistema
4. Configura cron job para procesar reintentos
5. Monitorea estadísticas y ajusta configuración

---

**Documentación creada:** 10 de Diciembre 2025
**Versión:** 1.0
**Sistema:** API de Facturación Electrónica SUNAT Perú
**Framework:** Laravel 12

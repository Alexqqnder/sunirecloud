# 🚀 TESTING RÁPIDO DE WEBHOOKS CON WEBHOOK.SITE

Esta es la forma **MÁS RÁPIDA** para probar webhooks sin necesidad de programar ni configurar nada.

---

## ⚡ Paso a Paso (5 minutos)

### 1️⃣ Obtener una URL de prueba

1. Abre tu navegador y ve a: **https://webhook.site**
2. Automáticamente te asignará una URL única, por ejemplo:
   ```
   https://webhook.site/12ab34cd-56ef-78gh-90ij-1234567890ab
   ```
3. **¡Copia esta URL!** La usaremos en el siguiente paso

### 2️⃣ Crear el Webhook en tu API

Usa Postman o cURL para crear un webhook con la URL de webhook.site:

```http
POST {{base_url}}/api/v1/webhooks
Content-Type: application/json
Authorization: Bearer {{token}}

{
  "company_id": 1,
  "name": "Testing con Webhook.site",
  "url": "https://webhook.site/12ab34cd-56ef-78gh-90ij-1234567890ab",
  "method": "POST",
  "events": [
    "invoice.accepted",
    "invoice.rejected",
    "boleta.accepted"
  ]
}
```

### 3️⃣ Probar el Webhook

Tienes dos opciones:

#### Opción A: Enviar un Webhook de Prueba

```http
POST {{base_url}}/api/v1/webhooks/1/test
Authorization: Bearer {{token}}
```

#### Opción B: Generar un Evento Real

Envía una factura real a SUNAT:

```http
POST {{base_url}}/api/v1/invoices/123/send-to-sunat
Authorization: Bearer {{token}}
```

### 4️⃣ Ver los Resultados en Webhook.site

1. Vuelve a la pestaña de **webhook.site** en tu navegador
2. ¡Verás aparecer la petición en tiempo real!
3. Podrás ver:
   - ✅ Headers recibidos (incluido `X-Webhook-Signature`)
   - ✅ Payload completo en JSON
   - ✅ Timestamp de recepción
   - ✅ Código de respuesta HTTP

---

## 📸 Ejemplo de lo que Verás

### Headers Recibidos:
```
POST / HTTP/1.1
Host: webhook.site
Content-Type: application/json
User-Agent: FacturacionElectronica/1.0
X-Webhook-Signature: a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6...
X-Webhook-Event: invoice.accepted
Content-Length: 456
```

### Payload (Body):
```json
{
  "event": "invoice.accepted",
  "timestamp": "2025-12-23T10:30:00.000Z",
  "data": {
    "document_id": 123,
    "numero": "F001-00000123",
    "company_id": 1,
    "client": {
      "tipo_documento": "6",
      "numero_documento": "20123456789",
      "razon_social": "EMPRESA EJEMPLO SAC"
    },
    "monto": 1500.00,
    "moneda": "PEN",
    "estado_sunat": "ACEPTADO"
  }
}
```

---

## 🔍 Características Útiles de Webhook.site

### 1. **Custom Response (Personalizar Respuesta)**

Puedes configurar qué responde webhook.site:

- Click en "Edit" en la parte superior
- Cambia el "Status Code" (200, 400, 500, etc.)
- Cambia el "Response Body"
- Útil para probar cómo tu sistema maneja errores

**Ejemplo: Simular un error 500**
```
Status Code: 500
Content-Type: application/json
Body: {"error": "Internal Server Error"}
```

Esto hará que tu webhook se reintente automáticamente según la configuración de `max_retries`.

### 2. **DNT (Do Not Track)**

Activa "DNT" para que webhook.site no guarde los datos (útil para información sensible).

### 3. **Exportar Requests**

Puedes exportar todas las peticiones recibidas en formato JSON.

### 4. **Copy as cURL**

Click derecho en cualquier request → "Copy as cURL" para replicarlo fácilmente.

---

## 🧪 Casos de Prueba Recomendados

### Prueba 1: Webhook Exitoso (200 OK)
1. Deja la configuración por defecto (Status 200)
2. Envía un webhook de prueba
3. Verifica que aparece como "success" en el historial:
   ```http
   GET {{base_url}}/api/v1/webhooks/1/deliveries
   ```

### Prueba 2: Webhook con Error (500)
1. Configura webhook.site para responder con Status 500
2. Envía un webhook de prueba
3. Verifica que:
   - El webhook queda en estado "pending"
   - Se programa un reintento en `next_retry_at`
   - Aparece en el historial con `attempts: 1`

### Prueba 3: Validar Firma HMAC
1. Copia el header `X-Webhook-Signature` de webhook.site
2. Copia el payload completo
3. En tu terminal, ejecuta:
   ```php
   php -r "echo hash_hmac('sha256', '{\"event\":\"webhook.test\"...}', 'tu_secret');"
   ```
4. Compara que coincidan

### Prueba 4: Timeout
1. Configura webhook.site con un delay de 35 segundos
2. Configura tu webhook con timeout de 30 segundos:
   ```json
   { "timeout": 30 }
   ```
3. Envía un webhook de prueba
4. Debería fallar por timeout

---

## 🎯 Alternativas a Webhook.site

Si webhook.site no te funciona, puedes usar:

### 1. **RequestBin** (https://requestbin.com)
- Similar a webhook.site
- Gratis con cuenta
- Más opciones de personalización

### 2. **Beeceptor** (https://beeceptor.com)
- Permite crear mocks más complejos
- Soporta regex para rutas
- Plan gratuito limitado

### 3. **Pipedream** (https://pipedream.com)
- Permite ejecutar código cuando llega un webhook
- Integración con múltiples servicios
- Plan gratuito generoso

### 4. **ngrok + Servidor Local** (Más avanzado)
```bash
# Terminal 1: Iniciar servidor local
php -S localhost:8080 webhook-receiver.php

# Terminal 2: Exponer con ngrok
ngrok http 8080

# Copiar la URL HTTPS generada
# Ejemplo: https://abc123.ngrok.io
```

---

## 📊 Verificar el Comportamiento de Reintentos

1. **Configura webhook.site para responder con error 500**

2. **Crea un webhook con configuración de reintentos:**
   ```json
   {
     "max_retries": 3,
     "retry_delay": 60
   }
   ```

3. **Envía un webhook de prueba**

4. **Verifica el historial de entregas:**
   ```http
   GET {{base_url}}/api/v1/webhooks/1/deliveries
   ```

5. **Deberías ver:**
   ```json
   {
     "id": 1,
     "status": "pending",
     "attempts": 1,
     "response_code": 500,
     "next_retry_at": "2025-12-23T10:35:00.000Z"  // +60 segundos
   }
   ```

6. **Ejecuta manualmente el procesador de reintentos:**
   ```bash
   php artisan webhooks:process
   ```

7. **Vuelve a verificar el historial:**
   - `attempts` debería aumentar a 2
   - `next_retry_at` se recalcula para el siguiente intento
   - Si falla 3 veces, `status` cambia a "failed"

---

## 🛠️ Debugging con Webhook.site

### Ver Headers Personalizados

Si configuraste headers personalizados:
```json
{
  "headers": {
    "X-API-Key": "mi-key-123",
    "X-System": "ERP"
  }
}
```

En webhook.site verás:
```
X-API-Key: mi-key-123
X-System: ERP
X-Webhook-Signature: a1b2c3...
X-Webhook-Event: invoice.accepted
```

### Comparar Payloads

Webhook.site te permite comparar diferentes peticiones lado a lado para detectar diferencias.

### Buscar en el Historial

Usa el buscador para filtrar por:
- Evento específico
- Timestamp
- Contenido del payload

---

## ✅ Checklist de Testing

- [ ] Webhook de prueba se envía correctamente
- [ ] Headers personalizados se incluyen
- [ ] Firma HMAC es válida
- [ ] Payload tiene la estructura esperada
- [ ] Eventos configurados se disparan
- [ ] Webhook responde en menos del timeout
- [ ] Reintentos funcionan ante errores 5xx
- [ ] Webhook se marca como "failed" tras max_retries
- [ ] Estadísticas se actualizan correctamente
- [ ] Logs se generan en `storage/logs/audit.log`

---

## 🎓 Próximos Pasos

Una vez que hayas probado con webhook.site, implementa tu propio receptor usando:

1. **PHP**: `ejemplos/webhook-receiver.php`
2. **Node.js**: `ejemplos/webhook-receiver-nodejs.js`
3. **Ejemplos Postman**: `ejemplos/WEBHOOKS-EJEMPLOS-POSTMAN.md`

---

## 💡 Tips Finales

1. **Guarda la URL de webhook.site** - Es válida por 7 días
2. **Activa "DNT"** si vas a enviar datos reales de clientes
3. **Usa el botón "Clear"** para limpiar el historial entre pruebas
4. **Configura "Auto-refresh"** para ver las peticiones en tiempo real
5. **Descarga los requests** antes de que expiren (7 días)

---

¡Listo! Con esto puedes probar webhooks en **menos de 5 minutos** sin escribir una línea de código. 🎉

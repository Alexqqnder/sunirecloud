# 🔄 Envío a SUNAT: Síncrono vs Asíncrono

Esta documentación explica las diferencias entre los dos modos de envío de documentos electrónicos a SUNAT disponibles en la API.

---

## 📋 Resumen Comparativo

| Característica | Síncrono | Asíncrono |
|----------------|----------|-----------|
| **Endpoint** | `/send-sunat` | `/send-sunat-async` |
| **Respuesta** | Espera resultado de SUNAT | Inmediata ("EN_COLA") |
| **Bloquea sistema** | ✅ Sí | ❌ No |
| **Resultado SUNAT** | Al momento | Consultar después |
| **Volumen alto** | ❌ No recomendado | ✅ Ideal |
| **Timeout SUNAT** | ⚠️ Problema | ✅ Manejado |
| **Reintentos** | Manual | Automático (3 intentos) |
| **Requiere Worker** | ❌ No | ✅ Sí |

---

## 📊 Flujo Visual

### Síncrono
```
Cliente ──► API ──► SUNAT ──► Respuesta
              │
         Espera 5-30 seg
```

### Asíncrono
```
Cliente ──► API ──► Cola ──► Respuesta inmediata ("EN_COLA")
                      │
                      ▼
               Worker (segundo plano)
                      │
                      ▼
                    SUNAT
                      │
                      ▼
              Actualiza estado en BD
```

---

## 🔄 Estados del Documento

| Estado | Descripción | Color |
|--------|-------------|-------|
| `PENDIENTE` | Documento creado, no enviado | 🟡 Amarillo |
| `EN_COLA` | En cola de envío (solo asíncrono) | 🔵 Azul |
| `ACEPTADO` | SUNAT aceptó el documento | 🟢 Verde |
| `RECHAZADO` | SUNAT rechazó el documento | 🔴 Rojo |
| `ERROR` | Error de conexión o timeout | ⚫ Negro |

### Flujo de Estados

```
PENDIENTE ──► EN_COLA ──► ACEPTADO
                │
                ├──► RECHAZADO
                │
                └──► ERROR (reintenta automáticamente hasta 3 veces)
```

---

## 🧪 EJEMPLOS REALES DE USO

### Flujo Completo

```
1. Crear documento → 2. Enviar a SUNAT → 3. Verificar estado
```

---

## PASO 1: Crear una Factura

```http
POST {{base_url}}/api/v1/invoices
Authorization: Bearer {token}
Content-Type: application/json
```

```json
{
    "company_id": 1,
    "branch_id": 1,
    "client_id": 1,
    "tipo_operacion": "0101",
    "tipo_documento": "01",
    "serie": "F001",
    "correlativo": 1,
    "fecha_emision": "2025-12-12",
    "fecha_vencimiento": "2025-12-27",
    "moneda": "PEN",
    "forma_pago_tipo": "Contado",
    "mto_oper_gravadas": 100.00,
    "mto_igv": 18.00,
    "total_impuestos": 18.00,
    "valor_venta": 100.00,
    "sub_total": 118.00,
    "mto_imp_venta": 118.00,
    "detalles": [
        {
            "cod_producto": "PROD001",
            "unidad": "NIU",
            "descripcion": "Producto de prueba",
            "cantidad": 1,
            "mto_valor_unitario": 100.00,
            "mto_valor_venta": 100.00,
            "mto_base_igv": 100.00,
            "porcentaje_igv": 18,
            "igv": 18.00,
            "tip_afe_igv": "10",
            "total_impuestos": 18.00,
            "mto_precio_unitario": 118.00
        }
    ],
    "leyendas": [
        {
            "code": "1000",
            "value": "CIENTO DIECIOCHO CON 00/100 SOLES"
        }
    ]
}
```

**Respuesta:**
```json
{
    "success": true,
    "data": {
        "id": 123,
        "numero_completo": "F001-00000001",
        "estado_sunat": "PENDIENTE"
    },
    "message": "Factura creada correctamente"
}
```

---

## PASO 2A: Envío SÍNCRONO

### Endpoint
```http
POST {{base_url}}/api/v1/invoices/{id}/send-sunat
Authorization: Bearer {token}
```

### Ejemplo
```http
POST {{base_url}}/api/v1/invoices/123/send-sunat
Authorization: Bearer {token}
```

### Respuesta Exitosa (ACEPTADO)
```json
{
    "success": true,
    "data": {
        "id": 123,
        "numero_completo": "F001-00000001",
        "estado_sunat": "ACEPTADO",
        "codigo_hash": "abc123def456...",
        "respuesta_sunat": {
            "codigo": "0",
            "descripcion": "La Factura numero F001-00000001 ha sido aceptada"
        },
        "xml_path": "empresas/20123456789/facturas/xml/F001-00000001.xml",
        "cdr_path": "empresas/20123456789/facturas/cdr/R-F001-00000001.zip"
    },
    "message": "Factura enviada y aceptada por SUNAT"
}
```

### Respuesta Rechazada
```json
{
    "success": false,
    "message": "SUNAT rechazó el documento: El RUC del receptor no existe",
    "error": {
        "code": "2017",
        "message": "El RUC del receptor no existe"
    }
}
```

### Respuesta Error de Conexión
```json
{
    "success": false,
    "message": "Error de conexión con SUNAT. Intente nuevamente.",
    "error": {
        "code": "CONNECTION_ERROR",
        "message": "Timeout al conectar con el servidor de SUNAT"
    }
}
```

---

## PASO 2B: Envío ASÍNCRONO

### Endpoint
```http
POST {{base_url}}/api/v1/invoices/{id}/send-sunat-async
Authorization: Bearer {token}
```

### Ejemplo
```http
POST {{base_url}}/api/v1/invoices/123/send-sunat-async
Authorization: Bearer {token}
```

### Respuesta Inmediata
```json
{
    "success": true,
    "data": {
        "id": 123,
        "numero_completo": "F001-00000001",
        "estado_sunat": "EN_COLA"
    },
    "message": "Factura agregada a la cola de envío. Recibirá una notificación cuando se complete el proceso."
}
```

> **Nota:** El código de respuesta HTTP es `202 Accepted`, indicando que la solicitud fue aceptada para procesamiento posterior.

---

## PASO 3: Ejecutar Worker (Solo para Asíncrono)

Para que el envío asíncrono funcione, debes ejecutar el worker de colas:

### Comando Básico
```bash
php artisan queue:work --queue=sunat-send
```

### Con Más Detalles (Debug)
```bash
php artisan queue:work --queue=sunat-send -vvv
```

### Salida Esperada
```
[2025-12-12 10:30:00][Job ID: 1] Processing: App\Jobs\SendDocumentToSunat
[2025-12-12 10:30:05][Job ID: 1] Processed:  App\Jobs\SendDocumentToSunat
```

### Configuración del Job

| Parámetro | Valor | Descripción |
|-----------|-------|-------------|
| `tries` | 3 | Número máximo de reintentos |
| `backoff` | [30, 60, 120] | Segundos de espera entre reintentos |
| `timeout` | 300 | Timeout del job (5 minutos) |
| `queue` | sunat-send | Nombre de la cola |

---

## PASO 4: Verificar Estado (Para Asíncrono)

### Endpoint
```http
GET {{base_url}}/api/v1/invoices/{id}
Authorization: Bearer {token}
```

### Ejemplo
```http
GET {{base_url}}/api/v1/invoices/123
Authorization: Bearer {token}
```

### Respuesta (Estado EN_COLA - Aún procesando)
```json
{
    "success": true,
    "data": {
        "id": 123,
        "numero_completo": "F001-00000001",
        "estado_sunat": "EN_COLA",
        "respuesta_sunat": null
    }
}
```

### Respuesta (Estado ACEPTADO - Ya procesado)
```json
{
    "success": true,
    "data": {
        "id": 123,
        "numero_completo": "F001-00000001",
        "estado_sunat": "ACEPTADO",
        "codigo_hash": "abc123def456...",
        "respuesta_sunat": {
            "codigo": "0",
            "descripcion": "La Factura numero F001-00000001 ha sido aceptada"
        },
        "xml_path": "empresas/20123456789/facturas/xml/F001-00000001.xml",
        "cdr_path": "empresas/20123456789/facturas/cdr/R-F001-00000001.zip"
    }
}
```

### Respuesta (Estado ERROR - Falló después de 3 intentos)
```json
{
    "success": true,
    "data": {
        "id": 123,
        "numero_completo": "F001-00000001",
        "estado_sunat": "ERROR",
        "respuesta_sunat": {
            "error": "Connection timeout",
            "code": "JOB_FAILED",
            "attempts": 3
        }
    }
}
```

---

## 📡 Endpoints Disponibles por Tipo de Documento

### Facturas

| Método | Endpoint | Tipo | Descripción |
|--------|----------|------|-------------|
| `POST` | `/v1/invoices/{id}/send-sunat` | Síncrono | Enviar y esperar respuesta |
| `POST` | `/v1/invoices/{id}/send-sunat-async` | Asíncrono | Enviar a cola |

### Boletas

| Método | Endpoint | Tipo | Descripción |
|--------|----------|------|-------------|
| `POST` | `/v1/boletas/{id}/send-sunat` | Síncrono | Enviar y esperar respuesta |
| `POST` | `/v1/boletas/summary/{id}/send-sunat` | Síncrono | Enviar resumen diario |

### Notas de Crédito

| Método | Endpoint | Tipo | Descripción |
|--------|----------|------|-------------|
| `POST` | `/v1/credit-notes/{id}/send-sunat` | Síncrono | Enviar y esperar respuesta |

### Notas de Débito

| Método | Endpoint | Tipo | Descripción |
|--------|----------|------|-------------|
| `POST` | `/v1/debit-notes/{id}/send-sunat` | Síncrono | Enviar y esperar respuesta |

### Guías de Remisión

| Método | Endpoint | Tipo | Descripción |
|--------|----------|------|-------------|
| `POST` | `/v1/dispatch-guides/{id}/send-sunat` | Síncrono | Enviar y esperar respuesta |

### Retenciones

| Método | Endpoint | Tipo | Descripción |
|--------|----------|------|-------------|
| `POST` | `/v1/retentions/{id}/send-sunat` | Síncrono | Enviar y esperar respuesta |

### Comunicaciones de Baja

| Método | Endpoint | Tipo | Descripción |
|--------|----------|------|-------------|
| `POST` | `/v1/voided-documents/{id}/send-sunat` | Síncrono | Enviar y esperar respuesta |

### Resúmenes Diarios

| Método | Endpoint | Tipo | Descripción |
|--------|----------|------|-------------|
| `POST` | `/v1/daily-summaries/{id}/send-sunat` | Síncrono | Enviar y esperar respuesta |

---

## 🎯 ¿Cuándo Usar Cada Uno?

### ✅ Usa SÍNCRONO cuando:

| Escenario | Razón |
|-----------|-------|
| Pocas facturas (1-10 por minuto) | No hay riesgo de saturación |
| Punto de venta (POS) | El cliente espera su comprobante impreso |
| Necesitas el PDF/CDR al momento | El resultado es inmediato |
| Sistema simple sin colas | Menor complejidad de implementación |
| Facturación manual | Usuario espera confirmación |

### ✅ Usa ASÍNCRONO cuando:

| Escenario | Razón |
|-----------|-------|
| Facturación masiva (+100 docs/hora) | Alto volumen de documentos |
| E-commerce con mucho tráfico | No bloquear el proceso de checkout |
| Importación de facturas en lote | Procesar muchas a la vez |
| Sistemas críticos 24/7 | Mayor estabilidad y tolerancia a fallos |
| Integración con ERP | Procesamiento en segundo plano |
| SUNAT está lento | Evitar timeouts |

---

## 💡 Ejemplos Prácticos de Uso

### Ejemplo 1: Punto de Venta (POS)

**Recomendación:** SÍNCRONO ✅

```
Cajero cobra → Genera factura → POST /send-sunat → Espera 5 seg → Imprime comprobante
```

**¿Por qué?** El cajero y el cliente esperan el comprobante físico. Necesitan saber inmediatamente si fue aceptado para poder entregarlo.

### Ejemplo 2: Tienda Online (E-commerce)

**Recomendación:** ASÍNCRONO ✅

```
Cliente paga → Genera factura → POST /send-sunat-async → Muestra "Pedido confirmado"
                                         │
                                         ▼
                          (Worker procesa en segundo plano)
                                         │
                                         ▼
                          Envía email con factura PDF cuando esté lista
```

**¿Por qué?** El cliente no quiere esperar 30 segundos en la pantalla de pago. Es mejor confirmar el pedido y enviar la factura por email.

### Ejemplo 3: Facturación de Fin de Mes

**Recomendación:** ASÍNCRONO ✅

```
Ejecutar proceso → 500 facturas a la cola → Workers procesan en paralelo
                                                    │
                                                    ▼
                                          Dashboard muestra progreso
                                                    │
                                                    ▼
                                          Notifica cuando termine
```

**¿Por qué?** Imposible enviar 500 facturas síncronamente sin bloquear el sistema por horas.

### Ejemplo 4: API Pública para Terceros

**Recomendación:** ASÍNCRONO ✅

```
Cliente API envía factura → POST /send-sunat-async → Responde 202 Accepted
                                     │
                                     ▼
                          Cliente consulta estado con GET /invoices/{id}
                          o recibe webhook cuando esté lista
```

**¿Por qué?** No puedes mantener una conexión HTTP abierta por 30 segundos esperando a SUNAT.

---

## ⚙️ Configuración del Sistema

### 1. Verificar Configuración de Colas

En tu archivo `.env`:
```env
QUEUE_CONNECTION=database
```

### 2. Verificar que la Tabla de Jobs Existe

```bash
php artisan migrate:status | grep job
```

Debe mostrar:
```
create_jobs_table ................................. Ran
```

Si no existe, ejecutar:
```bash
php artisan queue:table
php artisan migrate
```

### 3. Ejecutar Worker en Desarrollo

```bash
# Terminal dedicada para el worker
php artisan queue:work --queue=sunat-send
```

### 4. Ejecutar Worker en Producción (Supervisor)

Crear archivo `/etc/supervisor/conf.d/sunat-worker.conf`:

```ini
[program:sunat-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /ruta/a/tu/proyecto/artisan queue:work --queue=sunat-send --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/supervisor/sunat-worker.log
stopwaitsecs=3600
```

Comandos de Supervisor:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start sunat-worker:*
```

---

## 🔔 Webhooks (Opcional)

Puedes configurar webhooks para recibir notificaciones cuando un documento sea procesado:

### Configurar Webhook

```http
POST /api/v1/webhooks
Authorization: Bearer {token}
Content-Type: application/json

{
    "url": "https://tu-sistema.com/webhook/sunat",
    "events": [
        "invoice.accepted",
        "invoice.rejected",
        "boleta.accepted",
        "boleta.rejected"
    ],
    "secret": "tu_secret_key_seguro"
}
```

### Payload Recibido

```json
{
    "event": "invoice.accepted",
    "timestamp": "2025-12-12T10:35:00Z",
    "signature": "sha256=abc123...",
    "data": {
        "document_type": "invoice",
        "id": 123,
        "numero_completo": "F001-00000001",
        "estado_sunat": "ACEPTADO",
        "respuesta_sunat": {
            "codigo": "0",
            "descripcion": "La Factura numero F001-00000001 ha sido aceptada"
        }
    }
}
```

---

## ❓ Preguntas Frecuentes

### ¿Puedo mezclar síncrono y asíncrono?

**Sí.** Puedes usar síncrono para tu POS y asíncrono para facturación masiva en el mismo sistema.

### ¿Qué pasa si SUNAT está caído con envío asíncrono?

El job se reintentará automáticamente:
- 1er reintento: después de 30 segundos
- 2do reintento: después de 60 segundos
- 3er reintento: después de 120 segundos

Si falla después de 3 intentos, el estado cambia a `ERROR`.

### ¿Cómo sé si mi factura asíncrona fue aceptada?

Tres opciones:
1. **Polling:** Consultar `GET /v1/invoices/{id}` periódicamente
2. **Webhook:** Recibir notificación automática
3. **Dashboard:** Consultar `GET /v1/dashboard/requires-resend` para ver errores

### ¿El envío asíncrono es más lento?

**No.** De hecho puede ser más rápido porque:
- Procesa en paralelo (múltiples workers)
- No bloquea tu sistema
- Reintentos automáticos sin intervención manual

### ¿Qué pasa si el worker se cae?

Los jobs quedan en la tabla `jobs` de la base de datos. Cuando el worker vuelva a iniciar, continuará procesándolos.

### ¿Puedo ver los jobs pendientes?

```bash
# Ver jobs pendientes
php artisan queue:monitor sunat-send

# Ver jobs fallidos
php artisan queue:failed
```

---

## 🔍 Monitoreo y Debug

### Ver Logs del Worker

```bash
tail -f storage/logs/laravel.log | grep -i sunat
```

### Ver Jobs en la Base de Datos

```sql
-- Jobs pendientes
SELECT * FROM jobs WHERE queue = 'sunat-send';

-- Jobs fallidos
SELECT * FROM failed_jobs ORDER BY failed_at DESC;
```

### Reintentar Jobs Fallidos

```bash
# Reintentar todos los jobs fallidos
php artisan queue:retry all

# Reintentar un job específico
php artisan queue:retry 5
```

### Limpiar Jobs Fallidos

```bash
php artisan queue:flush
```

---

## 📚 Referencias

- [Documentación SUNAT - Facturación Electrónica](https://cpe.sunat.gob.pe/)
- [Laravel Queues](https://laravel.com/docs/queues)
- [Supervisor Configuration](http://supervisord.org/)

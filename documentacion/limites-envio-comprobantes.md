# Límites de Envío de Comprobantes Electrónicos a SUNAT

## Índice
1. [Resumen Ejecutivo](#resumen-ejecutivo)
2. [Límites Configurados en la API](#límites-configurados-en-la-api)
3. [Límites y Restricciones de SUNAT](#límites-y-restricciones-de-sunat)
4. [Cálculo de Comprobantes por Segundo](#cálculo-de-comprobantes-por-segundo)
5. [Recomendaciones de Implementación](#recomendaciones-de-implementación)
6. [Estrategias de Optimización](#estrategias-de-optimización)
7. [Monitoreo y Manejo de Errores](#monitoreo-y-manejo-de-errores)

---

## Resumen Ejecutivo

### ⚡ Respuesta Rápida: ¿Cuántos comprobantes por segundo?

**Configuración actual de la API:**
- **10 comprobantes por minuto** = **0.16 comprobantes por segundo**
- **1 comprobante cada 6 segundos** (aproximadamente)

**Cálculos por período:**
```
Por segundo:  0.16 comprobantes
Por minuto:   10 comprobantes
Por hora:     600 comprobantes
Por día:      14,400 comprobantes
Por mes:      432,000 comprobantes
```

---

## Límites Configurados en la API

### 1. Rate Limiting de Envío a SUNAT

**Archivo:** `app/Providers/AppServiceProvider.php` (Línea 79-89)

```php
RateLimiter::for('sunat-send', function (Request $request) {
    return Limit::perMinute(10)
        ->by($request->user()?->id ?: $request->ip())
        ->response(function (Request $request, array $headers) {
            return response()->json([
                'success' => false,
                'message' => 'Demasiados envíos a SUNAT. Límite: 10 por minuto. Por favor espere.',
                'retry_after' => $headers['Retry-After'] ?? 60
            ], 429);
        });
});
```

**Características:**
- ✅ Límite: **10 envíos por minuto** por usuario o IP
- ✅ Respuesta HTTP 429 (Too Many Requests) al exceder límite
- ✅ Header `Retry-After` indica cuándo reintentar
- ✅ Límite individual por usuario autenticado (más justo)

### 2. Rate Limiting General de API

**Archivo:** `app/Providers/AppServiceProvider.php` (Línea 92-102)

```php
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(120)
        ->by($request->user()?->id ?: $request->ip());
});
```

**Características:**
- ✅ Límite: **120 peticiones por minuto** (todas las rutas API)
- ✅ Incluye creación, consultas, descargas, etc.

### 3. Rate Limiting de Consultas CPE

**Archivo:** `app/Providers/AppServiceProvider.php` (Línea 105-115)

```php
RateLimiter::for('cpe-consulta', function (Request $request) {
    return Limit::perMinute(30)
        ->by($request->user()?->id ?: $request->ip());
});
```

**Características:**
- ✅ Límite: **30 consultas por minuto** para verificar estado de comprobantes

### 4. Endpoints Afectados

Los siguientes endpoints tienen el middleware `throttle:sunat-send`:

#### Facturas
```http
POST /api/v1/invoices/{id}/send-sunat
POST /api/v1/invoices/{id}/send-sunat-async
```

#### Boletas
```http
POST /api/v1/boletas/{id}/send-sunat
POST /api/v1/boletas/summary/{id}/send-sunat
```

#### Notas de Crédito
```http
POST /api/v1/credit-notes/{id}/send-sunat
```

#### Notas de Débito
```http
POST /api/v1/debit-notes/{id}/send-sunat
```

---

## Límites y Restricciones de SUNAT

### 1. Límites Documentados

Según la documentación oficial de SUNAT, **NO existen límites técnicos publicados** sobre:
- ❌ Requests por segundo a los webservices
- ❌ Concurrencia máxima de conexiones
- ❌ Throttling o rate limiting oficial

### 2. Restricciones Temporales

Lo que SÍ está regulado por SUNAT:

#### ⏰ Plazo Máximo de Envío
**Normativa:** Resolución de Superintendencia N° 097-2012/SUNAT

- Los comprobantes electrónicos deben enviarse desde la **fecha de emisión** hasta un **máximo de 3 días calendario** del día siguiente a la fecha de emisión.

**Ejemplo:**
```
Fecha de emisión:     10 de Diciembre 2025 (cualquier hora)
Día siguiente:        11 de Diciembre 2025
Plazo máximo:         14 de Diciembre 2025 (23:59:59)
```

#### 📋 Tipos de Documento y Envío

| Tipo de Documento | Método de Envío | Plazo |
|-------------------|-----------------|-------|
| Facturas | Envío inmediato individual | Máximo 3 días calendario |
| Boletas | Resumen diario (RC) | Máximo 3 días calendario |
| Notas de Crédito | Envío inmediato individual | Máximo 3 días calendario |
| Notas de Débito | Envío inmediato individual | Máximo 3 días calendario |
| Comunicación de Baja | Resumen (RA) | Máximo 7 días calendario |

### 3. Requisitos de OSE (Operadores de Servicios Electrónicos)

Si estás actuando como OSE:
- ✅ Disponibilidad mínima: **99.96% anual**
- ✅ Envío de CDR a SUNAT: **Máximo 1 hora** después de la validación
- ✅ Almacenamiento: Mínimo **5 años**

---

## Cálculo de Comprobantes por Segundo

### Escenario 1: Límite Actual (10/minuto)

```
Tasa actual: 10 comprobantes / 60 segundos = 0.166 comprobantes/segundo
Intervalo entre envíos: 60s / 10 = 6 segundos
```

**Volumen máximo:**
```
Por minuto:  10 comprobantes
Por hora:    600 comprobantes
Por día:     14,400 comprobantes
Por mes:     432,000 comprobantes
Por año:     5,256,000 comprobantes
```

### Escenario 2: Límite Aumentado (30/minuto)

Si aumentaras el límite a 30 por minuto:

```
Tasa: 30 comprobantes / 60 segundos = 0.5 comprobantes/segundo
Intervalo entre envíos: 60s / 30 = 2 segundos
```

**Volumen máximo:**
```
Por minuto:  30 comprobantes
Por hora:    1,800 comprobantes
Por día:     43,200 comprobantes
Por mes:     1,296,000 comprobantes
Por año:     15,768,000 comprobantes
```

### Escenario 3: Límite Agresivo (60/minuto = 1/segundo)

```
Tasa: 60 comprobantes / 60 segundos = 1 comprobante/segundo
Intervalo entre envíos: 1 segundo
```

**Volumen máximo:**
```
Por minuto:  60 comprobantes
Por hora:    3,600 comprobantes
Por día:     86,400 comprobantes
Por mes:     2,592,000 comprobantes
Por año:     31,536,000 comprobantes
```

⚠️ **ADVERTENCIA:** Este límite es muy agresivo y podría causar problemas con SUNAT si sus servidores tienen límites no documentados.

---

## Recomendaciones de Implementación

### 1. ✅ Límite Conservador (Recomendado para Producción)

```php
// 10-20 comprobantes por minuto
RateLimiter::for('sunat-send', function (Request $request) {
    return Limit::perMinute(15)  // Aumentado ligeramente
        ->by($request->user()?->id ?: $request->ip());
});
```

**Ventajas:**
- ✅ Seguro y estable
- ✅ No sobrecarga servidores de SUNAT
- ✅ Maneja errores con gracia
- ✅ Cumple normativas sin problemas

**Desventajas:**
- ❌ Más lento en volúmenes altos

### 2. ⚡ Límite Moderado (Balance)

```php
// 30-40 comprobantes por minuto
RateLimiter::for('sunat-send', function (Request $request) {
    return Limit::perMinute(30)
        ->by($request->user()?->id ?: $request->ip());
});
```

**Ventajas:**
- ✅ Buen balance velocidad/estabilidad
- ✅ Maneja volúmenes medianos bien
- ✅ Suficiente para la mayoría de empresas

**Desventajas:**
- ⚠️ Requiere monitoreo de errores SUNAT

### 3. 🚀 Límite Agresivo (Alto Volumen)

```php
// 60+ comprobantes por minuto
RateLimiter::for('sunat-send', function (Request $request) {
    return Limit::perMinute(60)
        ->by($request->user()?->id ?: $request->ip());
});
```

**Ventajas:**
- ✅ Máxima velocidad de procesamiento
- ✅ Necesario para alto volumen (retail, e-commerce)

**Desventajas:**
- ❌ Mayor riesgo de errores de SUNAT
- ❌ Requiere retry logic robusto
- ❌ Posible bloqueo temporal por SUNAT

---

## Estrategias de Optimización

### 1. 🔄 Envío Asíncrono con Colas

**Implementación:**
```php
// Ya implementado en la API
POST /api/v1/invoices/{id}/send-sunat-async
```

**Ventajas:**
- ✅ No bloquea al usuario
- ✅ Procesamiento en background con Laravel Queues
- ✅ Reintentos automáticos en caso de fallo
- ✅ Escalable horizontalmente

**Configuración recomendada:**
```bash
# Supervisor o PM2 para workers
php artisan queue:work --queue=sunat --tries=3 --timeout=90
```

### 2. 📦 Envío por Lotes (Batch)

Para boletas, usa resúmenes diarios:

```http
POST /api/v1/boletas/create-daily-summary
POST /api/v1/boletas/create-all-pending-summaries
```

**Ventajas:**
- ✅ Un resumen puede incluir **cientos de boletas**
- ✅ Solo cuenta como **1 envío** al rate limiter
- ✅ Más eficiente para alto volumen

### 3. ⏰ Distribución de Carga Temporal

**Evita picos de tráfico:**
```
09:00 - 11:00  ➜  Bajo volumen (oficinas abriendo)
11:00 - 14:00  ➜  Alto volumen (operaciones comerciales)
14:00 - 17:00  ➜  Medio volumen
17:00 - 19:00  ➜  Alto volumen (cierre de día)
19:00 - 09:00  ➜  Procesar cola pendiente
```

**Implementación:**
```php
// Programar envíos en horarios de bajo tráfico
$schedule->command('sunat:send-pending')
    ->hourlyAt(15)  // A las XX:15 de cada hora
    ->between('22:00', '07:00');  // Horario nocturno
```

### 4. 🎯 Rate Limiting Inteligente

**Por tipo de comprobante:**
```php
RateLimiter::for('sunat-send-facturas', function (Request $request) {
    return Limit::perMinute(20);  // Facturas: más límite
});

RateLimiter::for('sunat-send-boletas', function (Request $request) {
    return Limit::perMinute(10);  // Boletas: menos límite (van por RC)
});
```

### 5. 💾 Cache de Validaciones

**Antes de enviar:**
```php
// Validar certificado y conexión (cachear 5 minutos)
$company = Cache::remember("company_valid_{$companyId}", 300, function() use ($companyId) {
    return Company::with('branches')->find($companyId);
});
```

---

## Monitoreo y Manejo de Errores

### 1. 📊 Métricas a Monitorear

```php
// Log de envíos
Log::channel('sunat')->info('Envío exitoso', [
    'tipo' => 'factura',
    'id' => $invoice->id,
    'numero' => $invoice->numero_completo,
    'tiempo_respuesta' => $responseTime,
    'intentos' => $attempts
]);
```

**Métricas clave:**
- ✅ Tasa de éxito/fallo
- ✅ Tiempo de respuesta promedio de SUNAT
- ✅ Códigos de error más frecuentes
- ✅ Rate limiting triggers (429 responses)
- ✅ Reintentos necesarios

### 2. 🔔 Alertas Automáticas

```php
// Notificar cuando rate limit se alcanza frecuentemente
if ($rateLimitHits > 10) {
    Notification::route('mail', 'admin@empresa.com')
        ->notify(new RateLimitExceeded($rateLimitHits));
}
```

### 3. 🔄 Estrategia de Reintentos

**Exponential Backoff:**
```php
// En Jobs de Queue
public $tries = 5;
public $backoff = [30, 60, 180, 600, 1800];  // 30s, 1m, 3m, 10m, 30m

public function handle()
{
    try {
        $this->sendToSunat();
    } catch (SunatException $e) {
        if ($e->isRateLimited()) {
            $this->release(60);  // Reintentar en 60 segundos
        }
        throw $e;
    }
}
```

### 4. 📈 Dashboard de Monitoreo

**Información útil a mostrar:**
```
┌─────────────────────────────────────────┐
│ SUNAT - Envíos en Tiempo Real          │
├─────────────────────────────────────────┤
│ Comprobantes enviados (hoy):    2,345  │
│ Tasa de éxito:                  98.5%  │
│ Tiempo promedio respuesta:      2.3s   │
│ En cola de envío:               45      │
│ Rate limit alcanzado (última hora): 3  │
│ Próximo slot disponible:        15s    │
└─────────────────────────────────────────┘
```

---

## Códigos de Error Comunes de SUNAT

| Código | Descripción | Solución |
|--------|-------------|----------|
| 0001 | Formato de archivo inválido | Validar XML antes de enviar |
| 0002 | Certificado no válido | Renovar certificado digital |
| 0100 | Sistema SUNAT no disponible | Reintentar en 5-10 minutos |
| 0150 | RUC no autorizado para emitir | Verificar estado de empresa en SUNAT |
| 2000 | Numeración duplicada | Verificar correlativos |
| 2800 | Comprobante fuera de plazo | Enviar dentro de 3 días calendario |
| 429 | Too Many Requests (Rate Limit) | Esperar según header Retry-After |

---

## Ejemplo de Implementación Completa

### Configurar Rate Limit Personalizado

```php
// app/Providers/AppServiceProvider.php

protected function configureRateLimiting(): void
{
    // Límite base para envíos a SUNAT
    RateLimiter::for('sunat-send', function (Request $request) {
        $user = $request->user();

        // Usuarios premium tienen límites más altos
        $limit = $user?->is_premium ? 30 : 10;

        return Limit::perMinute($limit)
            ->by($user?->id ?: $request->ip())
            ->response(function (Request $request, array $headers) use ($limit) {
                return response()->json([
                    'success' => false,
                    'message' => "Límite de envíos alcanzado: {$limit} por minuto.",
                    'retry_after' => $headers['Retry-After'] ?? 60,
                    'current_plan' => $request->user()?->plan ?? 'free'
                ], 429);
            });
    });
}
```

### Usar en Controlador con Retry

```php
// app/Http/Controllers/Api/InvoiceController.php

public function sendToSunat($id)
{
    $invoice = Invoice::findOrFail($id);

    try {
        // Intentar envío con retry automático
        $result = retry(3, function () use ($invoice) {
            return $this->documentService->sendToSunat($invoice);
        }, 5000);  // 5 segundos entre reintentos

        return response()->json([
            'success' => true,
            'message' => 'Comprobante enviado exitosamente',
            'data' => $result
        ]);

    } catch (RateLimitException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Rate limit alcanzado. Intente nuevamente en ' . $e->retryAfter . ' segundos.',
            'retry_after' => $e->retryAfter
        ], 429);

    } catch (SunatException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error de SUNAT: ' . $e->getMessage(),
            'code' => $e->getCode()
        ], 422);
    }
}
```

---

## Conclusión

### ✅ Configuración Actual
- **10 comprobantes por minuto** = **0.16 por segundo**
- Suficiente para pequeñas y medianas empresas

### 📊 Recomendaciones por Tamaño de Empresa

| Tamaño Empresa | Comprobantes/Día | Límite Recomendado | Estrategia |
|----------------|------------------|-------------------|------------|
| Pequeña (1-50 comprobantes/día) | < 50 | 10/minuto | Envío directo |
| Mediana (50-500 comprobantes/día) | 50-500 | 20-30/minuto | Envío directo + async |
| Grande (500-5000 comprobantes/día) | 500-5,000 | 40-60/minuto | Async + queues + batch |
| Enterprise (>5000 comprobantes/día) | > 5,000 | 60+/minuto | Queues + batch + workers múltiples |

### 🎯 Próximos Pasos Sugeridos

1. **Evaluar volumen real** de comprobantes de tu negocio
2. **Ajustar rate limits** según necesidad
3. **Implementar monitoreo** de métricas
4. **Configurar alertas** para rate limit excedido
5. **Probar en ambiente beta** de SUNAT antes de producción

---

## Referencias

- [SUNAT - Comprobantes de Pago Electrónicos](https://cpe.sunat.gob.pe/node/131)
- [SUNAT - Procedimiento de Contingencia](https://cpe.sunat.gob.pe/informacion_general/procedimiento_contingencia)
- [Resolución de Superintendencia N° 097-2012/SUNAT](https://cpe.sunat.gob.pe/informacion_general/normas_legales)
- [Laravel Rate Limiting Documentation](https://laravel.com/docs/12.x/routing#rate-limiting)

---

**Última actualización:** 10 de Diciembre 2025
**Versión API:** Laravel 12
**Autor:** Sistema de Facturación Electrónica SUNAT Perú

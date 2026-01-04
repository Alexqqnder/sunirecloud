# 📚 Documentación - Sistema de Facturación Electrónica SUNAT

Bienvenido a la documentación técnica del Sistema de Facturación Electrónica para SUNAT Perú.

## 📑 Índice de Documentación

### 1. [Resúmenes Diarios de Boletas](./resumenes-diarios-boletas.md)
**Tema:** Estados de comprobantes en resúmenes diarios
**Contenido:**
- ¿Qué son los Resúmenes Diarios?
- Estados de Comprobantes (Adición, Modificación, Anulación)
- Estructura del JSON de detalles
- Flujo de operación completo
- Ejemplos prácticos
- Códigos de error comunes
- Mejores prácticas

**Ideal para:** Entender cómo funcionan los estados `"1"`, `"2"` y `"3"` en los resúmenes de boletas.

---

### 2. [Límites de Envío de Comprobantes](./limites-envio-comprobantes.md)
**Tema:** Rate limiting y límites de envío a SUNAT
**Contenido:**
- Límites de comprobantes por segundo/minuto
- Configuración de rate limiting
- Estrategias de optimización
- Cálculos y recomendaciones por volumen
- Monitoreo y manejo de errores
- Implementación de colas y envío asíncrono

**Ideal para:** Entender cuántos comprobantes puedes enviar y cómo optimizar el rendimiento.

---

### 3. [Sistema de Webhooks](./webhooks.md)
**Tema:** Notificaciones en tiempo real mediante webhooks
**Contenido:**
- ¿Qué son los webhooks y para qué sirven?
- Arquitectura del sistema
- Tablas de base de datos (webhooks, webhook_deliveries)
- Eventos disponibles (invoice.accepted, boleta.accepted, etc.)
- API completa de gestión de webhooks
- Seguridad y firmas HMAC SHA256
- Sistema de reintentos automático
- 6 alternativas para probar webhooks (RequestBin, Beeceptor, endpoint local, etc.)
- Ejemplos prácticos en PHP, Node.js y Python
- Monitoreo y solución de problemas

**Ideal para:** Integrar tu sistema con notificaciones automáticas cuando ocurren eventos en la facturación.

---

### 4. [Detracción](./detraccion.md)
**Tema:** Sistema de detracciones SUNAT
**Contenido:**
- Conceptos de detracción
- Implementación técnica
- Validaciones y reglas de negocio

**Ideal para:** Implementar y entender el sistema de detracciones.

---

## 🔍 Búsqueda Rápida por Tema

### Comprobantes Electrónicos
- **Boletas de Venta:** Ver [Resúmenes Diarios](./resumenes-diarios-boletas.md#qué-son-los-resúmenes-diarios)
- **Estados de Documentos:** Ver [Estados de Comprobantes](./resumenes-diarios-boletas.md#estados-de-comprobantes)
- **Anulación de Boletas:** Ver [Estado 3 - Anulación](./resumenes-diarios-boletas.md#estado-3---anulación)

### Integración SUNAT
- **Resúmenes Diarios:** Ver [Resúmenes Diarios](./resumenes-diarios-boletas.md)
- **Límites de Envío:** Ver [Límites de Envío](./limites-envio-comprobantes.md)
- **Rate Limiting:** Ver [Rate Limiting](./limites-envio-comprobantes.md#límites-configurados-en-la-api)
- **Webhooks:** Ver [Sistema de Webhooks](./webhooks.md)
- **Notificaciones en Tiempo Real:** Ver [Webhooks](./webhooks.md#qué-son-los-webhooks)
- **Detracciones:** Ver [Detracción](./detraccion.md)

### Estructura de Datos
- **JSON de Detalles:** Ver [Estructura del JSON](./resumenes-diarios-boletas.md#estructura-del-json)
- **Campos del Detalle:** Ver [Campos del Detalle](./resumenes-diarios-boletas.md#campos-del-detalle)

### Rendimiento y Optimización
- **Límites por Segundo:** Ver [Cálculo de Comprobantes](./limites-envio-comprobantes.md#cálculo-de-comprobantes-por-segundo)
- **Optimización de Envíos:** Ver [Estrategias de Optimización](./limites-envio-comprobantes.md#estrategias-de-optimización)
- **Envío Asíncrono:** Ver [Envío Asíncrono](./limites-envio-comprobantes.md#-envío-asíncrono-con-colas)

### Automatización e Integración
- **Eventos Disponibles:** Ver [Eventos de Webhooks](./webhooks.md#eventos-disponibles)
- **API de Webhooks:** Ver [API REST Completa](./webhooks.md#api-de-webhooks)
- **Probar Webhooks:** Ver [Alternativas de Prueba](./webhooks.md#alternativas-para-probar-webhooks)
- **Ejemplos PHP/Node/Python:** Ver [Ejemplos Prácticos](./webhooks.md#ejemplos-prácticos-completos)

### Solución de Problemas
- **Errores Comunes:** Ver [Códigos de Error](./resumenes-diarios-boletas.md#códigos-de-error-comunes)
- **Validaciones:** Ver [Validaciones Importantes](./resumenes-diarios-boletas.md#validaciones-importantes)
- **Rate Limit Excedido:** Ver [Monitoreo y Errores](./limites-envio-comprobantes.md#monitoreo-y-manejo-de-errores)
- **Webhooks no se disparan:** Ver [Solución de Problemas Webhooks](./webhooks.md#solución-de-problemas)

---

## 🚀 Guías de Inicio Rápido

### Para Desarrolladores

1. **Nuevo en el proyecto**
   - Comienza con [Resúmenes Diarios](./resumenes-diarios-boletas.md) para entender el flujo básico
   - Revisa los [Ejemplos de Uso](./resumenes-diarios-boletas.md#ejemplos-de-uso)

2. **Implementando funcionalidades**
   - Consulta las [Mejores Prácticas](./resumenes-diarios-boletas.md#mejores-prácticas)
   - Revisa el [Flujo de Operación](./resumenes-diarios-boletas.md#flujo-de-operación)

3. **Debugging y errores**
   - Consulta [Códigos de Error Comunes](./resumenes-diarios-boletas.md#códigos-de-error-comunes)

---

## 📂 Estructura del Proyecto

```
documentacion/
├── README.md                           # Este archivo (índice)
├── resumenes-diarios-boletas.md       # Documentación de resúmenes diarios
├── limites-envio-comprobantes.md      # Rate limiting y límites de envío
├── webhooks.md                         # Sistema de webhooks y notificaciones
└── detraccion.md                       # Documentación de detracciones
```

---

## 🔗 Enlaces Útiles

### Documentación Oficial SUNAT
- [Portal de Facturación Electrónica](https://cpe.sunat.gob.pe/)
- [Manuales y Guías](https://cpe.sunat.gob.pe/documentacion)
- [Códigos de Catálogo](https://cpe.sunat.gob.pe/sites/default/files/inline-files/ANEXOS-UBL-2.1.pdf)

### Bibliotecas y Herramientas
- [Greenter - GitHub](https://github.com/thegreenter/greenter)
- [Laravel 12 Docs](https://laravel.com/docs/12.x)

### Código Fuente del Proyecto
- **Servicios:** `app/Services/DocumentService.php`
- **Modelos:** `app/Models/`
- **Controladores:** `app/Http/Controllers/Api/`

---

## 📝 Contribuir a la Documentación

Si encuentras errores o quieres agregar contenido:

1. Edita los archivos `.md` correspondientes
2. Mantén el formato Markdown consistente
3. Agrega ejemplos prácticos cuando sea posible
4. Actualiza este índice si agregas nuevos documentos

---

## 📅 Historial de Actualizaciones

| Fecha | Documento | Versión | Descripción |
|-------|-----------|---------|-------------|
| 2025-12-10 | webhooks.md | 1.0 | Sistema de webhooks, notificaciones en tiempo real |
| 2025-12-10 | limites-envio-comprobantes.md | 1.0 | Rate limiting y límites de envío a SUNAT |
| 2025-12-10 | resumenes-diarios-boletas.md | 1.0 | Creación inicial - Estados de comprobantes |
| 2025-12-04 | detraccion.md | 1.0 | Documentación de detracciones |

---

**Sistema de Facturación Electrónica - SUNAT Perú**
**Última actualización:** Diciembre 2025

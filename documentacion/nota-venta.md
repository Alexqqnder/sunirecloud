# 📝 Documentación: Notas de Venta

## Descripción
Las **Notas de Venta** son documentos internos que **NO se envían a SUNAT** y solo generan PDF para entregar al cliente. No tienen validez tributaria.

---

## 🔑 Características Principales

✅ **No envía a SUNAT** - Documento interno
✅ **Solo genera PDF** - Formatos: A4, A5, 80mm, 58mm
✅ **Numeración automática** - Sistema de correlativos
✅ **Cálculo de IGV** - Igual que facturas/boletas
✅ **Gestión de clientes** - Crea o usa clientes existentes
✅ **QR Code interno** - Para tracking
✅ **Soft delete** - Mantiene histórico

---

## 📊 Códigos y Series

| Campo | Valor | Descripción |
|-------|-------|-------------|
| **Tipo de Documento** | `17` | Código para Nota de Venta |
| **Series sugeridas** | `NV01`, `NV02` | Serie personalizada |
| **Formato de número** | `NV01-00000001` | Serie + correlativo (8 dígitos) |

---

## 🔌 Endpoints

### Base URL
```
/api/v1/nota-ventas
```

### 1. Crear Nota de Venta

```http
POST /api/v1/nota-ventas
Content-Type: application/json
Authorization: Bearer {{token}}

{
  "company_id": 1,
  "branch_id": 1,
  "client": {
    "tipo_documento": "6",
    "numero_documento": "20123456789",
    "razon_social": "EMPRESA EJEMPLO SAC",
    "direccion": "Av. Ejemplo 123, Lima",
    "email": "contacto@ejemplo.com",
    "telefono": "987654321"
  },
  "serie": "NV01",
  "fecha_emision": "2025-12-24",
  "moneda": "PEN",
  "detalles": [
    {
      "codigo": "PROD001",
      "unidad": "NIU",
      "descripcion": "Producto de Ejemplo",
      "cantidad": 2,
      "precio_unitario": 100.00,
      "codigo_afectacion_igv": "10",
      "porcentaje_igv": 18
    }
  ],
  "observaciones": "Nota de venta de prueba"
}
```

**Respuesta (201 Created):**
```json
{
  "success": true,
  "message": "Nota de Venta creada exitosamente",
  "data": {
    "id": 1,
    "company_id": 1,
    "branch_id": 1,
    "tipo_documento": "17",
    "serie": "NV01",
    "correlativo": "00000001",
    "numero_completo": "NV01-00000001",
    "fecha_emision": "2025-12-24",
    "mto_imp_venta": 236.00,
    "moneda": "PEN"
  }
}
```

---

### 2. Listar Notas de Venta

```http
GET /api/v1/nota-ventas?company_id=1&per_page=15
Authorization: Bearer {{token}}
```

**Filtros disponibles:**
- `company_id` - ID de la empresa
- `branch_id` - ID de la sucursal
- `client_id` - ID del cliente
- `serie` - Serie (NV01, NV02, etc.)
- `numero_completo` - Búsqueda parcial del número
- `fecha_desde` y `fecha_hasta` - Rango de fechas
- `per_page` - Resultados por página (default: 15)

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 1,
        "numero_completo": "NV01-00000001",
        "fecha_emision": "2025-12-24",
        "client": {
          "razon_social": "EMPRESA EJEMPLO SAC"
        },
        "mto_imp_venta": 236.00,
        "moneda": "PEN"
      }
    ],
    "total": 1
  }
}
```

---

### 3. Ver Detalle

```http
GET /api/v1/nota-ventas/{id}
Authorization: Bearer {{token}}
```

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "numero_completo": "NV01-00000001",
    "fecha_emision": "2025-12-24",
    "company": {...},
    "branch": {...},
    "client": {...},
    "detalles": [...],
    "totales": {...}
  }
}
```

---

### 4. Actualizar Observaciones

```http
PUT /api/v1/nota-ventas/{id}
Content-Type: application/json
Authorization: Bearer {{token}}

{
  "observaciones": "Observaciones actualizadas"
}
```

---

### 5. Eliminar (Soft Delete)

```http
DELETE /api/v1/nota-ventas/{id}
Authorization: Bearer {{token}}
```

**Respuesta:**
```json
{
  "success": true,
  "message": "Nota de Venta eliminada exitosamente"
}
```

---

### 6. Generar PDF

```http
POST /api/v1/nota-ventas/{id}/generate-pdf
Content-Type: application/json
Authorization: Bearer {{token}}

{
  "format": "a4"
}
```

**Formatos disponibles:** `a4`, `a5`, `80mm`, `58mm`

**Respuesta:**
```json
{
  "success": true,
  "message": "PDF generado correctamente en formato a4",
  "data": {
    "pdf_path": "empresas/1/nota-ventas/NV01-00000001_a4.pdf",
    "format": "a4",
    "document_type": "nota-venta",
    "document_id": 1
  }
}
```

---

### 7. Descargar PDF

```http
GET /api/v1/nota-ventas/{id}/download-pdf?format=a4
Authorization: Bearer {{token}}
```

**Respuesta:** Archivo PDF para descargar

---

## 📋 Estructura de Datos

### Detalle de Item

```json
{
  "codigo": "PROD001",
  "unidad": "NIU",
  "descripcion": "Producto de Ejemplo",
  "cantidad": 2,
  "precio_unitario": 100.00,
  "codigo_afectacion_igv": "10",
  "porcentaje_igv": 18,
  "descuento": 0
}
```

### Códigos de Afectación IGV

| Código | Descripción |
|--------|-------------|
| `10` | Gravado - Operación Onerosa |
| `20` | Exonerado - Operación Onerosa |
| `30` | Inafecto - Operación Onerosa |
| `40` | Exportación |

---

## 🔄 Sistema de Correlativos

### Crear Correlativo

```http
POST /api/v1/branches/{branch_id}/correlatives

{
  "tipo_documento": "17",
  "serie": "NV01",
  "correlativo_actual": 0
}
```

---

## 📊 Diferencias con Documentos Electrónicos

| Característica | Factura/Boleta | Nota de Venta |
|----------------|----------------|---------------|
| Envío SUNAT | ✅ Sí | ❌ No |
| XML firmado | ✅ Sí | ❌ No |
| CDR de SUNAT | ✅ Sí | ❌ No |
| PDF | ✅ Sí | ✅ Sí |
| Estado SUNAT | ✅ Sí | ❌ No |
| Correlativos | ✅ Sí | ✅ Sí |
| Cálculo IGV | ✅ Sí | ✅ Sí |

---

## 🧮 Cálculo de Totales

El sistema calcula automáticamente:

- **Valor Venta** - Suma de subtotales de items
- **Operaciones Gravadas** - Items con código afectación `10`
- **Operaciones Exoneradas** - Items con código afectación `20`
- **Operaciones Inafectas** - Items con código afectación `30`
- **Total IGV** - Suma del IGV de todos los items gravados
- **Total a Pagar** - Valor venta + IGV

---

## 📄 Formatos PDF

### A4 (210x297mm)
- Uso: Impresión estándar
- Ideal para: Archivos, envío por email

### A5 (148x210mm)
- Uso: Versión compacta
- Ideal para: Economizar papel

### 80mm (Ticket)
- Uso: Impresoras térmicas de 80mm
- Ideal para: Puntos de venta, tiendas

### 58mm (Ticket)
- Uso: Impresoras térmicas de 58mm
- Ideal para: Dispositivos móviles, POS pequeños

---

## 💾 Base de Datos

### Tabla: `nota_ventas`

**Campos principales:**
- `id` - ID autoincremental
- `company_id` - Empresa
- `branch_id` - Sucursal
- `client_id` - Cliente
- `tipo_documento` - Código `17`
- `serie` - Serie (NV01, etc.)
- `correlativo` - Número secuencial
- `numero_completo` - Serie-Correlativo
- `fecha_emision` - Fecha de emisión
- `mto_imp_venta` - Total a pagar
- `detalles` - JSON con items
- `pdf_path` - Ruta del PDF
- `codigo_hash` - Hash interno
- `observaciones` - Observaciones

---

## 🔍 Logs y Auditoría

Los eventos se registran en:

```
storage/logs/audit.log
```

**Eventos registrados:**
- Creación de nota de venta
- Generación de PDF
- Actualización de datos
- Eliminación (soft delete)

---

## ⚠️ Importante

1. **No usar tipo de documento '01' o '03'** - Son para factura/boleta
2. **Usar código '17' para Nota de Venta** - Código personalizado
3. **No se envía a SUNAT** - Documento interno únicamente
4. **No genera XML ni CDR** - Solo PDF
5. **Calcular totales igual que boletas** - Usa `calculateTotals()`

---

## 🚀 Próximas Funcionalidades

- Reportes de notas de venta por periodo
- Conversión de Nota de Venta a Factura/Boleta
- Estadísticas en el dashboard
- Envío automático por email del PDF

---

## 📞 Soporte

Para dudas o problemas:
1. Revisar logs en `storage/logs/`
2. Verificar correlativos creados
3. Validar formato de datos de entrada

---

**Última actualización:** 2025-12-24

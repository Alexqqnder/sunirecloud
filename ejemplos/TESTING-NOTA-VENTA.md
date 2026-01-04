# 🧪 EJEMPLOS DE TESTING - NOTA DE VENTA

## Variables de Entorno (Postman)

```javascript
base_url = http://localhost:8000
token = tu_bearer_token_aqui
company_id = 1
branch_id = 1
nota_venta_id = 1
```

---

## 1. CREAR CORRELATIVO (Paso Previo)

### Crear correlativo para tipo documento '17'

```http
POST {{base_url}}/api/v1/branches/{{branch_id}}/correlatives
Content-Type: application/json
Authorization: Bearer {{token}}

{
  "tipo_documento": "17",
  "serie": "NV01",
  "correlativo_actual": 0
}
```

**Respuesta esperada (201 Created):**
```json
{
  "success": true,
  "message": "Correlativo creado exitosamente",
  "data": {
    "id": 1,
    "branch_id": 1,
    "tipo_documento": "17",
    "serie": "NV01",
    "correlativo_actual": 0
  }
}
```

---

## 2. CREAR NOTA DE VENTA

### Ejemplo Básico

```http
POST {{base_url}}/api/v1/nota-ventas
Content-Type: application/json
Authorization: Bearer {{token}}

{
  "company_id": {{company_id}},
  "branch_id": {{branch_id}},
  "client": {
    "tipo_documento": "6",
    "numero_documento": "20123456789",
    "razon_social": "EMPRESA EJEMPLO SAC",
    "direccion": "Av. Los Alamos 123, Lima",
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
      "descripcion": "Laptop HP Core i5",
      "cantidad": 2,
      "precio_unitario": 1500.00,
      "codigo_afectacion_igv": "10",
      "porcentaje_igv": 18
    },
    {
      "codigo": "SERV001",
      "unidad": "ZZ",
      "descripcion": "Servicio de Instalación",
      "cantidad": 1,
      "precio_unitario": 200.00,
      "codigo_afectacion_igv": "10",
      "porcentaje_igv": 18
    }
  ],
  "observaciones": "Entrega a domicilio incluida"
}
```

**cURL:**
```bash
curl -X POST "{{base_url}}/api/v1/nota-ventas" \
  -H "Authorization: Bearer {{token}}" \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": 1,
    "branch_id": 1,
    "client": {
      "tipo_documento": "6",
      "numero_documento": "20123456789",
      "razon_social": "EMPRESA EJEMPLO SAC"
    },
    "serie": "NV01",
    "fecha_emision": "2025-12-24",
    "moneda": "PEN",
    "detalles": [
      {
        "unidad": "NIU",
        "descripcion": "Producto Ejemplo",
        "cantidad": 1,
        "precio_unitario": 100.00,
        "codigo_afectacion_igv": "10"
      }
    ]
  }'
```

**Respuesta esperada (201 Created):**
```json
{
  "success": true,
  "message": "Nota de Venta creada exitosamente",
  "data": {
    "id": 1,
    "company_id": 1,
    "branch_id": 1,
    "client_id": 1,
    "tipo_documento": "17",
    "serie": "NV01",
    "correlativo": "00000001",
    "numero_completo": "NV01-00000001",
    "fecha_emision": "2025-12-24",
    "moneda": "PEN",
    "valor_venta": 3200.00,
    "mto_oper_gravadas": 3200.00,
    "mto_igv": 576.00,
    "mto_imp_venta": 3776.00,
    "company": {...},
    "branch": {...},
    "client": {...}
  }
}
```

### Ejemplo con Productos Exonerados

```http
POST {{base_url}}/api/v1/nota-ventas
Content-Type: application/json
Authorization: Bearer {{token}}

{
  "company_id": {{company_id}},
  "branch_id": {{branch_id}},
  "client": {
    "tipo_documento": "1",
    "numero_documento": "12345678",
    "razon_social": "JUAN PEREZ LOPEZ"
  },
  "serie": "NV01",
  "fecha_emision": "2025-12-24",
  "detalles": [
    {
      "codigo": "LIB001",
      "unidad": "NIU",
      "descripcion": "Libro de Matemáticas",
      "cantidad": 3,
      "precio_unitario": 50.00,
      "codigo_afectacion_igv": "20",
      "porcentaje_igv": 0
    }
  ]
}
```

---

## 3. LISTAR NOTAS DE VENTA

### Listar todas

```http
GET {{base_url}}/api/v1/nota-ventas?company_id={{company_id}}
Authorization: Bearer {{token}}
```

### Con paginación

```http
GET {{base_url}}/api/v1/nota-ventas?company_id={{company_id}}&per_page=10&page=1
Authorization: Bearer {{token}}
```

### Filtrar por fecha

```http
GET {{base_url}}/api/v1/nota-ventas?company_id={{company_id}}&fecha_desde=2025-12-01&fecha_hasta=2025-12-31
Authorization: Bearer {{token}}
```

### Filtrar por serie

```http
GET {{base_url}}/api/v1/nota-ventas?company_id={{company_id}}&serie=NV01
Authorization: Bearer {{token}}
```

### Buscar por número

```http
GET {{base_url}}/api/v1/nota-ventas?company_id={{company_id}}&numero_completo=NV01-000001
Authorization: Bearer {{token}}
```

---

## 4. VER DETALLE

```http
GET {{base_url}}/api/v1/nota-ventas/{{nota_venta_id}}
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
    "company": {
      "id": 1,
      "razon_social": "MI EMPRESA SAC",
      "ruc": "20123456789"
    },
    "client": {
      "id": 1,
      "razon_social": "EMPRESA EJEMPLO SAC",
      "numero_documento": "20123456789"
    },
    "detalles": [...],
    "mto_imp_venta": 3776.00,
    "pdf_path": "empresas/1/nota-ventas/NV01-00000001.pdf"
  }
}
```

---

## 5. ACTUALIZAR OBSERVACIONES

```http
PUT {{base_url}}/api/v1/nota-ventas/{{nota_venta_id}}
Content-Type: application/json
Authorization: Bearer {{token}}

{
  "observaciones": "Cliente solicitó cambio de dirección de entrega"
}
```

---

## 6. GENERAR PDF

### Formato A4

```http
POST {{base_url}}/api/v1/nota-ventas/{{nota_venta_id}}/generate-pdf
Content-Type: application/json
Authorization: Bearer {{token}}

{
  "format": "a4"
}
```

### Formato A5

```http
POST {{base_url}}/api/v1/nota-ventas/{{nota_venta_id}}/generate-pdf
Content-Type: application/json
Authorization: Bearer {{token}}

{
  "format": "a5"
}
```

### Formato 80mm (Ticket)

```http
POST {{base_url}}/api/v1/nota-ventas/{{nota_venta_id}}/generate-pdf
Content-Type: application/json
Authorization: Bearer {{token}}

{
  "format": "80mm"
}
```

### Formato 58mm (Ticket)

```http
POST {{base_url}}/api/v1/nota-ventas/{{nota_venta_id}}/generate-pdf
Content-Type: application/json
Authorization: Bearer {{token}}

{
  "format": "58mm"
}
```

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

## 7. DESCARGAR PDF

```http
GET {{base_url}}/api/v1/nota-ventas/{{nota_venta_id}}/download-pdf?format=a4
Authorization: Bearer {{token}}
```

**Otros formatos:**
```http
GET {{base_url}}/api/v1/nota-ventas/{{nota_venta_id}}/download-pdf?format=a5
GET {{base_url}}/api/v1/nota-ventas/{{nota_venta_id}}/download-pdf?format=80mm
GET {{base_url}}/api/v1/nota-ventas/{{nota_venta_id}}/download-pdf?format=58mm
```

---

## 8. ELIMINAR (SOFT DELETE)

```http
DELETE {{base_url}}/api/v1/nota-ventas/{{nota_venta_id}}
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

## 9. CASOS DE ERROR

### Error: Serie no existe

```http
POST {{base_url}}/api/v1/nota-ventas
{
  "serie": "NV99"  // Serie sin correlativo
}
```

**Respuesta (500):**
```json
{
  "success": false,
  "message": "Error al crear la nota de venta",
  "error": "Correlativo no encontrado para serie NV99"
}
```

### Error: Validación de datos

```http
POST {{base_url}}/api/v1/nota-ventas
{
  "company_id": 1,
  "detalles": []  // Array vacío
}
```

**Respuesta (422):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "branch_id": ["La sucursal es obligatoria"],
    "client": ["Los datos del cliente son obligatorios"],
    "serie": ["La serie es obligatoria"],
    "detalles": ["Debe incluir al menos un item"]
  }
}
```

---

## 10. TESTING COMPLETO (Script de Prueba)

### Test Secuencial

```bash
#!/bin/bash

BASE_URL="http://localhost:8000/api/v1"
TOKEN="tu_token_aqui"

echo "1. Crear correlativo..."
curl -X POST "$BASE_URL/branches/1/correlatives" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"tipo_documento":"17","serie":"NV01","correlativo_actual":0}'

echo "\n2. Crear nota de venta..."
RESPONSE=$(curl -X POST "$BASE_URL/nota-ventas" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "company_id": 1,
    "branch_id": 1,
    "client": {
      "tipo_documento": "6",
      "numero_documento": "20123456789",
      "razon_social": "TEST SAC"
    },
    "serie": "NV01",
    "fecha_emision": "2025-12-24",
    "detalles": [{
      "unidad": "NIU",
      "descripcion": "Test",
      "cantidad": 1,
      "precio_unitario": 100,
      "codigo_afectacion_igv": "10"
    }]
  }')

NOTA_ID=$(echo $RESPONSE | jq -r '.data.id')
echo "Nota de Venta ID: $NOTA_ID"

echo "\n3. Generar PDF A4..."
curl -X POST "$BASE_URL/nota-ventas/$NOTA_ID/generate-pdf" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"format":"a4"}'

echo "\n4. Descargar PDF..."
curl -X GET "$BASE_URL/nota-ventas/$NOTA_ID/download-pdf?format=a4" \
  -H "Authorization: Bearer $TOKEN" \
  -o "nota_venta_$NOTA_ID.pdf"

echo "\nTesting completado!"
```

---

## ✅ CHECKLIST DE PRUEBAS

- [ ] Crear correlativo para tipo '17'
- [ ] Crear nota de venta con productos gravados
- [ ] Crear nota de venta con productos exonerados
- [ ] Crear nota de venta con cliente DNI
- [ ] Crear nota de venta con cliente RUC
- [ ] Listar notas de venta
- [ ] Filtrar por fecha
- [ ] Filtrar por serie
- [ ] Ver detalle de nota de venta
- [ ] Actualizar observaciones
- [ ] Generar PDF en formato A4
- [ ] Generar PDF en formato A5
- [ ] Generar PDF en formato 80mm
- [ ] Generar PDF en formato 58mm
- [ ] Descargar PDF
- [ ] Eliminar nota de venta
- [ ] Verificar soft delete funciona
- [ ] Probar validaciones de campos requeridos

---

## 🔍 VERIFICAR EN BASE DE DATOS

```sql
-- Ver todas las notas de venta
SELECT id, numero_completo, fecha_emision, mto_imp_venta, created_at
FROM nota_ventas
WHERE deleted_at IS NULL
ORDER BY created_at DESC;

-- Ver detalles de una nota de venta
SELECT detalles, leyendas
FROM nota_ventas
WHERE id = 1;

-- Ver correlativos disponibles
SELECT serie, correlativo_actual, tipo_documento
FROM correlatives
WHERE tipo_documento = '17';

-- Ver cliente creado automáticamente
SELECT id, razon_social, numero_documento
FROM clients
WHERE numero_documento = '20123456789';
```

---

**Última actualización:** 2025-12-24

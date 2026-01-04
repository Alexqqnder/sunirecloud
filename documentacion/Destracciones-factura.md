# 📊 Sistema de Detracciones (SPOT) - Documentación Completa

## Tabla de Contenidos

1. [¿Qué es la Detracción?](#qué-es-la-detracción)
2. [Marco Legal](#marco-legal)
3. [¿Cómo Funciona el Sistema?](#cómo-funciona-el-sistema)
4. [Implementación en la API](#implementación-en-la-api)
5. [Estructura de Datos](#estructura-de-datos)
6. [Catálogo de Bienes y Servicios](#catálogo-de-bienes-y-servicios)
7. [Ejemplos Prácticos](#ejemplos-prácticos)
8. [Validaciones y Reglas](#validaciones-y-reglas)
9. [Errores Comunes](#errores-comunes)
10. [Flujo Completo](#flujo-completo)

---

## ¿Qué es la Detracción?

La **detracción** (también llamada **SPOT** - Sistema de Pago de Obligaciones Tributarias) es un mecanismo tributario implementado por SUNAT en Perú donde:

- El **comprador retiene** un porcentaje del precio de venta
- Este monto es **depositado** en una cuenta especial del Banco de la Nación
- La cuenta está a nombre del **proveedor/vendedor**
- El proveedor usa estos fondos **exclusivamente** para pagar impuestos

### 🎯 Objetivo

Asegurar el pago de impuestos en sectores con alta informalidad o riesgo tributario.

### 💰 ¿Quién Retiene?

El **comprador** (adquirente del bien o servicio).

### 💵 ¿A Quién se le Retiene?

Al **proveedor** (vendedor del bien o servicio).

---

## Marco Legal

### Normas Principales

| Norma | Descripción |
|-------|-------------|
| **Ley N° 28194** | Ley para la lucha contra la evasión y formalización |
| **D.S. N° 155-2004-EF** | Reglamento de la Ley de SPOT |
| **Resoluciones de Superintendencia** | Aprueban bienes y servicios sujetos a detracción |

### Fechas Clave

- **2002**: Implementación inicial del SPOT
- **2004**: Reglamentación completa
- **Actualidad**: Más de 40 códigos de bienes/servicios

---

## ¿Cómo Funciona el Sistema?

### Flujo de una Operación con Detracción

```
┌─────────────────────────────────────────────────────────────────┐
│ PASO 1: Emisión de la Factura                                  │
└─────────────────────────────────────────────────────────────────┘
Proveedor emite factura por S/ 1,180.00
- Valor Venta: S/ 1,000.00
- IGV (18%): S/ 180.00
- Total: S/ 1,180.00
- Detracción (12%): S/ 141.60

┌─────────────────────────────────────────────────────────────────┐
│ PASO 2: El Cliente Realiza DOS Pagos                           │
└─────────────────────────────────────────────────────────────────┘
✅ Pago al Proveedor: S/ 1,038.40
   (S/ 1,180.00 - S/ 141.60)
   → Transferencia/cheque/efectivo

✅ Depósito en Banco de la Nación: S/ 141.60
   → A la cuenta de detracciones del proveedor
   → Usando formulario N° 1662 (depósito)
   → Plazo: 5 días hábiles desde el pago o entrega del bien

┌─────────────────────────────────────────────────────────────────┐
│ PASO 3: El Proveedor Usa los Fondos                            │
└─────────────────────────────────────────────────────────────────┘
El proveedor SOLO puede usar estos fondos para:
- Pago de tributos administrados por SUNAT
- Multas e intereses
- Costas y gastos por cobranza coactiva

┌─────────────────────────────────────────────────────────────────┐
│ PASO 4: Liberación de Fondos (Opcional)                        │
└─────────────────────────────────────────────────────────────────┘
Si hay exceso de fondos retenidos:
- Solicitud de libre disposición
- Después de 3 meses sin deudas tributarias
- Mediante SUNAT Virtual
```

---

## Implementación en la API

### Arquitectura del Sistema

```
┌─────────────────────────────────────────────────────────────────┐
│                    REQUEST DEL CLIENTE                          │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│         StoreInvoiceRequest (Validación)                        │
│  • Valida estructura del objeto "detraccion"                    │
│  • Valida código de bien/servicio (Catálogo 54)                 │
│  • Valida porcentaje (0-100%)                                   │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│         DocumentService (Lógica de Negocio)                     │
│  • Calcula totales de la factura                                │
│  • Genera leyenda "2006" si hay detracción                      │
│  • Guarda datos en campo JSON "detraccion"                      │
│  • Guarda monto en campo "mto_detraccion"                       │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│         GreenterService (Generación XML)                        │
│  • Crea objeto Detraction de Greenter                           │
│  • setCodBienDetraccion() → Código del bien/servicio            │
│  • setCodMedioPago() → Medio de pago (001)                      │
│  • setCtaBanco() → Cuenta del Banco de la Nación                │
│  • setPercent() → Porcentaje de detracción                      │
│  • setMount() → Monto calculado                                 │
│  • Agrega al XML en nodo <cac:PaymentTerms>                     │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                  XML GENERADO → SUNAT                           │
└─────────────────────────────────────────────────────────────────┘
```

### Archivos Involucrados

| Archivo | Responsabilidad | Líneas Clave |
|---------|----------------|--------------|
| `database/migrations/2025_09_01_122355_create_invoices_table.php` | Estructura de BD | 48, 61 |
| `app/Models/Invoice.php` | Modelo Eloquent | 41, 52, 92, 103 |
| `app/Http/Requests/StoreInvoiceRequest.php` | Validación | 70-76 |
| `app/Services/DocumentService.php` | Lógica de negocio | 943-949 |
| `app/Services/GreenterService.php` | Generación XML | 247-263 |
| `app/Services/BancarizacionService.php` | Leyenda bancarización | 102-108 |

---

## Estructura de Datos

### Base de Datos

#### Tabla: `invoices`

```sql
-- Campo para el monto calculado
mto_detraccion DECIMAL(12, 2) DEFAULT 0

-- Campo JSON para información completa
detraccion JSON NULL
```

**Ejemplo de datos almacenados:**

```sql
-- mto_detraccion
141.60

-- detraccion (JSON)
{
  "codigo_bien_servicio": "020",
  "codigo_medio_pago": "001",
  "cuenta_banco": "00000123456",
  "porcentaje": 12.0,
  "monto": 141.60
}
```

### Modelo Laravel (Invoice.php)

```php
// Campos fillable
protected $fillable = [
    'mto_detraccion',  // Monto de detracción (decimal)
    'detraccion',      // Objeto JSON con detalles
];

// Casts automáticos
protected $casts = [
    'mto_detraccion' => 'decimal:2',  // Convierte a decimal con 2 decimales
    'detraccion' => 'array',           // Convierte JSON a array de PHP
];
```

### Objeto Request (JSON)

```json
{
  "detraccion": {
    "codigo_bien_servicio": "020",
    "codigo_medio_pago": "001",
    "cuenta_banco": "00000123456",
    "porcentaje": 12.0,
    "monto": 141.60
  }
}
```

#### Campos del Objeto Detracción

| Campo | Tipo | Requerido | Descripción | Ejemplo |
|-------|------|-----------|-------------|---------|
| `codigo_bien_servicio` | string(3) | ✅ Sí | Código del bien/servicio (Catálogo 54) | "020" |
| `codigo_medio_pago` | string(3) | ❌ No | Medio de pago (Catálogo 59). Default: "001" | "001" |
| `cuenta_banco` | string(20) | ❌ No | Cuenta del Banco de la Nación del proveedor | "00000123456" |
| `porcentaje` | decimal | ✅ Sí | Porcentaje de detracción (0-100) | 12.0 |
| `monto` | decimal | ❌ No | Monto calculado de la detracción | 141.60 |

### XML Generado (UBL 2.1)

```xml
<cac:PaymentTerms>
    <cbc:ID>Detraccion</cbc:ID>
    <cbc:PaymentMeansID>001</cbc:PaymentMeansID>
    <cbc:PaymentPercent>12.0</cbc:PaymentPercent>
    <cbc:Amount currencyID="PEN">141.60</cbc:Amount>
</cac:PaymentTerms>

<cac:TaxTotal>
    <cac:TaxSubtotal>
        <cbc:Legend>
            <cbc:ID>2006</cbc:ID>
            <cbc:Value>Operación sujeta a detracción</cbc:Value>
        </cbc:Legend>
    </cac:TaxSubtotal>
</cac:TaxTotal>
```

---

## Catálogo de Bienes y Servicios

### Catálogo 54 - SUNAT (Actualizado)

#### Bienes

| Código | Descripción | Porcentaje |
|--------|-------------|------------|
| 001 | Azúcar y melaza de caña | 10% |
| 003 | Alcohol etílico | 10% |
| 005 | Maíz amarillo duro | 4% |
| 007 | Arena y piedra | 10% |
| 009 | Madera | 4% |
| 010 | Oro gravado con el IGV | 10% |
| 011 | Páprika y otros frutos de los géneros capsicum o pimienta | 10% |
| 012 | Espárragos | 10% |
| 014 | Carnes y despojos comestibles | 4% |
| 016 | Aceite de pescado | 9% |
| 017 | Harina, polvo y pellets de pescado, crustáceos | 9% |
| 019 | Minerales metálicos no auríferos | 10% |
| 020 | Bienes del inciso A) del Apéndice I de la Ley del IGV | 1.5% |
| 021 | Oro y demás minerales metálicos exonerados del IGV | 10% |
| 022 | Plomo | 15% |
| 040 | Algodón | 10% |

#### Servicios

| Código | Descripción | Porcentaje |
|--------|-------------|------------|
| 019 | Arrendamiento de bienes muebles e inmuebles | 10% |
| 020 | **Mantenimiento y reparación de bienes muebles** | **12%** |
| 021 | Movimiento de carga | 10% |
| 022 | **Otros servicios empresariales** | **12%** |
| 024 | **Intermediación laboral y tercerización** | **12%** |
| 025 | **Transporte de bienes por vía terrestre** | **4%** |
| 027 | Transporte público de pasajeros | 4% |
| 030 | **Contratos de construcción** | **4%** |
| 031 | Demás servicios gravados con el IGV | 12% |
| 032 | Fabricación de bienes por encargo | 10% |
| 033 | Servicio de transporte de personas | 4% |
| 034 | Contratos de construcción de inmuebles | 4% |
| 036 | Demás bienes gravados con el IGV | 10% |
| 037 | **Demás servicios gravados con el IGV** | **12%** |
| 040 | Algodón | 10% |

**Códigos Más Usados (resaltados en negrita)**

---

## Ejemplos Prácticos

### Ejemplo 1: Servicio de Mantenimiento

**Escenario:**
- Servicio: Mantenimiento de maquinaria industrial
- Código: 020 (Mantenimiento y reparación)
- Porcentaje: 12%

**Request:**

```json
POST /api/v1/invoices

{
  "company_id": 1,
  "branch_id": 1,
  "serie": "F001",
  "fecha_emision": "2025-11-26",
  "moneda": "PEN",
  "tipo_operacion": "1001",
  "forma_pago_tipo": "Contado",

  "client": {
    "tipo_documento": "6",
    "numero_documento": "20123456789",
    "razon_social": "INDUSTRIAS ABC SAC",
    "direccion": "Av. Industrial 456"
  },

  "detalles": [
    {
      "codigo": "MANT001",
      "descripcion": "Servicio de mantenimiento preventivo de maquinaria industrial",
      "unidad": "ZZ",
      "cantidad": 1,
      "mto_valor_unitario": 1000.00,
      "tip_afe_igv": "10",
      "porcentaje_igv": 18
    }
  ],

  "detraccion": {
    "codigo_bien_servicio": "020",
    "codigo_medio_pago": "001",
    "cuenta_banco": "00000123456",
    "porcentaje": 12.0,
    "monto": 141.60
  }
}
```

**Cálculo:**

```
Valor Venta:        S/ 1,000.00
IGV (18%):          S/   180.00
────────────────────────────────
Total Factura:      S/ 1,180.00

Detracción (12%):   S/ 1,180.00 × 12% = S/ 141.60
```

**Flujo de Pago:**

```
Cliente paga al proveedor:           S/ 1,038.40
Cliente deposita en Banco Nación:    S/   141.60
                                     ─────────────
Total:                               S/ 1,180.00
```

---

### Ejemplo 2: Transporte de Carga

**Escenario:**
- Servicio: Transporte de mercadería Lima-Arequipa
- Código: 025 (Transporte terrestre)
- Porcentaje: 4%

**Request:**

```json
{
  "company_id": 1,
  "branch_id": 1,
  "serie": "F001",
  "fecha_emision": "2025-11-26",
  "moneda": "PEN",
  "tipo_operacion": "1001",
  "forma_pago_tipo": "Credito",
  "fecha_vencimiento": "2025-12-26",
  "forma_pago_cuotas": [
    {
      "moneda": "PEN",
      "monto": 5900.00,
      "fecha_pago": "2025-12-26"
    }
  ],

  "client": {
    "tipo_documento": "6",
    "numero_documento": "20987654321",
    "razon_social": "DISTRIBUIDORA XYZ SAC"
  },

  "detalles": [
    {
      "codigo": "TRANS001",
      "descripcion": "Transporte de mercadería Lima-Arequipa (20 toneladas)",
      "unidad": "ZZ",
      "cantidad": 1,
      "mto_valor_unitario": 5000.00,
      "tip_afe_igv": "10",
      "porcentaje_igv": 18
    }
  ],

  "detraccion": {
    "codigo_bien_servicio": "025",
    "codigo_medio_pago": "001",
    "cuenta_banco": "00000987654",
    "porcentaje": 4.0,
    "monto": 236.00
  }
}
```

**Cálculo:**

```
Valor Venta:        S/ 5,000.00
IGV (18%):          S/   900.00
────────────────────────────────
Total Factura:      S/ 5,900.00

Detracción (4%):    S/ 5,900.00 × 4% = S/ 236.00
```

---

### Ejemplo 3: Construcción

**Escenario:**
- Servicio: Construcción de edificio (avance)
- Código: 030 (Contratos de construcción)
- Porcentaje: 4%

**Request:**

```json
{
  "company_id": 1,
  "branch_id": 1,
  "serie": "F001",
  "fecha_emision": "2025-11-26",
  "moneda": "PEN",
  "tipo_operacion": "1001",
  "forma_pago_tipo": "Credito",
  "fecha_vencimiento": "2025-12-31",
  "forma_pago_cuotas": [
    {
      "moneda": "PEN",
      "monto": 59000.00,
      "fecha_pago": "2025-12-31"
    }
  ],

  "client": {
    "tipo_documento": "6",
    "numero_documento": "20111222333",
    "razon_social": "INMOBILIARIA DEF SAC"
  },

  "detalles": [
    {
      "codigo": "CONST001",
      "descripcion": "Construcción de edificio multifamiliar - Avance 30%",
      "unidad": "ZZ",
      "cantidad": 1,
      "mto_valor_unitario": 50000.00,
      "tip_afe_igv": "10",
      "porcentaje_igv": 18
    }
  ],

  "detraccion": {
    "codigo_bien_servicio": "030",
    "codigo_medio_pago": "001",
    "cuenta_banco": "00000555666",
    "porcentaje": 4.0,
    "monto": 2360.00
  }
}
```

**Cálculo:**

```
Valor Venta:        S/ 50,000.00
IGV (18%):          S/  9,000.00
─────────────────────────────────
Total Factura:      S/ 59,000.00

Detracción (4%):    S/ 59,000.00 × 4% = S/ 2,360.00
```

---

### Ejemplo 4: Intermediación Laboral

**Escenario:**
- Servicio: Tercerización de personal
- Código: 024 (Intermediación laboral)
- Porcentaje: 12%

**Request:**

```json
{
  "company_id": 1,
  "branch_id": 1,
  "serie": "F001",
  "fecha_emision": "2025-11-26",
  "moneda": "PEN",
  "tipo_operacion": "1001",
  "forma_pago_tipo": "Contado",

  "client": {
    "tipo_documento": "6",
    "numero_documento": "20444555666",
    "razon_social": "EMPRESA MINERA GHI SAC"
  },

  "detalles": [
    {
      "codigo": "TERCER001",
      "descripcion": "Servicio de tercerización de personal - Mes noviembre 2025",
      "unidad": "ZZ",
      "cantidad": 1,
      "mto_valor_unitario": 15000.00,
      "tip_afe_igv": "10",
      "porcentaje_igv": 18
    }
  ],

  "detraccion": {
    "codigo_bien_servicio": "024",
    "codigo_medio_pago": "001",
    "cuenta_banco": "00000777888",
    "porcentaje": 12.0,
    "monto": 2124.00
  }
}
```

**Cálculo:**

```
Valor Venta:        S/ 15,000.00
IGV (18%):          S/  2,700.00
─────────────────────────────────
Total Factura:      S/ 17,700.00

Detracción (12%):   S/ 17,700.00 × 12% = S/ 2,124.00
```

---

### Ejemplo 5: Con Bancarización (Operación > S/ 3,500)

**Escenario:**
- Servicio: Limpieza industrial
- Código: 037 (Demás servicios)
- Porcentaje detracción: 12%
- Aplica bancarización: Sí (monto > S/ 3,500)

**Request:**

```json
{
  "company_id": 1,
  "branch_id": 1,
  "serie": "F001",
  "fecha_emision": "2025-11-26",
  "moneda": "PEN",
  "tipo_operacion": "1001",
  "forma_pago_tipo": "Contado",

  "client": {
    "tipo_documento": "6",
    "numero_documento": "20333444555",
    "razon_social": "SERVICIOS JKL SAC"
  },

  "detalles": [
    {
      "codigo": "LIMP001",
      "descripcion": "Servicio de limpieza industrial mensual",
      "unidad": "ZZ",
      "cantidad": 1,
      "mto_valor_unitario": 8000.00,
      "tip_afe_igv": "10",
      "porcentaje_igv": 18
    }
  ],

  "detraccion": {
    "codigo_bien_servicio": "037",
    "codigo_medio_pago": "001",
    "cuenta_banco": "00000999000",
    "porcentaje": 12.0,
    "monto": 1132.80
  },

  "bancarizacion": {
    "medio_pago": "Transferencia Bancaria",
    "numero_operacion": "TRF-20251126-789012",
    "fecha_pago": "2025-11-26",
    "banco": "BCP",
    "observaciones": "Pago por transferencia bancaria"
  }
}
```

**Cálculo:**

```
Valor Venta:        S/ 8,000.00
IGV (18%):          S/ 1,440.00
─────────────────────────────────
Total Factura:      S/ 9,440.00

Detracción (12%):   S/ 9,440.00 × 12% = S/ 1,132.80
```

**Leyendas Generadas:**

```
1000: NUEVE MIL CUATROCIENTOS CUARENTA CON 00/100 SOLES
2006: Operación sujeta a detracción
2005: OPERACIÓN SUJETA A BANCARIZACIÓN - LEY N° 28194
```

---

## Validaciones y Reglas

### Validaciones en la API

#### 1. Validación de Estructura (StoreInvoiceRequest)

```php
// Detracción (opcional)
'detraccion' => 'nullable|array',
'detraccion.codigo_bien_servicio' => 'required_with:detraccion|string|max:3',
'detraccion.codigo_medio_pago' => 'nullable|string|max:3',
'detraccion.cuenta_banco' => 'nullable|string|max:20',
'detraccion.porcentaje' => 'required_with:detraccion|numeric|min:0|max:100',
'detraccion.monto' => 'nullable|numeric|min:0',
```

#### 2. Validación SUNAT

✅ **El campo `tipo_operacion` DEBE ser "1001"** cuando hay detracción

```json
{
  "tipo_operacion": "1001",  // ← OBLIGATORIO con detracción
  "detraccion": { ... }
}
```

❌ **Error si usas tipo_operacion incorrecto:**

```
Error 3128: El XML contiene información de codigo de bien y servicio
de detracción que no corresponde al tipo de operación.
```

### Reglas de Negocio

#### 1. ¿Cuándo Aplicar Detracción?

**Aplica detracción cuando:**

✅ El bien/servicio está en el Catálogo 54 de SUNAT
✅ El monto de la operación supera los umbrales establecidos:
   - **S/ 700** para la mayoría de servicios
   - **S/ 400** para transporte de bienes

✅ Ambas partes (comprador y vendedor) tienen RUC

**NO aplica detracción cuando:**

❌ La operación es menor al umbral
❌ El comprador no tiene RUC (consumidor final)
❌ El bien/servicio no está sujeto a detracción

#### 2. Cálculo del Monto

```
Monto Detracción = Total de la Factura (incluido IGV) × Porcentaje

Ejemplos:
- S/ 1,180.00 × 12% = S/ 141.60
- S/ 5,900.00 × 4%  = S/ 236.00
```

#### 3. Plazo para el Depósito

El **cliente** debe depositar la detracción en el Banco de la Nación:

- **Hasta 5 días hábiles** después de:
  - El pago parcial o total, o
  - La entrega del bien/prestación del servicio

Lo que ocurra **primero**.

#### 4. Cuenta del Banco de la Nación

- El **proveedor** debe tener cuenta de detracciones en el Banco de la Nación
- Formato: 11 dígitos (ej: `00000123456`)
- Se abre automáticamente al inscribirse en SUNAT

---

## Errores Comunes

### Error 1: Leyendas Duplicadas

**Síntoma:**

```json
{
  "error_code": "3014",
  "message": "El codigo de leyenda no debe repetirse en el comprobante"
}
```

**Causa:**

Detracción y bancarización usaban el mismo código de leyenda (`2006`).

**Solución:**

✅ **CORREGIDO** en `app/Services/BancarizacionService.php`:

```php
// ANTES (ERROR)
'code' => '2006',  // ❌ Duplicado

// AHORA (CORRECTO)
'code' => '2005',  // ✅ Único
```

**Códigos correctos:**

- Detracción: `2006`
- Bancarización: `2005`

---

### Error 2: Tipo de Operación Incorrecto

**Síntoma:**

```json
{
  "error_code": "3128",
  "message": "El XML contiene información de codigo de bien y servicio
             de detracción que no corresponde al tipo de operación"
}
```

**Causa:**

No se especificó `tipo_operacion: "1001"` en el request.

**Solución:**

```json
{
  "tipo_operacion": "1001",  // ← AGREGAR ESTE CAMPO
  "detraccion": { ... }
}
```

---

### Error 3: Código de Bien/Servicio Inválido

**Síntoma:**

SUNAT rechaza el documento por código incorrecto.

**Causa:**

Usaste un código que no existe en el Catálogo 54 o que no corresponde al servicio.

**Solución:**

Verifica el código correcto en la tabla de [Catálogo de Bienes y Servicios](#catálogo-de-bienes-y-servicios).

**Ejemplo:**

```
❌ "codigo_bien_servicio": "999"  // No existe
✅ "codigo_bien_servicio": "020"  // Mantenimiento
```

---

### Error 4: Porcentaje Incorrecto

**Síntoma:**

SUNAT rechaza porque el porcentaje no coincide con el código.

**Causa:**

Cada código tiene un porcentaje fijo establecido por SUNAT.

**Solución:**

```
Código 020 → 12% ✅
Código 025 → 4%  ✅
Código 030 → 4%  ✅

❌ NO puedes usar:
Código 020 → 10% (ERROR)
```

---

### Error 5: Cuenta Bancaria Incorrecta

**Síntoma:**

El depósito no llega a la cuenta del proveedor.

**Causa:**

Número de cuenta incorrecto o con formato errado.

**Solución:**

- Verificar que el proveedor tenga cuenta de detracciones
- Formato: 11 dígitos numéricos
- Ejemplo: `00000123456`

---

## Flujo Completo

### Diagrama de Flujo End-to-End

```
┌──────────────────────────────────────────────────────────────────┐
│ 1. EMISOR CREA FACTURA CON DETRACCIÓN                           │
└──────────────────────────────────────────────────────────────────┘
        │
        ├─→ POST /api/v1/invoices
        │   {
        │     "tipo_operacion": "1001",
        │     "detraccion": {
        │       "codigo_bien_servicio": "020",
        │       "porcentaje": 12.0,
        │       "cuenta_banco": "00000123456"
        │     }
        │   }
        │
        ↓
┌──────────────────────────────────────────────────────────────────┐
│ 2. API PROCESA Y VALIDA                                          │
└──────────────────────────────────────────────────────────────────┘
        │
        ├─→ Valida estructura (StoreInvoiceRequest)
        ├─→ Calcula totales (DocumentService)
        ├─→ Genera leyenda 2006 (generateLegends)
        ├─→ Guarda en BD (invoices table)
        │   • mto_detraccion: 141.60
        │   • detraccion: { JSON }
        │
        ↓
┌──────────────────────────────────────────────────────────────────┐
│ 3. SE GENERA XML UBL 2.1                                         │
└──────────────────────────────────────────────────────────────────┘
        │
        ├─→ GreenterService crea objeto Detraction
        ├─→ Agrega nodo <cac:PaymentTerms>
        ├─→ Agrega leyenda <cbc:Legend code="2006">
        │
        ↓
┌──────────────────────────────────────────────────────────────────┐
│ 4. SE ENVÍA A SUNAT                                              │
└──────────────────────────────────────────────────────────────────┘
        │
        ├─→ POST /api/v1/invoices/{id}/send-sunat
        ├─→ SUNAT valida tipo_operacion = 1001
        ├─→ SUNAT valida código y porcentaje
        ├─→ SUNAT acepta o rechaza
        │
        ↓
┌──────────────────────────────────────────────────────────────────┐
│ 5. CLIENTE REALIZA PAGOS (MUNDO REAL)                            │
└──────────────────────────────────────────────────────────────────┘
        │
        ├─→ Pago al proveedor: S/ 1,038.40
        ├─→ Depósito Banco Nación: S/ 141.60
        │   (Formulario 1662, plazo: 5 días hábiles)
        │
        ↓
┌──────────────────────────────────────────────────────────────────┐
│ 6. PROVEEDOR USA FONDOS PARA TRIBUTOS                            │
└──────────────────────────────────────────────────────────────────┘
        │
        └─→ Pago de IGV, Renta, multas, etc.
```

---

## Preguntas Frecuentes (FAQ)

### 1. ¿Es obligatorio incluir la cuenta bancaria?

**No** es obligatorio en el XML, pero **sí es necesario** para que el cliente sepa dónde depositar.

```json
{
  "cuenta_banco": "00000123456"  // Recomendado incluir
}
```

### 2. ¿Puedo usar diferentes porcentajes para el mismo código?

**No**. Cada código tiene un porcentaje fijo establecido por SUNAT. No puedes cambiarlo.

### 3. ¿Qué pasa si el cliente no deposita la detracción?

- El cliente es **responsable solidario** del tributo
- SUNAT puede cobrarle directamente al cliente
- Multas e intereses por incumplimiento

### 4. ¿Puedo anular una factura con detracción?

**Sí**, pero:
- Si ya se depositó la detracción, se debe devolver o usar para otras operaciones
- Seguir proceso normal de comunicación de baja

### 5. ¿La detracción aplica a boletas?

**No**. La detracción solo aplica a **facturas** entre empresas (B2B).

### 6. ¿Cómo calcular el monto si hay descuentos?

```
Detracción = (Total Factura - Descuentos) × Porcentaje
```

### 7. ¿Aplica detracción en exportaciones?

**No**. Las exportaciones no están sujetas a detracción.

### 8. ¿Qué pasa si me equivoco en el porcentaje?

SUNAT rechazará el comprobante. Debes corregir y reenviar.

### 9. ¿Puedo tener detracción y percepción en la misma factura?

**Sí**, es posible pero poco común. Son mecanismos diferentes.

### 10. ¿Dónde consulto los códigos actualizados?

- Web oficial: [sunat.gob.pe](https://www.sunat.gob.pe)
- Buscar: "Catálogo 54 - Bienes y servicios sujetos a detracción"

---

## Checklist de Implementación

### ✅ Para Desarrolladores

- [ ] Incluir campo `tipo_operacion: "1001"` cuando hay detracción
- [ ] Validar código de bien/servicio del Catálogo 54
- [ ] Calcular monto correctamente: `Total × Porcentaje`
- [ ] Generar leyenda `2006` automáticamente
- [ ] No duplicar códigos de leyenda con bancarización
- [ ] Guardar datos completos en campo JSON `detraccion`
- [ ] Verificar que el porcentaje coincida con el código

### ✅ Para Testers

- [ ] Probar con diferentes códigos de servicio
- [ ] Verificar que XML incluya `<cac:PaymentTerms>`
- [ ] Confirmar que SUNAT acepte el comprobante
- [ ] Validar cálculos de detracción
- [ ] Probar casos con y sin bancarización
- [ ] Verificar leyendas no duplicadas

### ✅ Para Clientes/Usuarios

- [ ] Solicitar cuenta de detracciones del proveedor
- [ ] Verificar que el servicio esté sujeto a detracción
- [ ] Calcular correctamente el monto a depositar
- [ ] Depositar dentro del plazo (5 días hábiles)
- [ ] Conservar constancia de depósito
- [ ] Verificar que el proveedor reciba los fondos

---

## Recursos Adicionales

### Documentación Oficial SUNAT

- [Resoluciones sobre SPOT](https://www.sunat.gob.pe/legislacion/spotindex.html)
- [Catálogo 54 - Bienes y servicios](https://cpe.sunat.gob.pe/sites/default/files/inline-files/Catalogo_54.xls)
- [Catálogo 51 - Tipo de operación](https://cpe.sunat.gob.pe/sites/default/files/inline-files/Catalogo_51.xls)
- [Catálogo 52 - Leyendas](https://cpe.sunat.gob.pe/sites/default/files/inline-files/Catalogo_52.xls)
- [Catálogo 59 - Medio de pago](https://cpe.sunat.gob.pe/sites/default/files/inline-files/Catalogo_59.xls)

### Herramientas

- **Banco de la Nación**: Apertura de cuenta de detracciones
- **SUNAT Virtual**: Consulta de operaciones
- **SOL**: Sistema de Operaciones en Línea

### Soporte Técnico

Para dudas sobre la implementación:
- Revisar logs en `storage/logs/laravel.log`
- Verificar XML generado en la carpeta de XMLs
- Consultar respuesta de SUNAT en campo `respuesta_sunat`

---

## Glosario

| Término | Definición |
|---------|------------|
| **SPOT** | Sistema de Pago de Obligaciones Tributarias con el Gobierno Central |
| **Detracción** | Mecanismo de retención del IGV aplicado a ciertos bienes y servicios |
| **UBL** | Universal Business Language - Estándar XML para documentos electrónicos |
| **Catálogo 54** | Lista oficial de bienes y servicios sujetos a detracción |
| **Formulario 1662** | Formulario para depósitos de detracción en el Banco de la Nación |
| **CDR** | Constancia de Recepción - Respuesta de SUNAT |

---

## Historial de Cambios

| Fecha | Versión | Cambios |
|-------|---------|---------|
| 2025-12-04 | 1.0.0 | Documentación inicial completa |
| 2025-12-04 | 1.0.1 | Corrección de código de leyenda bancarización (2006 → 2005) |

---

## Licencia y Uso

Este documento es parte de la documentación del proyecto de facturación electrónica.

**Autor**: Sistema de Facturación Electrónica SUNAT
**Última actualización**: 04 de diciembre de 2025

---

## Contacto y Soporte

Para consultas técnicas sobre esta implementación, contactar al equipo de desarrollo del proyecto.

Para consultas normativas sobre detracciones, contactar a SUNAT:
- **Teléfono**: 0-801-12-100
- **Web**: [www.sunat.gob.pe](https://www.sunat.gob.pe)

---

**FIN DE LA DOCUMENTACIÓN**

# Guía de Precios con/sin IGV - Sistema de Facturación Electrónica

## Índice
- [Introducción](#introducción)
- [Campos de Precio](#campos-de-precio)
- [Modos de Operación](#modos-de-operación)
- [Cuándo Usar Cada Campo](#cuándo-usar-cada-campo)
- [Ejemplos Prácticos](#ejemplos-prácticos)
- [Comprensión de "Operaciones Gravadas"](#comprensión-de-operaciones-gravadas)
- [Códigos de Afectación IGV](#códigos-de-afectación-igv)
- [FAQ - Preguntas Frecuentes](#faq---preguntas-frecuentes)

---

## Introducción

El sistema de facturación electrónica soporta **DOS MODOS** de ingreso de precios:

1. **MODO RETAIL (Precio CON IGV)**: El precio que ingresas YA incluye el IGV
2. **MODO MAYORISTA (Precio SIN IGV)**: El precio que ingresas NO incluye IGV, se suma después

Esto permite que el sistema se adapte tanto a negocios retail (tiendas, restaurantes) como a negocios mayoristas o B2B.

---

## Campos de Precio

### `mto_precio_unitario` (Precio CON IGV)
- **Definición**: Precio unitario que YA incluye el IGV
- **Uso**: Cuando vendes al público y el precio final ya está definido
- **Ejemplo**: Vendes una laptop a S/ 299.00 (precio final con IGV incluido)
- **Cálculo automático**: El sistema calcula hacia atrás para obtener el valor base sin IGV

### `mto_valor_unitario` (Precio SIN IGV)
- **Definición**: Valor unitario SIN incluir el IGV (valor base o base imponible)
- **Uso**: Cuando vendes a empresas o manejas precios sin IGV
- **Ejemplo**: Vendes un producto valorizado en S/ 100.00 + IGV (S/ 18.00) = Total S/ 118.00
- **Cálculo automático**: El sistema suma el IGV al valor base para obtener el precio final

---

## Modos de Operación

### MODO RETAIL (Recomendado para ventas al público)

**Campo a enviar**: `mto_precio_unitario`

```json
{
  "detalles": [
    {
      "codigo": "LAPTOP001",
      "descripcion": "Laptop HP Pavilion 15.6",
      "unidad": "NIU",
      "cantidad": 1,
      "mto_precio_unitario": 299.00,  // ← Precio FINAL con IGV
      "tip_afe_igv": "10",
      "porcentaje_igv": 18
    }
  ]
}
```

**Cálculo automático del sistema**:
```
Precio ingresado (CON IGV): S/ 299.00
÷ 1.18 (para quitar el IGV)
= Valor base: S/ 253.39
× 18% IGV = S/ 45.61
━━━━━━━━━━━━━━━━━━━━━━━━
Total: S/ 299.00 ✓
```

**Resultado en el PDF**:
- **Tabla de productos**: Muestra S/ 299.00 (precio que el cliente paga)
- **Ope. Gravadas**: S/ 253.39 (base imponible - requerido por SUNAT)
- **IGV 18%**: S/ 45.61
- **Total**: S/ 299.00

---

### MODO MAYORISTA (Recomendado para B2B)

**Campo a enviar**: `mto_valor_unitario`

```json
{
  "detalles": [
    {
      "codigo": "LAPTOP001",
      "descripcion": "Laptop HP Pavilion 15.6",
      "unidad": "NIU",
      "cantidad": 1,
      "mto_valor_unitario": 253.39,  // ← Valor base SIN IGV
      "tip_afe_igv": "10",
      "porcentaje_igv": 18
    }
  ]
}
```

**Cálculo automático del sistema**:
```
Valor base (SIN IGV): S/ 253.39
× 18% IGV = S/ 45.61
━━━━━━━━━━━━━━━━━━━━━━━━
Total: S/ 299.00 ✓
```

**Resultado en el PDF**: Igual que el modo retail (el resultado final es idéntico)

---

## Cuándo Usar Cada Campo

### ✅ USA `mto_precio_unitario` (RETAIL - Precio CON IGV)

| Tipo de Negocio | Razón |
|-----------------|-------|
| **Tiendas retail** | Los precios en góndola ya incluyen IGV |
| **Restaurantes** | Los precios del menú son finales |
| **Cafeterías** | El cliente paga exactamente lo que ve en la carta |
| **Farmacias** | Precios de mostrador incluyen IGV |
| **Supermercados** | Todos los productos tienen precio final |
| **E-commerce** | Los precios web son finales para el consumidor |

**Ventaja**: No necesitas calcular el IGV manualmente, solo ingresas el precio final que cobra el cliente.

---

### ✅ USA `mto_valor_unitario` (MAYORISTA - Precio SIN IGV)

| Tipo de Negocio | Razón |
|-----------------|-------|
| **Distribuidores** | Manejan precios base + IGV |
| **Mayoristas** | Venden a empresas que deducen IGV |
| **Importadores** | Costos de importación + IGV |
| **Servicios profesionales** | Honorarios base + IGV |
| **Constructoras** | Presupuestos sin IGV + IGV |
| **Software B2B** | Licencias valoradas sin IGV |

**Ventaja**: Separación clara entre valor del producto/servicio y el impuesto, útil para contabilidad empresarial.

---

## Ejemplos Prácticos

### Ejemplo 1: Bodega (RETAIL)

**Situación**: Una bodega vende una gaseosa a S/ 2.50 (precio final en el estante)

```json
{
  "detalles": [
    {
      "codigo": "BEB001",
      "descripcion": "Gaseosa Inca Kola 500ml",
      "unidad": "NIU",
      "cantidad": 1,
      "mto_precio_unitario": 2.50,  // ← Precio final
      "tip_afe_igv": "10",
      "porcentaje_igv": 18
    }
  ]
}
```

**Sistema calcula**:
- Valor base: S/ 2.12 (2.50 ÷ 1.18)
- IGV 18%: S/ 0.38
- Total: S/ 2.50

**En el PDF**:
- Tabla productos: S/ 2.50 ✓
- Ope. Gravadas: S/ 2.12 (base imponible)
- IGV: S/ 0.38
- Total a Pagar: S/ 2.50 ✓

---

### Ejemplo 2: Restaurante (RETAIL)

**Situación**: Un restaurante cobra S/ 45.00 por un menú ejecutivo (precio en carta)

```json
{
  "detalles": [
    {
      "codigo": "MENU001",
      "descripcion": "Menú Ejecutivo - Lomo Saltado",
      "unidad": "ZZ",
      "cantidad": 1,
      "mto_precio_unitario": 45.00,  // ← Precio en carta
      "tip_afe_igv": "10",
      "porcentaje_igv": 18
    }
  ]
}
```

**Sistema calcula**:
- Valor base: S/ 38.14 (45.00 ÷ 1.18)
- IGV 18%: S/ 6.86
- Total: S/ 45.00

---

### Ejemplo 3: Empresa de Software B2B (MAYORISTA)

**Situación**: Una empresa vende licencia de software valorizada en S/ 1,000.00 + IGV a otra empresa

```json
{
  "detalles": [
    {
      "codigo": "LIC001",
      "descripcion": "Licencia Software ERP Anual",
      "unidad": "ZZ",
      "cantidad": 1,
      "mto_valor_unitario": 1000.00,  // ← Valor sin IGV
      "tip_afe_igv": "10",
      "porcentaje_igv": 18
    }
  ]
}
```

**Sistema calcula**:
- Valor base: S/ 1,000.00
- IGV 18%: S/ 180.00
- Total: S/ 1,180.00

**En el PDF**:
- Tabla productos: S/ 1,180.00 (precio final calculado)
- Ope. Gravadas: S/ 1,000.00
- IGV: S/ 180.00
- Total a Pagar: S/ 1,180.00

---

### Ejemplo 4: Producto Exonerado de IGV (Libros, Alimentos de la Canasta Básica)

**Situación**: Librería vende un libro a S/ 50.00 (exonerado de IGV según Ley)

```json
{
  "detalles": [
    {
      "codigo": "LIB001",
      "descripcion": "Libro: Cien Años de Soledad",
      "unidad": "NIU",
      "cantidad": 1,
      "mto_precio_unitario": 50.00,  // ← Precio = Valor (no hay IGV)
      "tip_afe_igv": "20",  // ← EXONERADO
      "porcentaje_igv": 0
    }
  ]
}
```

**Sistema calcula**:
- Valor base: S/ 50.00 (no se divide porque no hay IGV)
- IGV: S/ 0.00
- Total: S/ 50.00

**En el PDF**:
- Tabla productos: S/ 50.00
- Ope. Exoneradas: S/ 50.00 (no aparece "gravadas")
- IGV: S/ 0.00
- Total a Pagar: S/ 50.00

---

## Comprensión de "Operaciones Gravadas"

### ¿Por qué en el PDF aparece un valor menor en "Ope. Gravadas"?

**Es CORRECTO y OBLIGATORIO según SUNAT.**

```
┌─────────────────────────────────────────┐
│  TABLA DE PRODUCTOS (PDF)               │
│  ────────────────────────────────────   │
│  Laptop HP   1.00   299.00   299.00     │  ← Precio CON IGV (lo que paga el cliente)
└─────────────────────────────────────────┘

┌─────────────────────────────────────────┐
│  RESUMEN TRIBUTARIO (PDF)               │
│  ────────────────────────────────────   │
│  Ope. Gravadas:      S/ 253.39          │  ← Base imponible SIN IGV (requerido por SUNAT)
│  IGV 18%:            S/  45.61          │
│  ─────────────────────────────────────  │
│  TOTAL A PAGAR:      S/ 299.00          │  ← Total FINAL (igual al precio)
└─────────────────────────────────────────┘
```

### Explicación:

- **"Operaciones Gravadas"** = Base imponible (valor SIN IGV)
- Es un **requisito legal de SUNAT** para calcular correctamente los impuestos
- **NO es un error**, todas las empresas en Perú deben mostrarlo así
- El cliente final **SÍ paga S/ 299.00** (el total correcto)

### Ejemplo comparativo:

| Concepto | Valor |
|----------|-------|
| Precio final del producto (CON IGV) | S/ 299.00 |
| **Operaciones Gravadas** (base sin IGV) | S/ 253.39 |
| IGV 18% (calculado sobre la base) | S/ 45.61 |
| **TOTAL A PAGAR** | S/ 299.00 ✓ |

**Conclusión**: El total que paga el cliente siempre es correcto (S/ 299.00). "Operaciones Gravadas" es solo un dato tributario interno.

---

## Códigos de Afectación IGV

El campo `tip_afe_igv` determina cómo se aplica el IGV:

| Código | Descripción | Cuándo Usar |
|--------|-------------|-------------|
| **10** | Gravado - Operación Onerosa | Ventas normales con IGV (mayoría de productos/servicios) |
| **20** | Exonerado - Operación Onerosa | Libros, canasta básica, servicios educativos (Ley específica) |
| **30** | Inafecto - Operación Onerosa | Alimentos naturales sin procesar, servicios prestados en extranjero |
| **40** | Exportación | Venta de bienes/servicios al exterior |
| **11** | Gravado - Retiro por premio | Entrega de productos como premio (IGV aplicable) |
| **17** | Gravado - IVAP (Arroz pilado) | Impuesto específico para arroz pilado |
| **21** | Exonerado - Transferencia Gratuita | Muestras gratis, donaciones exoneradas |
| **31** | Inafecto - Retiro por Bonificación | Bonificaciones o descuentos en productos |
| **32** | Inafecto - Retiro | Retiro de bienes por otros motivos |

**Más común**: `tip_afe_igv: "10"` (Gravado - Operación Onerosa) - Representa el 95% de las ventas normales.

---

## FAQ - Preguntas Frecuentes

### 1. ¿Puedo enviar ambos campos `mto_precio_unitario` y `mto_valor_unitario` a la vez?

**NO.** Debes enviar **solo uno de los dos**:
- Si envías `mto_precio_unitario`, el sistema calcula automáticamente `mto_valor_unitario`
- Si envías `mto_valor_unitario`, el sistema calcula automáticamente `mto_precio_unitario`

La validación del sistema requiere uno u otro (`required_without`).

---

### 2. ¿Qué pasa si mi producto NO tiene IGV (exonerado/inafecto)?

Usa cualquiera de los dos campos, **el resultado será el mismo**:

```json
{
  "mto_precio_unitario": 50.00,  // O mto_valor_unitario: 50.00
  "tip_afe_igv": "20",  // Exonerado
  "porcentaje_igv": 0
}
```

Como no hay IGV (0%), el precio = valor, no importa qué campo uses.

---

### 3. ¿El PDF siempre muestra el precio con IGV en la tabla de productos?

**SÍ.** Desde la última actualización, el PDF muestra `mto_precio_unitario` (precio final CON IGV) en la tabla de productos, sin importar qué campo enviaste.

Esto es para que el cliente vea el precio real que está pagando.

---

### 4. ¿Por qué "Ope. Gravadas" muestra un valor menor al total?

Porque **"Operaciones Gravadas"** es el **valor base SIN IGV** (base imponible), no el total.

Es un **requisito de SUNAT** para desglosar correctamente los impuestos. El total correcto aparece en **"TOTAL A PAGAR"**.

---

### 5. ¿Puedo usar `mto_precio_unitario` para facturas B2B?

**SÍ, puedes usarlo**, pero la mayoría de empresas B2B prefieren trabajar con `mto_valor_unitario` porque:
- Facilita la contabilidad (separación clara entre valor e impuesto)
- Es el estándar en cotizaciones empresariales
- Permite ver claramente el IGV deducible

---

### 6. ¿Cómo sé cuál usar en mi negocio?

**Regla simple**:
- ¿Tus precios de venta ya incluyen IGV? → Usa `mto_precio_unitario` (RETAIL)
- ¿Manejas precios base + IGV aparte? → Usa `mto_valor_unitario` (MAYORISTA)

---

### 7. ¿El cálculo tiene precisión suficiente?

**SÍ.** El sistema usa **10 decimales** en cálculos intermedios y redondea a **2 decimales** en el resultado final, cumpliendo con las normas de SUNAT.

Ejemplo:
```
299.00 ÷ 1.18 = 253.3898305084... (10 decimales)
Redondeo: 253.39 (2 decimales) ✓
```

---

### 8. ¿Qué pasa con productos mixtos (algunos con IGV, otros sin IGV)?

Puedes mezclar en la misma factura:

```json
{
  "detalles": [
    {
      "descripcion": "Laptop",
      "mto_precio_unitario": 299.00,
      "tip_afe_igv": "10",  // CON IGV
      "porcentaje_igv": 18
    },
    {
      "descripcion": "Libro",
      "mto_precio_unitario": 50.00,
      "tip_afe_igv": "20",  // SIN IGV (exonerado)
      "porcentaje_igv": 0
    }
  ]
}
```

El sistema calculará correctamente:
- Ope. Gravadas: S/ 253.39 (laptop)
- Ope. Exoneradas: S/ 50.00 (libro)
- IGV: S/ 45.61
- Total: S/ 349.00

---

### 9. ¿Funciona igual para Facturas, Boletas y Notas de Venta?

**SÍ.** El sistema de doble precio funciona idéntico para:
- Facturas (01)
- Boletas (03)
- Notas de Venta (NV - documento interno)

Todos aceptan ambos modos de ingreso.

---

### 10. ¿Hay alguna penalización fiscal por usar uno u otro?

**NO.** Ambos métodos son fiscalmente equivalentes y **generan el mismo XML y PDF final**.

La diferencia es solo en la **forma de ingreso** de datos, no en el resultado fiscal.

---

## Resumen Ejecutivo

| Aspecto | `mto_precio_unitario` | `mto_valor_unitario` |
|---------|----------------------|---------------------|
| **Significado** | Precio CON IGV | Precio SIN IGV (base) |
| **Uso típico** | Retail, ventas al público | Mayorista, B2B |
| **Cálculo** | Sistema quita el IGV (÷ 1.18) | Sistema suma el IGV (× 1.18) |
| **Ejemplo** | S/ 299.00 (precio final) | S/ 253.39 (+ S/ 45.61 IGV = S/ 299.00) |
| **Ventaja** | Más intuitivo para ventas retail | Más claro para contabilidad empresarial |
| **Resultado final** | Idéntico (mismo XML, mismo PDF) | Idéntico (mismo XML, mismo PDF) |

---

## Documentación Relacionada

- **Normativa SUNAT**: Resolución de Superintendencia N° 097-2012/SUNAT (Facturación Electrónica)
- **Ley de IGV**: Decreto Supremo N° 055-99-EF (Texto Único Ordenado de la Ley del IGV)
- **Catálogo de Afectación IGV**: SUNAT Catálogo No. 07 - Códigos de tipo de afectación del IGV

---

**Última actualización**: Diciembre 2025
**Versión del sistema**: 2.0
**Autor**: Sistema de Facturación Electrónica - GO SUNAT PERU

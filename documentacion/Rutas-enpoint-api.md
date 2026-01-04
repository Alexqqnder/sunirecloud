# API DE FACTURACIÓN ELECTRÓNICA SUNAT PERÚ - DOCUMENTACIÓN DE RUTAS

> **Versión:** 1.0
> **Base URL:** `http://tu-dominio.com/api`
> **Autenticación:** Bearer Token (Laravel Sanctum)

---

## ÍNDICE

1. [Rutas Públicas](#-rutas-públicas-sin-autenticación)
2. [Autenticación y Usuario](#-autenticación-y-usuario)
3. [Dashboard y Estadísticas](#-dashboard-y-estadísticas)
4. [Webhooks](#-webhooks)
5. [Setup del Sistema](#️-setup-del-sistema)
6. [Ubigeos](#-ubigeos)
7. [Empresas](#-empresas)
8. [Configuraciones de Empresas](#️-configuraciones-de-empresas)
9. [Credenciales GRE](#-credenciales-gre)
10. [Sucursales](#-sucursales)
11. [Clientes](#-clientes)
12. [Correlativos](#-correlativos)
13. [PDF](#-pdf)
14. [Facturas](#-facturas)
15. [Boletas](#-boletas)
16. [Resúmenes Diarios](#-resúmenes-diarios)
17. [Notas de Crédito](#-notas-de-crédito)
18. [Notas de Débito](#-notas-de-débito)
19. [Retenciones](#-retenciones)
20. [Comunicaciones de Baja](#-comunicaciones-de-baja)
21. [Guías de Remisión](#-guías-de-remisión)
22. [Consulta CPE](#-consulta-cpe)
23. [Bancarización](#-bancarización)
24. [Catálogos SUNAT](#-catálogos-sunat)

---

## 🔓 RUTAS PÚBLICAS (Sin Autenticación)

Estas rutas no requieren token de autenticación.

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/system/info` | Información del sistema |
| `GET` | `/health` | Health check del servidor |
| `GET` | `/ping` | Ping simple |
| `POST` | `/setup/migrate` | Ejecutar migraciones de BD |
| `POST` | `/setup/seed` | Ejecutar seeders de BD |
| `GET` | `/setup/status` | Estado del setup inicial |
| `POST` | `/auth/initialize` | Inicializar sistema (crear admin) |
| `POST` | `/auth/login` | Iniciar sesión |

### Ejemplos

#### Login
```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "admin@example.com",
    "password": "password123"
}
```

**Respuesta exitosa:**
```json
{
    "success": true,
    "token": "1|abc123...",
    "user": {
        "id": 1,
        "name": "Admin",
        "email": "admin@example.com"
    }
}
```

---

## 👤 AUTENTICACIÓN Y USUARIO

> **Prefijo:** `/api/v1`
> **Autenticación:** Requerida

| Método | Ruta | Descripción |
|--------|------|-------------|
| `POST` | `/v1/auth/logout` | Cerrar sesión |
| `GET` | `/v1/auth/me` | Obtener usuario autenticado |
| `POST` | `/v1/auth/create-user` | Crear nuevo usuario |
| `GET` | `/v1/user` | Obtener datos del usuario |

### Ejemplos

#### Obtener usuario actual
```http
GET /api/v1/auth/me
Authorization: Bearer {token}
```

#### Crear usuario
```http
POST /api/v1/auth/create-user
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "Nuevo Usuario",
    "email": "nuevo@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

---

## 📊 DASHBOARD Y ESTADÍSTICAS

> **Prefijo:** `/api/v1/dashboard`
> **Autenticación:** Requerida

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/v1/dashboard/statistics` | Estadísticas generales |
| `GET` | `/v1/dashboard/monthly-summary` | Resumen mensual de documentos |
| `GET` | `/v1/dashboard/client-statistics` | Estadísticas por cliente |
| `GET` | `/v1/dashboard/requires-resend` | Documentos que requieren reenvío |
| `GET` | `/v1/dashboard/expired-certificates` | Certificados vencidos o por vencer |

### Parámetros de Filtro Comunes

Todos los endpoints del dashboard soportan filtros por empresa y sucursal:

| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `company_id` | int | ID de la empresa. Si no se envía, muestra todas las empresas. |
| `branch_id` | int | ID de la sucursal. Si no se envía, muestra todas las sucursales. |

### Ejemplos

#### Estadísticas generales (todas las empresas)
```http
GET /api/v1/dashboard/statistics
Authorization: Bearer {token}
```

#### Estadísticas por empresa específica
```http
GET /api/v1/dashboard/statistics?company_id=1
Authorization: Bearer {token}
```

#### Estadísticas por empresa y sucursal específica
```http
GET /api/v1/dashboard/statistics?company_id=1&branch_id=2
Authorization: Bearer {token}
```

#### Estadísticas con rango de fechas
```http
GET /api/v1/dashboard/statistics?company_id=1&branch_id=2&start_date=2025-01-01&end_date=2025-12-31
Authorization: Bearer {token}
```

**Respuesta:**
```json
{
    "success": true,
    "data": {
        "filters": {
            "company_id": 1,
            "branch_id": 2,
            "start_date": "2025-01-01",
            "end_date": "2025-12-31"
        },
        "totals_pen": {
            "total_documentos": 150,
            "aceptados": 145,
            "rechazados": 2,
            "pendientes": 3,
            "total_monto": 125000.50,
            "total_igv": 22500.09,
            "total_gravable": 102500.41
        },
        "totals_usd": {
            "total_documentos": 10,
            "aceptados": 10,
            "rechazados": 0,
            "pendientes": 0,
            "total_monto": 5000.00,
            "total_igv": 900.00,
            "total_gravable": 4100.00
        },
        "top_clients": [...],
        "pending_documents": [...],
        "expiring_invoices": [...]
    },
    "message": "Estadísticas obtenidas correctamente"
}
```

#### Resumen mensual por sucursal
```http
GET /api/v1/dashboard/monthly-summary?company_id=1&branch_id=2&year=2025&month=12
Authorization: Bearer {token}
```

**Respuesta:**
```json
{
    "success": true,
    "data": {
        "filters": {
            "company_id": 1,
            "branch_id": 2,
            "year": 2025,
            "month": 12
        },
        "summary": {
            "PEN": {
                "total_documentos": 50,
                "aceptados": 48,
                "rechazados": 1,
                "pendientes": 1,
                "total_monto": 45000.00,
                "total_igv": 8100.00,
                "total_gravable": 36900.00
            },
            "USD": {
                "total_documentos": 5,
                "aceptados": 5,
                "rechazados": 0,
                "pendientes": 0,
                "total_monto": 2500.00,
                "total_igv": 450.00,
                "total_gravable": 2050.00
            }
        }
    },
    "message": "Resumen mensual obtenido correctamente"
}
```

#### Estadísticas de clientes por sucursal
```http
GET /api/v1/dashboard/client-statistics?company_id=1&branch_id=2
Authorization: Bearer {token}
```

**Respuesta:**
```json
{
    "success": true,
    "data": {
        "filters": {
            "company_id": 1,
            "branch_id": 2,
            "client_id": null
        },
        "clients": [
            {
                "id": 1,
                "razon_social": "CLIENTE PRINCIPAL SAC",
                "numero_documento": "20123456789",
                "total_invoices": 25,
                "total_purchases": 35000.50,
                "last_purchase_date": "2025-12-10"
            }
        ]
    },
    "message": "Estadísticas de clientes obtenidas correctamente"
}
```

#### Documentos que requieren reenvío por sucursal
```http
GET /api/v1/dashboard/requires-resend?company_id=1&branch_id=2&max_attempts=3
Authorization: Bearer {token}
```

**Respuesta:**
```json
{
    "success": true,
    "data": {
        "filters": {
            "company_id": 1,
            "branch_id": 2,
            "max_attempts": 3
        },
        "documents": [
            {
                "id": 123,
                "numero_completo": "F001-00000123",
                "fecha_emision": "2025-12-10",
                "estado_sunat": "ERROR",
                "intentos_envio": 1
            }
        ],
        "count": 1
    },
    "message": "Documentos pendientes de reenvío obtenidos correctamente"
}
```

---

## 🔔 WEBHOOKS

> **Prefijo:** `/api/v1/webhooks`
> **Autenticación:** Requerida

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/v1/webhooks` | Listar todos los webhooks |
| `POST` | `/v1/webhooks` | Crear nuevo webhook |
| `GET` | `/v1/webhooks/{id}` | Ver detalle de webhook |
| `PUT` | `/v1/webhooks/{id}` | Actualizar webhook |
| `DELETE` | `/v1/webhooks/{id}` | Eliminar webhook |
| `POST` | `/v1/webhooks/{id}/test` | Probar webhook |
| `GET` | `/v1/webhooks/{id}/statistics` | Estadísticas del webhook |
| `GET` | `/v1/webhooks/{id}/deliveries` | Historial de entregas |
| `POST` | `/v1/webhooks/deliveries/{deliveryId}/retry` | Reintentar entrega fallida |

### Ejemplos

#### Crear webhook
```http
POST /api/v1/webhooks
Authorization: Bearer {token}
Content-Type: application/json

{
    "url": "https://tu-servidor.com/webhook",
    "events": ["invoice.created", "invoice.sent", "invoice.accepted"],
    "active": true
}
```

---

## ⚙️ SETUP DEL SISTEMA

> **Prefijo:** `/api/v1/setup`
> **Autenticación:** Requerida

| Método | Ruta | Descripción |
|--------|------|-------------|
| `POST` | `/v1/setup/complete` | Configuración completa del sistema |
| `POST` | `/v1/setup/configure-sunat` | Configurar conexión con SUNAT |

---

## 📍 UBIGEOS

> **Prefijo:** `/api/v1/ubigeos`
> **Autenticación:** Requerida

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/v1/ubigeos/regiones` | Listar todas las regiones |
| `GET` | `/v1/ubigeos/provincias` | Listar provincias (filtrar por región) |
| `GET` | `/v1/ubigeos/distritos` | Listar distritos (filtrar por provincia) |
| `GET` | `/v1/ubigeos/search` | Buscar ubigeo por nombre |
| `GET` | `/v1/ubigeos/{id}` | Obtener ubigeo por ID |

### Ejemplos

#### Listar provincias de una región
```http
GET /api/v1/ubigeos/provincias?region_id=15
Authorization: Bearer {token}
```

#### Buscar ubigeo
```http
GET /api/v1/ubigeos/search?q=miraflores
Authorization: Bearer {token}
```

---

## 🏢 EMPRESAS

> **Prefijo:** `/api/v1/companies`
> **Autenticación:** Requerida

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/v1/companies` | Listar todas las empresas |
| `POST` | `/v1/companies` | Crear empresa (básico) |
| `POST` | `/v1/companies/complete` | Crear empresa (completo) |
| `GET` | `/v1/companies/{id}` | Ver detalle de empresa |
| `PUT` | `/v1/companies/{id}` | Actualizar empresa |
| `DELETE` | `/v1/companies/{id}` | Eliminar/desactivar empresa |
| `POST` | `/v1/companies/{id}/activate` | Activar/desactivar empresa |
| `POST` | `/v1/companies/{id}/toggle-production` | Cambiar modo beta/producción |
| `POST` | `/v1/companies/{id}/upload-files` | Subir logo y certificado |
| `GET` | `/v1/companies/{id}/pdf-info` | Obtener configuración PDF |
| `PUT` | `/v1/companies/{id}/pdf-info` | Actualizar configuración PDF |

### Ejemplos

#### Crear empresa completa
```http
POST /api/v1/companies/complete
Authorization: Bearer {token}
Content-Type: multipart/form-data

{
    "ruc": "20123456789",
    "razon_social": "MI EMPRESA SAC",
    "nombre_comercial": "Mi Empresa",
    "direccion": "Av. Principal 123",
    "ubigeo": "150101",
    "distrito": "Lima",
    "provincia": "Lima",
    "departamento": "Lima",
    "email": "contacto@miempresa.com",
    "telefono": "01-1234567",
    "usuario_sol": "MODDATOS",
    "clave_sol": "MODDATOS",
    "certificado_pem": [archivo .pfx o .pem],
    "certificado_password": "password123",
    "modo_produccion": false
}
```

#### Subir certificado PFX
```http
POST /api/v1/companies/1/upload-files
Authorization: Bearer {token}
Content-Type: multipart/form-data

{
    "certificado_pem": [archivo .pfx],
    "certificado_password": "password123"
}
```

---

## ⚙️ CONFIGURACIONES DE EMPRESAS

> **Prefijo:** `/api/v1/companies/{company_id}/config`
> **Autenticación:** Requerida

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/v1/companies/{id}/config` | Ver todas las configuraciones |
| `GET` | `/v1/companies/{id}/config/{section}` | Ver sección específica |
| `PUT` | `/v1/companies/{id}/config/{section}` | Actualizar sección |
| `GET` | `/v1/companies/{id}/config/validate/services` | Validar servicios SUNAT |
| `POST` | `/v1/companies/{id}/config/reset` | Restaurar valores por defecto |
| `POST` | `/v1/companies/{id}/config/migrate` | Migrar configuraciones |
| `DELETE` | `/v1/companies/{id}/config/cache` | Limpiar caché |
| `GET` | `/v1/config/defaults` | Valores por defecto globales |
| `GET` | `/v1/config/summary` | Resumen de configuraciones |

---

## 🔑 CREDENCIALES GRE

> **Prefijo:** `/api/v1/companies/{company}/gre-credentials`
> **Autenticación:** Requerida

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/v1/companies/{id}/gre-credentials` | Ver credenciales GRE |
| `PUT` | `/v1/companies/{id}/gre-credentials` | Actualizar credenciales |
| `POST` | `/v1/companies/{id}/gre-credentials/test-connection` | Probar conexión |
| `DELETE` | `/v1/companies/{id}/gre-credentials/clear` | Limpiar credenciales |
| `POST` | `/v1/companies/{id}/gre-credentials/copy` | Copiar de otra empresa |
| `GET` | `/v1/gre-credentials/defaults/{mode}` | Valores por defecto (beta/produccion) |

---

## 🏪 SUCURSALES

> **Prefijo:** `/api/v1/branches`
> **Autenticación:** Requerida

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/v1/branches` | Listar todas las sucursales |
| `POST` | `/v1/branches` | Crear sucursal |
| `GET` | `/v1/branches/{id}` | Ver detalle de sucursal |
| `PUT` | `/v1/branches/{id}` | Actualizar sucursal |
| `DELETE` | `/v1/branches/{id}` | Eliminar sucursal |
| `POST` | `/v1/branches/{id}/activate` | Activar/desactivar sucursal |
| `GET` | `/v1/companies/{id}/branches` | Listar sucursales de una empresa |

### Ejemplos

#### Crear sucursal
```http
POST /api/v1/branches
Authorization: Bearer {token}
Content-Type: application/json

{
    "company_id": 1,
    "codigo": "0001",
    "nombre": "Sucursal Principal",
    "direccion": "Av. Principal 123",
    "ubigeo": "150101",
    "distrito": "Lima",
    "provincia": "Lima",
    "departamento": "Lima"
}
```

---

## 👥 CLIENTES

> **Prefijo:** `/api/v1/clients`
> **Autenticación:** Requerida

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/v1/clients` | Listar todos los clientes |
| `POST` | `/v1/clients` | Crear cliente |
| `GET` | `/v1/clients/{id}` | Ver detalle de cliente |
| `PUT` | `/v1/clients/{id}` | Actualizar cliente |
| `DELETE` | `/v1/clients/{id}` | Eliminar cliente |
| `POST` | `/v1/clients/{id}/activate` | Activar/desactivar cliente |
| `GET` | `/v1/companies/{id}/clients` | Listar clientes de una empresa |
| `POST` | `/v1/clients/search-by-document` | Buscar cliente por documento |

### Ejemplos

#### Crear cliente
```http
POST /api/v1/clients
Authorization: Bearer {token}
Content-Type: application/json

{
    "company_id": 1,
    "tipo_documento": "6",
    "numero_documento": "20123456789",
    "razon_social": "CLIENTE SAC",
    "direccion": "Av. Cliente 456",
    "email": "cliente@example.com",
    "telefono": "999888777"
}
```

#### Buscar por documento
```http
POST /api/v1/clients/search-by-document
Authorization: Bearer {token}
Content-Type: application/json

{
    "tipo_documento": "6",
    "numero_documento": "20123456789"
}
```

---

## 🔢 CORRELATIVOS

> **Prefijo:** `/api/v1/branches/{branch}/correlatives`
> **Autenticación:** Requerida

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/v1/branches/{id}/correlatives` | Listar correlativos de sucursal |
| `POST` | `/v1/branches/{id}/correlatives` | Crear correlativo |
| `PUT` | `/v1/branches/{id}/correlatives/{correlativeId}` | Actualizar correlativo |
| `DELETE` | `/v1/branches/{id}/correlatives/{correlativeId}` | Eliminar correlativo |
| `POST` | `/v1/branches/{id}/correlatives/batch` | Crear múltiples correlativos |
| `POST` | `/v1/branches/{id}/correlatives/{correlativeId}/increment` | Incrementar correlativo |
| `GET` | `/v1/correlatives/document-types` | Listar tipos de documento |

### Ejemplos

#### Crear correlativo
```http
POST /api/v1/branches/1/correlatives
Authorization: Bearer {token}
Content-Type: application/json

{
    "tipo_documento": "01",
    "serie": "F001",
    "correlativo_actual": 1
}
```

#### Crear múltiples correlativos
```http
POST /api/v1/branches/1/correlatives/batch
Authorization: Bearer {token}
Content-Type: application/json

{
    "correlativos": [
        {"tipo_documento": "01", "serie": "F001", "correlativo_actual": 1},
        {"tipo_documento": "03", "serie": "B001", "correlativo_actual": 1},
        {"tipo_documento": "07", "serie": "FC01", "correlativo_actual": 1},
        {"tipo_documento": "08", "serie": "FD01", "correlativo_actual": 1}
    ]
}
```

---

## 📄 PDF

> **Prefijo:** `/api/v1/pdf`
> **Autenticación:** Requerida

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/v1/pdf/formats` | Listar formatos de PDF disponibles |

**Formatos disponibles:**
- `A4` - Tamaño estándar (210 x 297 mm)
- `A5` - Media página (148 x 210 mm)
- `80mm` - Ticket (80 x 200 mm)
- `50mm` - Ticket pequeño (50 x 150 mm)
- `ticket` - Formato ticket optimizado

---

## 🧾 FACTURAS

> **Prefijo:** `/api/v1/invoices`
> **Autenticación:** Requerida

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/v1/invoices` | Listar facturas |
| `POST` | `/v1/invoices` | Crear factura |
| `GET` | `/v1/invoices/{id}` | Ver detalle de factura |
| `PUT` | `/v1/invoices/{id}` | Actualizar factura |
| `PATCH` | `/v1/invoices/{id}` | Actualización parcial |
| `POST` | `/v1/invoices/{id}/send-sunat` | Enviar a SUNAT (sincrónico) |
| `POST` | `/v1/invoices/{id}/send-sunat-async` | Enviar a SUNAT (asincrónico) |
| `GET` | `/v1/invoices/{id}/download-xml` | Descargar XML |
| `GET` | `/v1/invoices/{id}/download-cdr` | Descargar CDR |
| `GET` | `/v1/invoices/{id}/download-pdf` | Descargar PDF |
| `POST` | `/v1/invoices/{id}/generate-pdf` | Generar PDF |

### Ejemplos

#### Crear factura simple
```http
POST /api/v1/invoices
Authorization: Bearer {token}
Content-Type: application/json

{
    "company_id": 1,
    "branch_id": 1,
    "serie": "F001",
    "fecha_emision": "2025-12-11",
    "moneda": "PEN",
    "tipo_operacion": "0101",
    "forma_pago_tipo": "Contado",

    "client": {
        "tipo_documento": "6",
        "numero_documento": "20123456789",
        "razon_social": "CLIENTE SAC",
        "direccion": "Av. Cliente 123"
    },

    "detalles": [
        {
            "codigo": "PROD001",
            "descripcion": "Producto de ejemplo",
            "unidad": "NIU",
            "cantidad": 2,
            "mto_valor_unitario": 100.00,
            "tip_afe_igv": "10",
            "porcentaje_igv": 18
        }
    ]
}
```

#### Crear factura con detracción
```http
POST /api/v1/invoices
Authorization: Bearer {token}
Content-Type: application/json

{
    "company_id": 1,
    "branch_id": 1,
    "serie": "F001",
    "fecha_emision": "2025-12-11",
    "moneda": "PEN",
    "tipo_operacion": "1001",
    "forma_pago_tipo": "Contado",

    "client": {
        "tipo_documento": "6",
        "numero_documento": "20123456789",
        "razon_social": "CLIENTE SAC",
        "direccion": "Av. Cliente 123"
    },

    "detalles": [
        {
            "codigo": "SRV001",
            "descripcion": "Servicio de mantenimiento",
            "unidad": "ZZ",
            "cantidad": 1,
            "mto_valor_unitario": 1000.00,
            "tip_afe_igv": "10",
            "porcentaje_igv": 18
        }
    ],

    "detraccion": {
        "codigo_bien_servicio": "020",
        "cuenta_banco": "00-123-456789"
    }
}
```

#### Crear factura al crédito
```http
POST /api/v1/invoices
Authorization: Bearer {token}
Content-Type: application/json

{
    "company_id": 1,
    "branch_id": 1,
    "serie": "F001",
    "fecha_emision": "2025-12-11",
    "fecha_vencimiento": "2026-01-11",
    "moneda": "PEN",
    "tipo_operacion": "0101",
    "forma_pago_tipo": "Credito",

    "forma_pago_cuotas": [
        {
            "moneda": "PEN",
            "monto": 590.00,
            "fecha_pago": "2025-12-26"
        },
        {
            "moneda": "PEN",
            "monto": 590.00,
            "fecha_pago": "2026-01-11"
        }
    ],

    "client": {
        "tipo_documento": "6",
        "numero_documento": "20123456789",
        "razon_social": "CLIENTE SAC"
    },

    "detalles": [
        {
            "codigo": "PROD001",
            "descripcion": "Producto",
            "unidad": "NIU",
            "cantidad": 1,
            "mto_valor_unitario": 1000.00,
            "tip_afe_igv": "10",
            "porcentaje_igv": 18
        }
    ]
}
```

#### Enviar a SUNAT
```http
POST /api/v1/invoices/1/send-sunat
Authorization: Bearer {token}
```

#### Descargar PDF
```http
GET /api/v1/invoices/1/download-pdf?format=A4
Authorization: Bearer {token}
```

---

## 🎫 BOLETAS

> **Prefijo:** `/api/v1/boletas`
> **Autenticación:** Requerida

### Operaciones CRUD

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/v1/boletas` | Listar boletas |
| `POST` | `/v1/boletas` | Crear boleta |
| `GET` | `/v1/boletas/{id}` | Ver detalle de boleta |
| `PUT` | `/v1/boletas/{id}` | Actualizar boleta |
| `PATCH` | `/v1/boletas/{id}` | Actualización parcial |
| `POST` | `/v1/boletas/{id}/send-sunat` | Enviar a SUNAT |
| `GET` | `/v1/boletas/{id}/download-xml` | Descargar XML |
| `GET` | `/v1/boletas/{id}/download-cdr` | Descargar CDR |
| `GET` | `/v1/boletas/{id}/download-pdf` | Descargar PDF |
| `POST` | `/v1/boletas/{id}/generate-pdf` | Generar PDF |

### Resumen Diario

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/v1/boletas/fechas-pendientes-resumen` | Fechas con boletas sin resumen |
| `GET` | `/v1/boletas/pending-for-summary` | Boletas pendientes de resumen |
| `POST` | `/v1/boletas/create-daily-summary` | Crear resumen de una fecha |
| `POST` | `/v1/boletas/create-multiple-summaries` | Crear múltiples resúmenes |
| `POST` | `/v1/boletas/create-all-pending-summaries` | Crear todos los resúmenes pendientes |
| `POST` | `/v1/boletas/summary/{id}/send-sunat` | Enviar resumen a SUNAT |
| `POST` | `/v1/boletas/summary/{id}/check-status` | Verificar estado del resumen |

### Anulaciones

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/v1/boletas/vencidas` | Boletas vencidas |
| `POST` | `/v1/boletas/anular-localmente` | Anular boletas localmente |
| `GET` | `/v1/boletas/anulables` | Boletas que pueden anularse |
| `POST` | `/v1/boletas/anular-oficialmente` | Anular oficialmente (vía resumen) |
| `GET` | `/v1/boletas/pendientes-anulacion` | Boletas pendientes de anulación |
| `GET` | `/v1/boletas/anuladas` | Boletas ya anuladas |

### Ejemplos

#### Crear boleta
```http
POST /api/v1/boletas
Authorization: Bearer {token}
Content-Type: application/json

{
    "company_id": 1,
    "branch_id": 1,
    "serie": "B001",
    "fecha_emision": "2025-12-11",
    "moneda": "PEN",
    "forma_pago_tipo": "Contado",

    "client": {
        "tipo_documento": "1",
        "numero_documento": "12345678",
        "razon_social": "JUAN PEREZ"
    },

    "detalles": [
        {
            "codigo": "PROD001",
            "descripcion": "Producto",
            "unidad": "NIU",
            "cantidad": 1,
            "mto_valor_unitario": 50.00,
            "tip_afe_igv": "10",
            "porcentaje_igv": 18
        }
    ]
}
```

#### Crear resumen diario
```http
POST /api/v1/boletas/create-daily-summary
Authorization: Bearer {token}
Content-Type: application/json

{
    "company_id": 1,
    "branch_id": 1,
    "fecha": "2025-12-11"
}
```

---

## 📅 RESÚMENES DIARIOS

> **Prefijo:** `/api/v1/daily-summaries`
> **Autenticación:** Requerida

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/v1/daily-summaries` | Listar resúmenes |
| `POST` | `/v1/daily-summaries` | Crear resumen |
| `GET` | `/v1/daily-summaries/{id}` | Ver detalle |
| `POST` | `/v1/daily-summaries/{id}/send-sunat` | Enviar a SUNAT |
| `POST` | `/v1/daily-summaries/{id}/check-status` | Verificar estado |
| `GET` | `/v1/daily-summaries/{id}/download-xml` | Descargar XML |
| `GET` | `/v1/daily-summaries/{id}/download-cdr` | Descargar CDR |
| `GET` | `/v1/daily-summaries/{id}/download-pdf` | Descargar PDF |
| `POST` | `/v1/daily-summaries/{id}/generate-pdf` | Generar PDF |
| `GET` | `/v1/daily-summaries/pending` | Resúmenes pendientes |
| `POST` | `/v1/daily-summaries/check-all-pending` | Verificar todos los pendientes |

---

## 📝 NOTAS DE CRÉDITO

> **Prefijo:** `/api/v1/credit-notes`
> **Autenticación:** Requerida

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/v1/credit-notes` | Listar notas de crédito |
| `POST` | `/v1/credit-notes` | Crear nota de crédito |
| `GET` | `/v1/credit-notes/{id}` | Ver detalle |
| `POST` | `/v1/credit-notes/{id}/send-sunat` | Enviar a SUNAT |
| `GET` | `/v1/credit-notes/{id}/download-xml` | Descargar XML |
| `GET` | `/v1/credit-notes/{id}/download-cdr` | Descargar CDR |
| `GET` | `/v1/credit-notes/{id}/download-pdf` | Descargar PDF |
| `POST` | `/v1/credit-notes/{id}/generate-pdf` | Generar PDF |
| `GET` | `/v1/credit-notes/catalogs/motivos` | Catálogo de motivos |

### Motivos de Nota de Crédito (Catálogo 09)

| Código | Descripción |
|--------|-------------|
| `01` | Anulación de la operación |
| `02` | Anulación por error en el RUC |
| `03` | Corrección por error en la descripción |
| `04` | Descuento global |
| `05` | Descuento por ítem |
| `06` | Devolución total |
| `07` | Devolución por ítem |
| `08` | Bonificación |
| `09` | Disminución en el valor |
| `10` | Otros conceptos |
| `11` | Ajuste de operaciones de exportación |
| `12` | Ajuste afectos al IVAP |
| `13` | Ajuste – Loss (Pérdida) montos y/o fechas de pago |

### Ejemplo

```http
POST /api/v1/credit-notes
Authorization: Bearer {token}
Content-Type: application/json

{
    "company_id": 1,
    "branch_id": 1,
    "serie": "FC01",
    "fecha_emision": "2025-12-11",
    "moneda": "PEN",
    "cod_motivo": "01",
    "des_motivo": "Anulación de la operación",

    "tipo_doc_afectado": "01",
    "num_doc_afectado": "F001-00000001",

    "client": {
        "tipo_documento": "6",
        "numero_documento": "20123456789",
        "razon_social": "CLIENTE SAC"
    },

    "detalles": [
        {
            "codigo": "PROD001",
            "descripcion": "Producto anulado",
            "unidad": "NIU",
            "cantidad": 1,
            "mto_valor_unitario": 100.00,
            "tip_afe_igv": "10",
            "porcentaje_igv": 18
        }
    ]
}
```

---

## 📝 NOTAS DE DÉBITO

> **Prefijo:** `/api/v1/debit-notes`
> **Autenticación:** Requerida

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/v1/debit-notes` | Listar notas de débito |
| `POST` | `/v1/debit-notes` | Crear nota de débito |
| `GET` | `/v1/debit-notes/{id}` | Ver detalle |
| `POST` | `/v1/debit-notes/{id}/send-sunat` | Enviar a SUNAT |
| `GET` | `/v1/debit-notes/{id}/download-xml` | Descargar XML |
| `GET` | `/v1/debit-notes/{id}/download-cdr` | Descargar CDR |
| `GET` | `/v1/debit-notes/{id}/download-pdf` | Descargar PDF |
| `POST` | `/v1/debit-notes/{id}/generate-pdf` | Generar PDF |
| `GET` | `/v1/debit-notes/catalogs/motivos` | Catálogo de motivos |

### Motivos de Nota de Débito (Catálogo 10)

| Código | Descripción |
|--------|-------------|
| `01` | Intereses por mora |
| `02` | Aumento en el valor |
| `03` | Penalidades/otros conceptos |
| `11` | Ajuste de operaciones de exportación |
| `12` | Ajuste afectos al IVAP |

---

## 💰 RETENCIONES

> **Prefijo:** `/api/v1/retentions`
> **Autenticación:** Requerida

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/v1/retentions` | Listar retenciones |
| `POST` | `/v1/retentions` | Crear retención |
| `GET` | `/v1/retentions/{id}` | Ver detalle |
| `POST` | `/v1/retentions/{id}/send-sunat` | Enviar a SUNAT |
| `GET` | `/v1/retentions/{id}/download-xml` | Descargar XML |
| `GET` | `/v1/retentions/{id}/download-cdr` | Descargar CDR |
| `GET` | `/v1/retentions/{id}/download-pdf` | Descargar PDF |
| `POST` | `/v1/retentions/{id}/generate-pdf` | Generar PDF |

---

## ❌ COMUNICACIONES DE BAJA

> **Prefijo:** `/api/v1/voided-documents`
> **Autenticación:** Requerida

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/v1/voided-documents` | Listar comunicaciones de baja |
| `POST` | `/v1/voided-documents` | Crear comunicación de baja |
| `GET` | `/v1/voided-documents/available-documents` | Documentos disponibles para anular |
| `GET` | `/v1/voided-documents/reasons` | Catálogo de motivos |
| `GET` | `/v1/voided-documents/reasons/categories` | Categorías de motivos |
| `GET` | `/v1/voided-documents/reasons/{codigo}` | Motivo por código |
| `GET` | `/v1/voided-documents/{id}` | Ver detalle |
| `POST` | `/v1/voided-documents/{id}/send-sunat` | Enviar a SUNAT |
| `POST` | `/v1/voided-documents/{id}/check-status` | Verificar estado |
| `GET` | `/v1/voided-documents/{id}/download-xml` | Descargar XML |
| `GET` | `/v1/voided-documents/{id}/download-cdr` | Descargar CDR |

### Ejemplo

```http
POST /api/v1/voided-documents
Authorization: Bearer {token}
Content-Type: application/json

{
    "company_id": 1,
    "branch_id": 1,
    "fecha_generacion": "2025-12-11",
    "documentos": [
        {
            "tipo_documento": "01",
            "serie": "F001",
            "correlativo": "00000001",
            "motivo": "Error en emisión"
        }
    ]
}
```

---

## 🚚 GUÍAS DE REMISIÓN

> **Prefijo:** `/api/v1/dispatch-guides`
> **Autenticación:** Requerida

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/v1/dispatch-guides` | Listar guías |
| `POST` | `/v1/dispatch-guides` | Crear guía |
| `GET` | `/v1/dispatch-guides/{id}` | Ver detalle |
| `POST` | `/v1/dispatch-guides/{id}/send-sunat` | Enviar a SUNAT |
| `POST` | `/v1/dispatch-guides/{id}/check-status` | Verificar estado |
| `GET` | `/v1/dispatch-guides/{id}/download-xml` | Descargar XML |
| `GET` | `/v1/dispatch-guides/{id}/download-cdr` | Descargar CDR |
| `GET` | `/v1/dispatch-guides/{id}/download-pdf` | Descargar PDF |
| `POST` | `/v1/dispatch-guides/{id}/generate-pdf` | Generar PDF |
| `GET` | `/v1/dispatch-guides/catalogs/transfer-reasons` | Motivos de traslado |
| `GET` | `/v1/dispatch-guides/catalogs/transport-modes` | Modalidades de transporte |

### Motivos de Traslado (Catálogo 20)

| Código | Descripción |
|--------|-------------|
| `01` | Venta |
| `02` | Compra |
| `03` | Venta con entrega a terceros |
| `04` | Traslado entre establecimientos de la misma empresa |
| `05` | Consignación |
| `06` | Devolución |
| `07` | Recojo de bienes transformados |
| `08` | Importación |
| `09` | Exportación |
| `13` | Otros |
| `14` | Venta sujeta a confirmación del comprador |
| `17` | Traslado de bienes para transformación |
| `18` | Traslado emisor itinerante CP |
| `19` | Traslado a zona primaria |

---

## 🔍 CONSULTA CPE

> **Prefijo:** `/api/v1/consulta-cpe`
> **Autenticación:** Requerida
> **Rate Limit:** throttle:cpe-consulta

| Método | Ruta | Descripción |
|--------|------|-------------|
| `POST` | `/v1/consulta-cpe/factura/{id}` | Consultar estado de factura |
| `POST` | `/v1/consulta-cpe/boleta/{id}` | Consultar estado de boleta |
| `POST` | `/v1/consulta-cpe/nota-credito/{id}` | Consultar estado de nota de crédito |
| `POST` | `/v1/consulta-cpe/nota-debito/{id}` | Consultar estado de nota de débito |
| `POST` | `/v1/consulta-cpe/masivo` | Consulta masiva de documentos |
| `GET` | `/v1/consulta-cpe/estadisticas` | Estadísticas de consultas |

### Ejemplo

```http
POST /api/v1/consulta-cpe/factura/1
Authorization: Bearer {token}
```

**Respuesta:**
```json
{
    "success": true,
    "data": {
        "estado": "ACEPTADO",
        "codigo": "0",
        "mensaje": "La Factura numero F001-00000001, ha sido aceptada",
        "fecha_consulta": "2025-12-11 10:30:00"
    }
}
```

---

## 🏦 BANCARIZACIÓN

> **Prefijo:** `/api/v1/bancarizacion`
> **Autenticación:** Requerida

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/v1/bancarizacion/medios-pago` | Listar medios de pago |
| `POST` | `/v1/bancarizacion/validar` | Validar si aplica bancarización |
| `GET` | `/v1/bancarizacion/reportes/sin-bancarizacion` | Reporte de operaciones sin bancarizar |
| `GET` | `/v1/bancarizacion/estadisticas` | Estadísticas de cumplimiento |

### Umbrales de Bancarización (Ley N° 28194)

| Moneda | Umbral |
|--------|--------|
| PEN (Soles) | S/ 2,000.00 |
| USD (Dólares) | US$ 500.00 |

### Ejemplo

```http
POST /api/v1/bancarizacion/validar
Authorization: Bearer {token}
Content-Type: application/json

{
    "monto": 2500.00,
    "moneda": "PEN"
}
```

**Respuesta:**
```json
{
    "success": true,
    "data": {
        "aplica_bancarizacion": true,
        "umbral": 2000.00,
        "moneda": "PEN",
        "mensaje": "Esta operación supera el umbral de S/ 2,000.00 y requiere bancarización"
    }
}
```

---

## 📚 CATÁLOGOS SUNAT

> **Prefijo:** `/api/v1/catalogos`
> **Autenticación:** Requerida

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/v1/catalogos/detracciones` | Listar códigos de detracción |
| `GET` | `/v1/catalogos/detracciones/buscar?q={texto}` | Buscar detracción por descripción |
| `GET` | `/v1/catalogos/detracciones/por-porcentaje` | Detracciones agrupadas por % |
| `GET` | `/v1/catalogos/detracciones/medios-pago` | Medios de pago para detracción |
| `GET` | `/v1/catalogos/detracciones/{codigo}` | Detalle de detracción por código |
| `POST` | `/v1/catalogos/detracciones/calcular` | Calcular monto de detracción |

### Códigos de Detracción (Catálogo 54)

| Código | Descripción | Porcentaje |
|--------|-------------|------------|
| `001` | Azúcar y melaza de caña | 10% |
| `004` | Recursos hidrobiológicos | 4% |
| `010` | Residuos, subproductos, desechos | 15% |
| `012` | Intermediación laboral y tercerización | 12% |
| `019` | Arrendamiento de bienes muebles | 10% |
| `020` | Mantenimiento y reparación de bienes muebles | 12% |
| `021` | Movimiento de carga | 10% |
| `022` | Otros servicios empresariales | 12% |
| `027` | Servicio de transporte de carga | 4% |
| `030` | Contratos de construcción | 4% |
| `037` | Demás servicios gravados con IGV | 12% |

### Ejemplo

```http
POST /api/v1/catalogos/detracciones/calcular
Authorization: Bearer {token}
Content-Type: application/json

{
    "codigo_bien_servicio": "020",
    "monto_total": 1180.00
}
```

**Respuesta:**
```json
{
    "success": true,
    "data": {
        "codigo_bien_servicio": "020",
        "descripcion": "Mantenimiento y reparación de bienes muebles",
        "monto_total_operacion": 1180.00,
        "porcentaje_detraccion": 12.00,
        "monto_detraccion": 141.60,
        "monto_neto_a_pagar": 1038.40
    }
}
```

---

## 📊 RESUMEN TOTAL DE RUTAS

| Categoría | Cantidad |
|-----------|----------|
| Rutas Públicas | 8 |
| Autenticación/Usuario | 4 |
| Dashboard | 5 |
| Webhooks | 9 |
| Setup | 2 |
| Ubigeos | 5 |
| Empresas | 11 |
| Configuraciones | 9 |
| Credenciales GRE | 6 |
| Sucursales | 7 |
| Clientes | 8 |
| Correlativos | 7 |
| PDF | 1 |
| Facturas | 11 |
| Boletas | 21 |
| Resúmenes Diarios | 11 |
| Notas de Crédito | 9 |
| Notas de Débito | 9 |
| Retenciones | 8 |
| Comunicaciones de Baja | 11 |
| Guías de Remisión | 11 |
| Consulta CPE | 6 |
| Bancarización | 4 |
| Catálogos SUNAT | 6 |
| **TOTAL** | **~179 rutas** |

---

## 🔐 AUTENTICACIÓN

Todas las rutas protegidas requieren un token Bearer en el header:

```http
Authorization: Bearer {tu-token-aquí}
```

### Obtener Token

```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "tu-email@example.com",
    "password": "tu-password"
}
```

---

## ⚠️ CÓDIGOS DE RESPUESTA HTTP

| Código | Descripción |
|--------|-------------|
| `200` | OK - Petición exitosa |
| `201` | Created - Recurso creado exitosamente |
| `400` | Bad Request - Error en los datos enviados |
| `401` | Unauthorized - Token inválido o expirado |
| `403` | Forbidden - Sin permisos para esta acción |
| `404` | Not Found - Recurso no encontrado |
| `422` | Unprocessable Entity - Error de validación |
| `429` | Too Many Requests - Rate limit excedido |
| `500` | Internal Server Error - Error del servidor |

---

## 📞 SOPORTE

Para soporte técnico o consultas sobre la API, contactar a:
- Email: djjmygm160399@gmail.com
- Documentación: https://apigo.apuuraydev.com/

---

> **Nota:** Esta documentación corresponde a la versión PRO de la API de Facturación Electrónica SUNAT Perú.

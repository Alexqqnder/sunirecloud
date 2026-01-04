# 🔐 Usuario API con Permisos Limitados

Esta guía explica cómo crear usuarios adicionales con permisos limitados para acceder a la API, específicamente con permisos solo para **CREAR** documentos electrónicos.

---

## 📋 ÍNDICE

1. [Roles Disponibles](#roles-disponibles)
2. [Crear Usuario con Permisos Limitados](#crear-usuario-con-permisos-limitados)
3. [Login con el Nuevo Usuario](#login-con-el-nuevo-usuario)
4. [Ejemplos de Uso en Postman](#ejemplos-de-uso-en-postman)
5. [Permisos y Restricciones](#permisos-y-restricciones)
6. [Casos de Uso Comunes](#casos-de-uso-comunes)

---

## 🎭 ROLES DISPONIBLES

La API tiene los siguientes roles predefinidos:

| Rol | Nombre Técnico | Descripción | Permisos |
|-----|---------------|-------------|----------|
| **Super Admin** | `super_admin` | Administrador total del sistema | Todos los permisos |
| **Admin de Empresa** | `company_admin` | Administrador de una empresa específica | Gestión completa de su empresa |
| **Usuario de Empresa** | `company_user` | Usuario regular de una empresa | Lectura y operaciones básicas |
| **Cliente API** | `api_client` | Acceso API externo limitado | Solo crear y ver documentos |
| **Solo Lectura** | `read_only` | Acceso de consulta únicamente | Solo lectura |

---

## 👤 CREAR USUARIO CON PERMISOS LIMITADOS

### Endpoint

```
POST {{base_url}}/api/v1/auth/create-user
```

### Autenticación Requerida

- **Tipo:** Bearer Token
- **Rol requerido:** `super_admin`
- **Header:** `Authorization: Bearer {token_super_admin}`

### Request Body (JSON)

```json
{
  "name": "Cliente API Externo",
  "email": "api.cliente@empresa.com",
  "password": "Password123!",
  "role_name": "api_client",
  "company_id": 1,
  "user_type": "api_client"
}
```

### Parámetros Explicados

| Campo | Tipo | Requerido | Descripción |
|-------|------|-----------|-------------|
| `name` | string | ✅ | Nombre del usuario |
| `email` | string | ✅ | Email único (no debe existir en el sistema) |
| `password` | string | ✅ | Contraseña mínimo 8 caracteres |
| `role_name` | string | ✅ | Rol: `api_client` para permisos limitados |
| `company_id` | integer | ⚠️ | ID de la empresa (null para acceso multi-empresa) |
| `user_type` | string | ✅ | Tipo: `system`, `user`, o `api_client` |

### Ejemplo Completo en Postman

**Headers:**
```
Authorization: Bearer 1|super_admin_token_aqui
Content-Type: application/json
Accept: application/json
```

**Body (raw - JSON):**
```json
{
  "name": "Sistema POS Externo",
  "email": "pos@mitienda.com",
  "password": "Segura123!",
  "role_name": "api_client",
  "company_id": 1,
  "user_type": "api_client"
}
```

### Response Exitoso (200 OK)

```json
{
  "message": "Usuario creado exitosamente",
  "user": {
    "id": 5,
    "name": "Sistema POS Externo",
    "email": "pos@mitienda.com",
    "role": "Cliente API",
    "user_type": "api_client"
  }
}
```

### Errores Comunes

#### Error 403: Sin Permisos
```json
{
  "message": "No tienes permisos para crear usuarios",
  "status": "error"
}
```
**Solución:** Debes usar un token de usuario `super_admin`.

#### Error 422: Email Duplicado
```json
{
  "success": false,
  "message": "El campo email ya ha sido tomado.",
  "errors": {
    "email": [
      "El campo email ya ha sido tomado."
    ]
  }
}
```
**Solución:** Usar un email diferente.

#### Error 422: Rol Inválido
```json
{
  "success": false,
  "message": "El campo role name seleccionado es inválido.",
  "errors": {
    "role_name": [
      "El campo role name seleccionado es inválido."
    ]
  }
}
```
**Solución:** Usar uno de los roles válidos: `super_admin`, `company_admin`, `company_user`, `api_client`, `read_only`.

---

## 🔑 LOGIN CON EL NUEVO USUARIO

Una vez creado el usuario, necesitas hacer login para obtener su token de acceso.

### Endpoint

```
POST {{base_url}}/api/auth/login
```

### Request Body

```json
{
  "email": "pos@mitienda.com",
  "password": "Segura123!"
}
```

### Ejemplo en Postman

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Body (raw - JSON):**
```json
{
  "email": "pos@mitienda.com",
  "password": "Segura123!"
}
```

### Response Exitoso (200 OK)

```json
{
  "message": "Login exitoso",
  "user": {
    "id": 5,
    "name": "Sistema POS Externo",
    "email": "pos@mitienda.com",
    "role": "Cliente API",
    "company_id": 1,
    "permissions": [
      "api.access",
      "invoices.create",
      "invoices.view",
      "boletas.create",
      "boletas.view"
    ]
  },
  "access_token": "5|eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "Bearer"
}
```

**⚠️ IMPORTANTE:** Copia el `access_token` y guárdalo. Este token se usa para autenticar todas las peticiones del usuario API.

---

## 📝 EJEMPLOS DE USO EN POSTMAN

### Configuración Inicial

1. Crea una nueva colección en Postman llamada "API Cliente Limitado"
2. Agrega una variable de entorno:
   - `api_client_token`: Pega el token obtenido del login

### 1️⃣ Crear Factura Electrónica

**Endpoint:**
```
POST {{base_url}}/api/v1/invoices
```

**Headers:**
```
Authorization: Bearer {{api_client_token}}
Content-Type: application/json
Accept: application/json
```

**Body (raw - JSON):**
```json
{
  "company_id": 1,
  "branch_id": 1,
  "client_id": 1,
  "tipo_doc": "01",
  "serie": "F001",
  "correlativo": null,
  "fecha_emision": "2025-12-15",
  "moneda": "PEN",
  "tipo_operacion": "0101",
  "client": {
    "tipo_documento": "6",
    "numero_documento": "20123456789",
    "razon_social": "EMPRESA CLIENTE SAC",
    "direccion": "Av. Principal 123"
  },
  "items": [
    {
      "cantidad": 2,
      "unidad": "NIU",
      "descripcion": "PRODUCTO DE PRUEBA",
      "mto_valor_unitario": 100.00,
      "codigo_producto": "PROD001",
      "mto_base_igv": 200.00,
      "porcentaje_igv": 18,
      "mto_igv": 36.00,
      "tipo_afectacion_igv": "10",
      "mto_precio_unitario": 118.00
    }
  ],
  "mto_oper_gravadas": 200.00,
  "mto_igv": 36.00,
  "mto_imp_venta": 236.00,
  "observaciones": "Factura creada desde API externa"
}
```

**Response Exitoso (201 Created):**
```json
{
  "success": true,
  "message": "Factura creada exitosamente",
  "data": {
    "id": 150,
    "company_id": 1,
    "branch_id": 1,
    "numero_completo": "F001-00000050",
    "tipo_doc": "01",
    "serie": "F001",
    "correlativo": "00000050",
    "fecha_emision": "2025-12-15",
    "moneda": "PEN",
    "mto_imp_venta": 236.00,
    "estado_sunat": "PENDIENTE",
    "hash": null,
    "qr": null,
    "xml_path": null,
    "cdr_path": null,
    "pdf_path": null,
    "created_at": "2025-12-15T10:30:00.000000Z"
  }
}
```

### 2️⃣ Crear Boleta de Venta

**Endpoint:**
```
POST {{base_url}}/api/v1/boletas
```

**Headers:**
```
Authorization: Bearer {{api_client_token}}
Content-Type: application/json
Accept: application/json
```

**Body (raw - JSON):**
```json
{
  "company_id": 1,
  "branch_id": 1,
  "client_id": null,
  "tipo_doc": "03",
  "serie": "B001",
  "correlativo": null,
  "fecha_emision": "2025-12-15",
  "moneda": "PEN",
  "tipo_operacion": "0101",
  "client": {
    "tipo_documento": "1",
    "numero_documento": "12345678",
    "razon_social": "CLIENTE VARIOS"
  },
  "items": [
    {
      "cantidad": 1,
      "unidad": "NIU",
      "descripcion": "SERVICIO DE CONSULTORÍA",
      "mto_valor_unitario": 500.00,
      "codigo_producto": "SERV001",
      "mto_base_igv": 500.00,
      "porcentaje_igv": 18,
      "mto_igv": 90.00,
      "tipo_afectacion_igv": "10",
      "mto_precio_unitario": 590.00
    }
  ],
  "mto_oper_gravadas": 500.00,
  "mto_igv": 90.00,
  "mto_imp_venta": 590.00
}
```

**Response Exitoso (201 Created):**
```json
{
  "success": true,
  "message": "Boleta creada exitosamente",
  "data": {
    "id": 85,
    "company_id": 1,
    "branch_id": 1,
    "numero_completo": "B001-00000025",
    "tipo_doc": "03",
    "serie": "B001",
    "correlativo": "00000025",
    "fecha_emision": "2025-12-15",
    "moneda": "PEN",
    "mto_imp_venta": 590.00,
    "estado_sunat": "PENDIENTE",
    "created_at": "2025-12-15T10:35:00.000000Z"
  }
}
```

### 3️⃣ Ver Detalle de Factura (Permitido)

**Endpoint:**
```
GET {{base_url}}/api/v1/invoices/150
```

**Headers:**
```
Authorization: Bearer {{api_client_token}}
Accept: application/json
```

**Response Exitoso (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 150,
    "numero_completo": "F001-00000050",
    "fecha_emision": "2025-12-15",
    "mto_imp_venta": 236.00,
    "estado_sunat": "PENDIENTE",
    "client": {
      "tipo_documento": "6",
      "numero_documento": "20123456789",
      "razon_social": "EMPRESA CLIENTE SAC"
    },
    "items": [
      {
        "cantidad": 2,
        "descripcion": "PRODUCTO DE PRUEBA",
        "mto_precio_unitario": 118.00,
        "mto_total": 236.00
      }
    ]
  }
}
```

### 4️⃣ Intentar Actualizar Factura (Denegado)

**Endpoint:**
```
PUT {{base_url}}/api/v1/invoices/150
```

**Headers:**
```
Authorization: Bearer {{api_client_token}}
Content-Type: application/json
Accept: application/json
```

**Body (raw - JSON):**
```json
{
  "observaciones": "Actualización no permitida"
}
```

**Response de Error (403 Forbidden):**
```json
{
  "success": false,
  "message": "No tienes permisos para realizar esta acción",
  "error": {
    "type": "forbidden",
    "required_permission": "invoices.update"
  }
}
```

### 5️⃣ Listar Facturas (Denegado)

**Endpoint:**
```
GET {{base_url}}/api/v1/invoices
```

**Headers:**
```
Authorization: Bearer {{api_client_token}}
Accept: application/json
```

**Response de Error (403 Forbidden):**
```json
{
  "success": false,
  "message": "No tienes permisos para realizar esta acción",
  "error": {
    "type": "forbidden",
    "required_permission": "invoices.list"
  }
}
```

---

## 🔒 PERMISOS Y RESTRICCIONES

### ✅ Operaciones Permitidas (Rol `api_client`)

| Operación | Endpoint | Método |
|-----------|----------|--------|
| Crear Factura | `/api/v1/invoices` | POST |
| Ver Factura | `/api/v1/invoices/{id}` | GET |
| Crear Boleta | `/api/v1/boletas` | POST |
| Ver Boleta | `/api/v1/boletas/{id}` | GET |

### ❌ Operaciones NO Permitidas

| Operación | Endpoint | Método | Permiso Requerido |
|-----------|----------|--------|-------------------|
| Listar Facturas | `/api/v1/invoices` | GET | `invoices.list` |
| Actualizar Factura | `/api/v1/invoices/{id}` | PUT/PATCH | `invoices.update` |
| Eliminar Factura | `/api/v1/invoices/{id}` | DELETE | `invoices.delete` |
| Enviar a SUNAT | `/api/v1/invoices/{id}/send-sunat` | POST | `invoices.send` |
| Crear Empresa | `/api/v1/companies` | POST | `companies.create` |
| Gestionar Clientes | `/api/v1/clients` | ANY | `clients.*` |
| Ver Dashboard | `/api/v1/dashboard/*` | GET | `dashboard.view` |

---

## 🎯 CASOS DE USO COMUNES

### Caso 1: Sistema POS que Solo Emite Comprobantes

**Escenario:** Tienes un sistema de punto de venta que necesita crear facturas y boletas, pero no debe poder modificar ni eliminar documentos.

**Solución:**
```json
{
  "name": "Sistema POS - Tienda Principal",
  "email": "pos@mitienda.com",
  "password": "POSSeguro123!",
  "role_name": "api_client",
  "company_id": 1,
  "user_type": "api_client"
}
```

### Caso 2: Integración con Plataforma E-commerce

**Escenario:** Tu tienda online necesita generar facturas automáticamente cuando un cliente compra, pero no debe tener acceso a otras operaciones.

**Solución:**
```json
{
  "name": "Tienda Online - WooCommerce",
  "email": "ecommerce@tienda.com",
  "password": "EcommerceAPI2025!",
  "role_name": "api_client",
  "company_id": 1,
  "user_type": "api_client"
}
```

### Caso 3: Aplicación Móvil de Vendedores

**Escenario:** Tus vendedores en campo usan una app móvil para emitir comprobantes, pero no deben poder ver históricos ni modificar documentos existentes.

**Solución:**
```json
{
  "name": "App Móvil - Vendedores",
  "email": "app.vendedores@empresa.com",
  "password": "AppVendedores2025!",
  "role_name": "api_client",
  "company_id": 1,
  "user_type": "api_client"
}
```

---

## 📊 VERIFICAR PERMISOS DEL USUARIO

### Endpoint: Ver Información del Usuario Autenticado

```
GET {{base_url}}/api/v1/auth/me
```

**Headers:**
```
Authorization: Bearer {{api_client_token}}
Accept: application/json
```

**Response:**
```json
{
  "user": {
    "id": 5,
    "name": "Sistema POS Externo",
    "email": "pos@mitienda.com",
    "role": "Cliente API",
    "company": "MI EMPRESA SAC",
    "permissions": [
      "api.access",
      "invoices.create",
      "invoices.view",
      "boletas.create",
      "boletas.view"
    ],
    "last_login_at": "2025-12-15T10:00:00.000000Z",
    "created_at": "2025-12-15T09:00:00.000000Z"
  }
}
```

---

## 🔐 SEGURIDAD Y BUENAS PRÁCTICAS

### 1. Rotación de Tokens

Es recomendable cambiar periódicamente las contraseñas y regenerar tokens:

```
POST {{base_url}}/api/auth/logout
POST {{base_url}}/api/auth/login
```

### 2. Uso de Variables de Entorno

En Postman, crea un environment con:
- `base_url`: https://api.tusitiio.com
- `api_client_token`: [token aquí]

### 3. Monitoreo de Actividad

Los super_admin pueden consultar los logs de actividad de cada usuario.

### 4. Desactivar Usuarios

Si necesitas desactivar temporalmente un usuario API, contacta al super_admin para que actualice el estado del usuario.

---

## 📚 REFERENCIAS

- **Roles y Permisos:** Ver archivo `database/seeders/RolesAndPermissionsSeeder.php`
- **Autenticación:** Ver `app/Http/Controllers/Api/AuthController.php`
- **Documentación de API:** Revisar archivo Postman Collection completo

---

## ❓ PREGUNTAS FRECUENTES

### ¿Puedo crear un usuario con permisos personalizados?

Actualmente, la API usa roles predefinidos. Si necesitas permisos personalizados, contacta al desarrollador para crear un nuevo rol.

### ¿El usuario api_client puede enviar documentos a SUNAT?

No. El rol `api_client` solo puede **crear** y **ver** documentos. Para enviar a SUNAT se requiere el permiso `invoices.send` que no está incluido en este rol.

### ¿Puedo cambiar el rol de un usuario existente?

Sí, pero requiere acceso de `super_admin` a la base de datos o crear un endpoint específico para eso.

### ¿Qué pasa si el usuario intenta hacer algo no permitido?

Recibirá un error 403 (Forbidden) indicando que no tiene permisos suficientes.

---

## 📞 SOPORTE

Para más información o problemas con permisos:
- Email: soporte@tuempresa.com
- WhatsApp: [Tu número]

---

**Última actualización:** 2025-12-15

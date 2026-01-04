# 🔐 SISTEMA DE ROLES Y PERMISOS

Documentación completa del sistema de autenticación, autorización, roles y permisos de la API de Facturación Electrónica SUNAT Perú.

---

## 📋 ÍNDICE

1. [Introducción](#introducción)
2. [Arquitectura del Sistema](#arquitectura-del-sistema)
3. [Estructura de Base de Datos](#estructura-de-base-de-datos)
4. [Roles del Sistema](#roles-del-sistema)
5. [Permisos del Sistema](#permisos-del-sistema)
6. [Modelos y Relaciones](#modelos-y-relaciones)
7. [Autenticación con Laravel Sanctum](#autenticación-con-laravel-sanctum)
8. [Uso en Controladores y Middleware](#uso-en-controladores-y-middleware)
9. [Seguridad Adicional](#seguridad-adicional)
10. [Ejemplos Prácticos](#ejemplos-prácticos)
11. [API Endpoints](#api-endpoints)
12. [Mejores Prácticas](#mejores-prácticas)

---

## 📖 INTRODUCCIÓN

El sistema implementa un modelo de autorización **RBAC (Role-Based Access Control)** con las siguientes características:

- ✅ **5 roles predefinidos** del sistema (super_admin, company_admin, company_user, api_client, read_only)
- ✅ **Permisos granulares** por módulo y acción
- ✅ **Soporte para wildcards** (ej: `invoices.*`, `*`)
- ✅ **Multi-tenancy** (usuarios pueden pertenecer a una empresa)
- ✅ **Permisos adicionales por usuario** (override de rol)
- ✅ **Seguridad avanzada** (bloqueo de cuenta, restricción de IP, expiración de contraseña)
- ✅ **Autenticación API con Laravel Sanctum**

---

## 🏗️ ARQUITECTURA DEL SISTEMA

```
┌─────────────┐
│    USER     │
└──────┬──────┘
       │
       │ 1:1
       │
┌──────▼──────┐        ┌──────────────┐
│    ROLE     │ N:M    │ PERMISSION   │
│             ├────────┤              │
│ permissions │        │              │
└─────────────┘        └──────────────┘
       │
       │ tabla: role_permission
       │
       ▼
┌─────────────────────────────┐
│   Permisos Efectivos        │
│   del Usuario               │
│   = Permisos del Rol        │
│   + Permisos del Usuario    │
└─────────────────────────────┘
```

### Flujo de Autorización

1. Usuario hace petición con token Bearer
2. Middleware `auth:sanctum` verifica token
3. Sistema carga usuario, rol y permisos
4. Se valida permiso específico requerido
5. Se permite o deniega acceso

---

## 🗄️ ESTRUCTURA DE BASE DE DATOS

### Tabla: `users`

```sql
CREATE TABLE users (
    -- Campos básicos
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,

    -- Relaciones
    role_id BIGINT UNSIGNED NULL,
    company_id BIGINT UNSIGNED NULL,

    -- Tipo de usuario
    user_type VARCHAR(255) DEFAULT 'user', -- 'user', 'api_client', 'system'

    -- Seguridad
    allowed_ips JSON NULL, -- IPs permitidas
    permissions JSON NULL, -- Permisos adicionales específicos
    restrictions JSON NULL, -- Restricciones específicas

    -- Control de sesión
    last_login_at TIMESTAMP NULL,
    last_login_ip VARCHAR(255) NULL,
    failed_login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,

    -- Estado
    active BOOLEAN DEFAULT TRUE,
    force_password_change BOOLEAN DEFAULT FALSE,
    password_changed_at TIMESTAMP NULL,

    -- Metadata
    metadata JSON NULL,

    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE,

    INDEX idx_company_active (company_id, active),
    INDEX idx_user_type_active (user_type, active),
    INDEX idx_last_login (last_login_at)
);
```

### Tabla: `roles`

```sql
CREATE TABLE roles (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) UNIQUE NOT NULL, -- 'super_admin', 'company_admin', etc.
    display_name VARCHAR(255) NOT NULL, -- 'Super Administrador'
    description TEXT NULL,
    permissions JSON NULL, -- Array de permisos rápidos ['invoices.*', 'boletas.*']
    is_system BOOLEAN DEFAULT FALSE, -- No se puede eliminar
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_name_active (name, active)
);
```

### Tabla: `permissions`

```sql
CREATE TABLE permissions (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) UNIQUE NOT NULL, -- 'invoices.create', 'boletas.view'
    display_name VARCHAR(255) NOT NULL, -- 'Crear Facturas'
    description TEXT NULL,
    category VARCHAR(255) DEFAULT 'general', -- 'invoices', 'boletas', 'system'
    is_system BOOLEAN DEFAULT FALSE, -- Permisos críticos
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_category_active (category, active),
    INDEX idx_name_active (name, active)
);
```

### Tabla: `role_permission` (Pivot)

```sql
CREATE TABLE role_permission (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,

    UNIQUE KEY unique_role_permission (role_id, permission_id)
);
```

### Diagrama de Relaciones

```
users
  ├── role_id → roles.id (N:1)
  └── company_id → companies.id (N:1)

roles
  └── permissions (N:M) → role_permission → permissions
```

---

## 🎭 ROLES DEL SISTEMA

### 1. Super Administrador (`super_admin`)

**Nivel de Acceso:** TOTAL

| Campo | Valor |
|-------|-------|
| `name` | `super_admin` |
| `display_name` | Super Administrador |
| `permissions` | `['*']` (TODOS) |
| `is_system` | `true` |

**Descripción:** Control absoluto del sistema, todas las empresas y todos los usuarios.

**Capacidades:**
- ✅ Crear/editar/eliminar empresas
- ✅ Crear/editar/eliminar usuarios de cualquier empresa
- ✅ Acceso a todas las funcionalidades de todas las empresas
- ✅ Configurar el sistema completo
- ✅ Ver logs del sistema
- ✅ Gestionar roles y permisos

**Casos de Uso:**
- Desarrollador del sistema
- Administrador de la plataforma SaaS
- Soporte técnico nivel 3

---

### 2. Administrador de Empresa (`company_admin`)

**Nivel de Acceso:** EMPRESA COMPLETA

| Campo | Valor |
|-------|-------|
| `name` | `company_admin` |
| `display_name` | Administrador de Empresa |
| `permissions` | `['company.manage', 'invoices.*', 'boletas.*', 'credit_notes.*', 'debit_notes.*', 'dispatch_guides.*', 'daily_summaries.*', 'users.manage']` |
| `is_system` | `true` |

**Descripción:** Administra completamente una empresa específica (no puede ver otras empresas).

**Capacidades:**
- ✅ Gestionar su empresa (configuración, certificados, logos)
- ✅ TODAS las operaciones con documentos electrónicos
- ✅ Crear y gestionar usuarios de su empresa
- ✅ Ver dashboard y reportes completos
- ✅ Gestionar clientes, productos, sucursales
- ✅ Configurar correlativos
- ❌ NO puede ver/gestionar otras empresas
- ❌ NO puede crear super_admin

**Casos de Uso:**
- Contador de la empresa
- Gerente administrativo
- Dueño del negocio

---

### 3. Usuario de Empresa (`company_user`)

**Nivel de Acceso:** OPERACIONES DIARIAS

| Campo | Valor |
|-------|-------|
| `name` | `company_user` |
| `display_name` | Usuario de Empresa |
| `permissions` | `['invoices.create', 'invoices.view', 'invoices.send', 'boletas.create', 'boletas.view', 'boletas.send', 'credit_notes.create', 'credit_notes.view', 'debit_notes.create', 'debit_notes.view', 'dispatch_guides.create', 'dispatch_guides.view']` |
| `is_system` | `true` |

**Descripción:** Usuario operativo que emite comprobantes diariamente.

**Capacidades:**
- ✅ Crear facturas, boletas, notas de crédito/débito
- ✅ Ver documentos
- ✅ Enviar a SUNAT
- ✅ Generar PDFs
- ✅ Descargar XML/CDR
- ❌ NO puede eliminar documentos
- ❌ NO puede editar documentos enviados
- ❌ NO puede gestionar usuarios
- ❌ NO puede configurar empresa

**Casos de Uso:**
- Vendedor
- Cajero
- Asistente administrativo
- Emisor de comprobantes

---

### 4. Cliente API (`api_client`)

**Nivel de Acceso:** SOLO CREAR Y VER

| Campo | Valor |
|-------|-------|
| `name` | `api_client` |
| `display_name` | Cliente API |
| `permissions` | `['api.access', 'invoices.create', 'invoices.view', 'boletas.create', 'boletas.view']` |
| `is_system` | `true` |

**Descripción:** Acceso API externo con permisos mínimos para integración.

**Capacidades:**
- ✅ Crear facturas
- ✅ Crear boletas
- ✅ Ver factura individual (por ID)
- ✅ Ver boleta individual (por ID)
- ❌ NO puede listar documentos
- ❌ NO puede editar documentos
- ❌ NO puede eliminar documentos
- ❌ NO puede enviar a SUNAT
- ❌ NO puede crear notas de crédito/débito
- ❌ NO puede ver dashboard

**Casos de Uso:**
- Sistema POS externo
- Plataforma e-commerce
- App móvil de ventas
- Integración con ERP

---

### 5. Solo Lectura (`read_only`)

**Nivel de Acceso:** SOLO CONSULTA

| Campo | Valor |
|-------|-------|
| `name` | `read_only` |
| `display_name` | Solo Lectura |
| `permissions` | `['invoices.view', 'boletas.view', 'credit_notes.view', 'debit_notes.view', 'dispatch_guides.view', 'reports.view']` |
| `is_system` | `true` |

**Descripción:** Acceso de solo lectura para auditoría o consulta.

**Capacidades:**
- ✅ Ver todos los documentos
- ✅ Ver reportes
- ✅ Descargar archivos (PDF, XML, CDR)
- ❌ NO puede crear NADA
- ❌ NO puede editar NADA
- ❌ NO puede eliminar NADA
- ❌ NO puede enviar a SUNAT

**Casos de Uso:**
- Auditor externo
- Cliente que solo consulta
- Supervisor de lectura
- Analista de datos

---

## 🔑 PERMISOS DEL SISTEMA

### Categorías de Permisos

El sistema organiza los permisos en **11 categorías:**

1. **system** - Sistema general
2. **companies** - Empresas
3. **users** - Usuarios
4. **invoices** - Facturas
5. **boletas** - Boletas
6. **credit_notes** - Notas de Crédito
7. **debit_notes** - Notas de Débito
8. **dispatch_guides** - Guías de Remisión
9. **daily_summaries** - Resúmenes Diarios
10. **voided_documents** - Comunicaciones de Baja
11. **reports** - Reportes
12. **config** - Configuraciones

---

### Permisos por Categoría

#### 1️⃣ SYSTEM (Sistema General)

| Permiso | Nombre | Descripción |
|---------|--------|-------------|
| `system.manage` | Administrar Sistema | Acceso completo al sistema |
| `system.config` | Configurar Sistema | Configurar parámetros del sistema |
| `system.logs` | Ver Logs | Acceder a logs del sistema |
| `api.access` | Acceso API | Acceso básico a la API |

---

#### 2️⃣ COMPANIES (Empresas)

| Permiso | Nombre | Descripción |
|---------|--------|-------------|
| `companies.view` | Ver Empresas | Ver información de empresas |
| `companies.create` | Crear Empresas | Crear nuevas empresas |
| `companies.update` | Editar Empresas | Editar información de empresas |
| `companies.delete` | Eliminar Empresas | Eliminar empresas |
| `companies.manage` | Administrar Empresa | Administrar completamente una empresa |
| `companies.config` | Configurar Empresa | Configurar parámetros de empresa |

---

#### 3️⃣ USERS (Usuarios)

| Permiso | Nombre | Descripción |
|---------|--------|-------------|
| `users.view` | Ver Usuarios | Ver usuarios del sistema |
| `users.create` | Crear Usuarios | Crear nuevos usuarios |
| `users.update` | Editar Usuarios | Editar información de usuarios |
| `users.delete` | Eliminar Usuarios | Eliminar usuarios |
| `users.manage` | Administrar Usuarios | Administrar usuarios de la empresa |
| `users.roles` | Asignar Roles | Asignar y modificar roles |

---

#### 4️⃣ INVOICES (Facturas)

| Permiso | Nombre | Descripción |
|---------|--------|-------------|
| `invoices.view` | Ver Facturas | Ver facturas |
| `invoices.create` | Crear Facturas | Crear nuevas facturas |
| `invoices.update` | Editar Facturas | Editar facturas existentes |
| `invoices.delete` | Eliminar Facturas | Eliminar facturas |
| `invoices.send` | Enviar Facturas | Enviar facturas a SUNAT |
| `invoices.download` | Descargar Facturas | Descargar XML/PDF/CDR |

**Wildcard:** `invoices.*` = Todos los permisos de facturas

---

#### 5️⃣ BOLETAS (Boletas de Venta)

| Permiso | Nombre | Descripción |
|---------|--------|-------------|
| `boletas.view` | Ver Boletas | Ver boletas |
| `boletas.create` | Crear Boletas | Crear nuevas boletas |
| `boletas.update` | Editar Boletas | Editar boletas existentes |
| `boletas.delete` | Eliminar Boletas | Eliminar boletas |
| `boletas.send` | Enviar Boletas | Enviar boletas a SUNAT |
| `boletas.download` | Descargar Boletas | Descargar XML/PDF/CDR |

**Wildcard:** `boletas.*` = Todos los permisos de boletas

---

#### 6️⃣ CREDIT_NOTES (Notas de Crédito)

| Permiso | Nombre | Descripción |
|---------|--------|-------------|
| `credit_notes.view` | Ver Notas de Crédito | Ver notas de crédito |
| `credit_notes.create` | Crear Notas de Crédito | Crear notas de crédito |
| `credit_notes.update` | Editar Notas de Crédito | Editar notas de crédito |
| `credit_notes.delete` | Eliminar Notas de Crédito | Eliminar notas de crédito |
| `credit_notes.send` | Enviar Notas de Crédito | Enviar notas de crédito a SUNAT |
| `credit_notes.download` | Descargar Notas de Crédito | Descargar archivos |

**Wildcard:** `credit_notes.*` = Todos los permisos de notas de crédito

---

#### 7️⃣ DEBIT_NOTES (Notas de Débito)

| Permiso | Nombre | Descripción |
|---------|--------|-------------|
| `debit_notes.view` | Ver Notas de Débito | Ver notas de débito |
| `debit_notes.create` | Crear Notas de Débito | Crear notas de débito |
| `debit_notes.update` | Editar Notas de Débito | Editar notas de débito |
| `debit_notes.delete` | Eliminar Notas de Débito | Eliminar notas de débito |
| `debit_notes.send` | Enviar Notas de Débito | Enviar notas de débito a SUNAT |
| `debit_notes.download` | Descargar Notas de Débito | Descargar archivos |

**Wildcard:** `debit_notes.*` = Todos los permisos de notas de débito

---

#### 8️⃣ DISPATCH_GUIDES (Guías de Remisión)

| Permiso | Nombre | Descripción |
|---------|--------|-------------|
| `dispatch_guides.view` | Ver Guías de Remisión | Ver guías de remisión |
| `dispatch_guides.create` | Crear Guías de Remisión | Crear guías de remisión |
| `dispatch_guides.update` | Editar Guías de Remisión | Editar guías de remisión |
| `dispatch_guides.delete` | Eliminar Guías de Remisión | Eliminar guías de remisión |
| `dispatch_guides.send` | Enviar Guías de Remisión | Enviar guías a SUNAT |
| `dispatch_guides.check` | Consultar Estado GRE | Consultar estado en SUNAT |
| `dispatch_guides.download` | Descargar Guías | Descargar archivos |

**Wildcard:** `dispatch_guides.*` = Todos los permisos de guías de remisión

---

#### 9️⃣ DAILY_SUMMARIES (Resúmenes Diarios)

| Permiso | Nombre | Descripción |
|---------|--------|-------------|
| `daily_summaries.view` | Ver Resúmenes Diarios | Ver resúmenes diarios |
| `daily_summaries.create` | Crear Resúmenes Diarios | Crear resúmenes diarios |
| `daily_summaries.send` | Enviar Resúmenes | Enviar resúmenes a SUNAT |
| `daily_summaries.check` | Consultar Estado | Consultar estado en SUNAT |
| `daily_summaries.download` | Descargar Resúmenes | Descargar archivos |

**Wildcard:** `daily_summaries.*` = Todos los permisos de resúmenes diarios

---

#### 🔟 VOIDED_DOCUMENTS (Comunicaciones de Baja)

| Permiso | Nombre | Descripción |
|---------|--------|-------------|
| `voided_documents.view` | Ver Comunicaciones de Baja | Ver comunicaciones de baja |
| `voided_documents.create` | Crear Comunicaciones de Baja | Crear comunicaciones de baja |
| `voided_documents.send` | Enviar Comunicaciones | Enviar comunicaciones a SUNAT |
| `voided_documents.check` | Consultar Estado | Consultar estado en SUNAT |
| `voided_documents.download` | Descargar Comunicaciones | Descargar archivos |

**Wildcard:** `voided_documents.*` = Todos los permisos de comunicaciones de baja

---

#### 1️⃣1️⃣ REPORTS (Reportes)

| Permiso | Nombre | Descripción |
|---------|--------|-------------|
| `reports.view` | Ver Reportes | Ver reportes del sistema |
| `reports.export` | Exportar Reportes | Exportar reportes en diferentes formatos |

**Wildcard:** `reports.*` = Todos los permisos de reportes

---

#### 1️⃣2️⃣ CONFIG (Configuraciones)

| Permiso | Nombre | Descripción |
|---------|--------|-------------|
| `config.view` | Ver Configuraciones | Ver configuraciones |
| `config.update` | Editar Configuraciones | Editar configuraciones |

**Wildcard:** `config.*` = Todos los permisos de configuración

---

### Wildcards y Patrones

El sistema soporta permisos comodín (wildcards):

| Patrón | Significado | Ejemplo |
|--------|-------------|---------|
| `*` | TODOS los permisos | Super Admin |
| `invoices.*` | Todos los permisos de facturas | `invoices.create`, `invoices.view`, `invoices.update`, etc. |
| `boletas.*` | Todos los permisos de boletas | `boletas.create`, `boletas.view`, `boletas.update`, etc. |

**Ejemplo de expansión:**

```php
'invoices.*' se expande a:
[
    'invoices.view',
    'invoices.create',
    'invoices.update',
    'invoices.delete',
    'invoices.send',
    'invoices.download'
]
```

---

## 🧩 MODELOS Y RELACIONES

### Modelo: `User`

**Ubicación:** `app/Models/User.php`

#### Relaciones

```php
// Usuario pertenece a un Rol
public function role(): BelongsTo
{
    return $this->belongsTo(Role::class);
}

// Usuario pertenece a una Empresa
public function company(): BelongsTo
{
    return $this->belongsTo(Company::class);
}
```

#### Métodos Principales

##### 1. Verificar Permiso

```php
/**
 * Verificar si el usuario tiene un permiso específico
 */
public function hasPermission(string $permission): bool
```

**Ejemplo:**
```php
if ($user->hasPermission('invoices.create')) {
    // Permitir crear factura
}
```

##### 2. Verificar Múltiples Permisos (OR)

```php
/**
 * Verificar si el usuario tiene CUALQUIERA de los permisos dados
 */
public function hasAnyPermission(array $permissions): bool
```

**Ejemplo:**
```php
if ($user->hasAnyPermission(['invoices.create', 'boletas.create'])) {
    // Puede crear facturas O boletas
}
```

##### 3. Verificar Múltiples Permisos (AND)

```php
/**
 * Verificar si el usuario tiene TODOS los permisos dados
 */
public function hasAllPermissions(array $permissions): bool
```

**Ejemplo:**
```php
if ($user->hasAllPermissions(['invoices.create', 'invoices.send'])) {
    // Puede crear Y enviar facturas
}
```

##### 4. Verificar Rol

```php
/**
 * Verificar si el usuario tiene un rol específico
 */
public function hasRole(string $roleName): bool
```

**Ejemplo:**
```php
if ($user->hasRole('super_admin')) {
    // Es super administrador
}
```

##### 5. Obtener Todos los Permisos

```php
/**
 * Obtener todos los permisos del usuario (rol + usuario)
 */
public function getAllPermissions(): array
```

**Ejemplo:**
```php
$permisos = $user->getAllPermissions();
// ['invoices.create', 'invoices.view', 'boletas.create', ...]
```

##### 6. Verificar Acceso a Empresa

```php
/**
 * Verificar si el usuario puede acceder a una empresa
 */
public function canAccessCompany(int $companyId): bool
```

**Ejemplo:**
```php
if ($user->canAccessCompany(1)) {
    // Puede acceder a la empresa con ID 1
}
```

##### 7. Bloqueo de Cuenta

```php
/**
 * Verificar si el usuario está bloqueado
 */
public function isLocked(): bool

/**
 * Incrementar intentos fallidos de login
 */
public function incrementFailedLoginAttempts(): void

/**
 * Registrar login exitoso
 */
public function recordSuccessfulLogin(string $ip): void
```

**Ejemplo:**
```php
if ($user->isLocked()) {
    return response()->json(['error' => 'Cuenta bloqueada'], 401);
}
```

##### 8. Restricción de IP

```php
/**
 * Verificar si la IP está permitida
 */
public function isIpAllowed(string $ip): bool
```

**Ejemplo:**
```php
if (!$user->isIpAllowed($request->ip())) {
    return response()->json(['error' => 'IP no autorizada'], 403);
}
```

---

### Modelo: `Role`

**Ubicación:** `app/Models/Role.php`

#### Métodos Principales

```php
// Verificar si el rol tiene un permiso
public function hasPermission(string $permission): bool

// Asignar permiso al rol
public function givePermission(string|Permission $permission): self

// Revocar permiso del rol
public function revokePermission(string|Permission $permission): self

// Sincronizar permisos
public function syncPermissions(array $permissions): self

// Obtener todos los permisos
public function getAllPermissions(): array

// Obtener roles del sistema
public static function getSystemRoles(): array
```

---

### Modelo: `Permission`

**Ubicación:** `app/Models/Permission.php`

#### Métodos Principales

```php
// Obtener permisos del sistema
public static function getSystemPermissions(): array

// Obtener permisos por categoría
public static function getPermissionsByCategory(string $category): array

// Obtener categorías
public static function getCategories(): array

// Verificar si un permiso existe
public static function permissionExists(string $permission): bool

// Verificar si es wildcard
public static function isWildcardPermission(string $permission): bool

// Expandir wildcard
public static function expandWildcardPermission(string $permission): array

// Verificar coincidencia con patrón
public static function matchesPattern(string $permission, string $pattern): bool
```

---

## 🔐 AUTENTICACIÓN CON LARAVEL SANCTUM

### 1. Inicializar Sistema

**Endpoint:** `POST /api/auth/initialize`

Crea el primer super admin y genera roles y permisos.

```json
{
  "name": "Super Admin",
  "email": "admin@empresa.com",
  "password": "Admin123456!"
}
```

### 2. Login

**Endpoint:** `POST /api/auth/login`

```json
{
  "email": "admin@empresa.com",
  "password": "Admin123456!"
}
```

**Response:**
```json
{
  "message": "Login exitoso",
  "user": {
    "id": 1,
    "name": "Super Admin",
    "email": "admin@empresa.com",
    "role": "Super Administrador",
    "permissions": ["*"]
  },
  "access_token": "1|abcdef123456...",
  "token_type": "Bearer"
}
```

### 3. Usar Token en Peticiones

```http
GET /api/v1/invoices
Authorization: Bearer 1|abcdef123456...
Accept: application/json
```

### 4. Verificar Usuario Autenticado

**Endpoint:** `GET /api/v1/auth/me`

```json
{
  "user": {
    "id": 1,
    "name": "Super Admin",
    "email": "admin@empresa.com",
    "role": "Super Administrador",
    "company": null,
    "permissions": ["*"],
    "last_login_at": "2025-12-15T10:00:00Z"
  }
}
```

### 5. Logout

**Endpoint:** `POST /api/v1/auth/logout`

Elimina el token actual.

---

## 🛡️ USO EN CONTROLADORES Y MIDDLEWARE

### Middleware de Autenticación

```php
// En routes/api.php
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/invoices', [InvoiceController::class, 'index']);
});
```

### Verificar Permiso en Controlador

#### Opción 1: Manualmente

```php
public function store(Request $request)
{
    if (!$request->user()->hasPermission('invoices.create')) {
        return response()->json([
            'success' => false,
            'message' => 'No tienes permiso para crear facturas'
        ], 403);
    }

    // Crear factura...
}
```

#### Opción 2: Con Gate

```php
// En AuthServiceProvider.php
Gate::define('create-invoice', function ($user) {
    return $user->hasPermission('invoices.create');
});

// En el controlador
public function store(Request $request)
{
    $this->authorize('create-invoice');

    // Crear factura...
}
```

#### Opción 3: Con Middleware Personalizado

```php
// Crear middleware: app/Http/Middleware/CheckPermission.php
public function handle($request, Closure $next, $permission)
{
    if (!$request->user()->hasPermission($permission)) {
        abort(403, 'No tienes permiso para realizar esta acción');
    }

    return $next($request);
}

// En routes/api.php
Route::post('/invoices', [InvoiceController::class, 'store'])
    ->middleware('permission:invoices.create');
```

### Verificar Rol

```php
if ($request->user()->hasRole('super_admin')) {
    // Lógica especial para super admin
}
```

### Verificar Acceso a Empresa

```php
public function show(Request $request, $id)
{
    $invoice = Invoice::findOrFail($id);

    if (!$request->user()->canAccessCompany($invoice->company_id)) {
        return response()->json([
            'success' => false,
            'message' => 'No tienes acceso a esta empresa'
        ], 403);
    }

    return response()->json(['data' => $invoice]);
}
```

---

## 🔒 SEGURIDAD ADICIONAL

### 1. Bloqueo de Cuenta

Después de **5 intentos fallidos de login**, la cuenta se bloquea por **30 minutos**.

```php
// Automático en el modelo User
if ($this->failed_login_attempts >= 5) {
    $this->update(['locked_until' => now()->addMinutes(30)]);
}
```

### 2. Restricción de IP

```php
// En users table
'allowed_ips' => ['192.168.1.100', '10.0.0.0/24']

// Verificar
if (!$user->isIpAllowed($request->ip())) {
    abort(403, 'IP no autorizada');
}
```

### 3. Expiración de Contraseña

Las contraseñas expiran después de **90 días**.

```php
if ($user->mustChangePassword()) {
    return response()->json([
        'message' => 'Debes cambiar tu contraseña'
    ], 403);
}
```

### 4. Rate Limiting

```php
// En routes/api.php
Route::post('/auth/login')->middleware('throttle:5,1'); // 5 intentos por minuto
Route::middleware('throttle:60,1')->group(function () { // 60 requests por minuto
    Route::get('/invoices', ...);
});
```

---

## 💡 EJEMPLOS PRÁCTICOS

### Ejemplo 1: Crear Usuario API Client

```php
POST /api/v1/auth/create-user
Authorization: Bearer {super_admin_token}

{
  "name": "Sistema POS",
  "email": "pos@empresa.com",
  "password": "POS123456!",
  "role_name": "api_client",
  "company_id": 1,
  "user_type": "api_client"
}
```

### Ejemplo 2: Crear Usuario con Permisos Personalizados

```php
$user = User::create([
    'name' => 'Usuario Especial',
    'email' => 'especial@empresa.com',
    'password' => Hash::make('password'),
    'role_id' => $roleCompanyUser->id,
    'company_id' => 1,
    'permissions' => ['dispatch_guides.create', 'dispatch_guides.send'], // Extra permisos
]);
```

### Ejemplo 3: Verificar Permisos en Blade (si usas vistas)

```blade
@can('create-invoice')
    <button>Crear Factura</button>
@endcan
```

### Ejemplo 4: Restringir Usuario a IPs Específicas

```php
$user->update([
    'allowed_ips' => ['192.168.1.100', '192.168.1.101']
]);
```

### Ejemplo 5: Crear Rol Personalizado

```php
$customRole = Role::create([
    'name' => 'vendedor_especial',
    'display_name' => 'Vendedor Especial',
    'description' => 'Vendedor con permisos especiales',
    'permissions' => ['invoices.create', 'boletas.create', 'dispatch_guides.create'],
    'is_system' => false
]);
```

---

## 🌐 API ENDPOINTS

### Autenticación

| Método | Endpoint | Descripción | Auth |
|--------|----------|-------------|------|
| POST | `/api/auth/initialize` | Inicializar sistema | ❌ No |
| POST | `/api/auth/login` | Login | ❌ No |
| POST | `/api/v1/auth/logout` | Logout | ✅ Sí |
| GET | `/api/v1/auth/me` | Info usuario actual | ✅ Sí |
| POST | `/api/v1/auth/create-user` | Crear usuario | ✅ Sí (super_admin) |

### Verificación de Permisos

No hay endpoints específicos, la verificación se hace a nivel de código:

```php
$user->hasPermission('invoices.create'); // true/false
$user->hasRole('super_admin'); // true/false
$user->canAccessCompany(1); // true/false
```

---

## ✅ MEJORES PRÁCTICAS

### 1. Nunca Hardcodear Roles en Lógica de Negocio

❌ **Mal:**
```php
if ($user->role->name === 'super_admin') {
    // lógica
}
```

✅ **Bien:**
```php
if ($user->hasPermission('system.manage')) {
    // lógica
}
```

### 2. Usar Permisos Específicos en Lugar de Roles

❌ **Mal:**
```php
if ($user->hasRole('company_admin')) {
    // permitir crear factura
}
```

✅ **Bien:**
```php
if ($user->hasPermission('invoices.create')) {
    // permitir crear factura
}
```

### 3. Validar Acceso a Empresa en Multi-tenancy

```php
// Siempre verificar que el usuario puede acceder a la empresa
if (!$user->canAccessCompany($invoice->company_id)) {
    abort(403);
}
```

### 4. Usar Scopes en Eloquent

```php
// En lugar de filtrar manualmente
$invoices = Invoice::where('company_id', $user->company_id)->get();

// Crear scope en modelo Invoice
public function scopeForUser($query, User $user)
{
    if (!$user->hasRole('super_admin')) {
        $query->where('company_id', $user->company_id);
    }
}

// Usar
$invoices = Invoice::forUser($user)->get();
```

### 5. Proteger Rutas Sensibles

```php
// Rutas que solo super_admin puede acceder
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/auth/create-user', function (Request $request) {
        if (!$request->user()->hasRole('super_admin')) {
            abort(403);
        }
        // lógica...
    });
});
```

### 6. Auditar Cambios Críticos

```php
// Registrar cuando se crean/modifican usuarios
Log::info('Usuario creado', [
    'created_by' => auth()->user()->id,
    'new_user' => $newUser->id,
    'role' => $newUser->role->name
]);
```

### 7. Rotación de Tokens

```php
// Eliminar tokens antiguos periódicamente
User::find($userId)->tokens()->delete(); // Eliminar todos
$user->createToken('nuevo_token'); // Crear nuevo
```

### 8. No Exponer Información Sensible

```php
// Ocultar campos sensibles en JSON
protected $hidden = ['password', 'remember_token', 'allowed_ips'];
```

---

## 📚 RESUMEN

### Flujo Completo

1. **Inicialización** → Crear super_admin
2. **Login** → Obtener token Bearer
3. **Request** → Enviar token en header `Authorization: Bearer {token}`
4. **Middleware** → `auth:sanctum` verifica token
5. **Autorización** → Verificar permiso con `$user->hasPermission()`
6. **Respuesta** → 200 OK o 403 Forbidden

### Tabla de Decisión: ¿Qué Rol Usar?

| Necesidad | Rol Recomendado |
|-----------|----------------|
| Acceso total al sistema | `super_admin` |
| Gestionar una empresa completa | `company_admin` |
| Emitir comprobantes diariamente | `company_user` |
| Integración API externa (solo crear) | `api_client` |
| Solo consultar y ver reportes | `read_only` |

---

## 📞 SOPORTE

Para más información:
- **Archivo de Modelos:** `app/Models/User.php`, `app/Models/Role.php`, `app/Models/Permission.php`
- **Migraciones:** `database/migrations/2025_09_07_*.php`
- **Seeder:** `database/seeders/RolesAndPermissionsSeeder.php`
- **Controlador Auth:** `app/Http/Controllers/Api/AuthController.php`

---

**Última actualización:** 2025-12-15

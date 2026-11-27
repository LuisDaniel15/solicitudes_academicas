# Guía de uso de SIGESU

**SIGESU** — Sistema de Gestión de Solicitudes Universitarias.
MVP funcional: Laravel 13 + React 19 (Vite) + PostgreSQL + Sanctum.

Esta guía explica cómo levantar el sistema y cómo probarlo por primera vez.

---

## 1. Requisitos

| Herramienta | Versión verificada |
|---|---|
| PHP | 8.3.30 (con extensión `pdo_pgsql`) |
| Composer | 2.9.5 |
| Node / npm | 24.14.1 / 11.11.0 |
| PostgreSQL | 17 (escuchando en `127.0.0.1:5432`) |

Bases de datos creadas:

- `sigesu` → aplicación
- `sigesu_test` → pruebas automáticas

---

## 2. Instalación inicial (solo la primera vez)

Si acabas de clonar el repositorio:

```bash
cd solicitudes_academicas

composer install
npm install

# Windows PowerShell:
Copy-Item .env.example .env
php artisan key:generate
```

Edita `.env` con tus credenciales reales de PostgreSQL:

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sigesu
DB_USERNAME=postgres
DB_PASSWORD=12345678
```

Luego prepara la base y compila el frontend:

```bash
php artisan migrate:fresh --seed --force
npm run build
```

> `migrate:fresh --seed` **borra** las solicitudes existentes y deja solo los usuarios y categorías de demostración.

---

## 3. Levantar el sistema

### Opción A — Rápida (usa los assets ya compilados)

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Abre en el navegador:

```
http://127.0.0.1:8000/login
```

### Opción B — Desarrollo (si vas a modificar React)

```bash
composer dev
```

Levanta en paralelo:

- Laravel en `http://127.0.0.1:8000`
- Cola de trabajos
- Vite en `http://127.0.0.1:5173`

Abre igualmente `http://127.0.0.1:8000/login`. Laravel inyecta el servidor de Vite automáticamente; el proxy `/api` de Vite apunta al puerto 8000.

---

## 4. Verificación rápida

```bash
composer test              # 31 pruebas / 116 aserciones
vendor/bin/pint --test     # estilo de código
npm run build              # compilación del frontend
php artisan migrate:status # estado de las migraciones
```

---

## 5. Cuentas de demostración

Todas usan la contraseña **`password123`**.

| Rol | Correo | Qué puede hacer |
|---|---|---|
| Administrador | `admin@sigesu.test` | Usuarios, categorías, solicitudes, reportes |
| Estudiante | `estudiante@sigesu.test` | Registrar y ver **sus** solicitudes |
| Funcionario | `funcionario@sigesu.test` | Atender **sus** solicitudes asignadas |
| Supervisor | `supervisor@sigesu.test` | Asignar, rechazar, cerrar, reabrir y ver reportes |

> Recomendación: abre cada rol en una ventana de incógnito distinta para no perder el token al cambiar de usuario.

---

## 6. Flujo de estados

```
REGISTRADA ──> ASIGNADA ──> EN_PROCESO ──> RESUELTA ──> CERRADA
     │             │
     └─────────────┴──> RECHAZADA (terminal)

CERRADA ──(supervisor + justificación)──> REABIERTA ──> EN_PROCESO
```

No existe ninguna otra transición. Una solicitud **CERRADA** es inmutable.

---

## 7. Primeros pasos (recorrido guiado)

### Paso 1 — El estudiante registra una solicitud

1. Ingresa con `estudiante@sigesu.test`.
2. En el menú, **Nueva solicitud**.
3. Elige categoría (por ejemplo *Matrícula*), prioridad *Alta* y escribe una descripción de al menos 10 caracteres.
4. Al guardar, verás el detalle con:
   - Código automático tipo `SOL-2026-000001`
   - Estado **Registrada**
   - Historial de estados inicial
5. En **Mis solicitudes** prueba los filtros: estado, categoría, prioridad y búsqueda por código o texto.

### Paso 2 — El supervisor asigna

1. Ingresa con `supervisor@sigesu.test`.
2. **Solicitudes** → busca el código creado.
3. Botón **Asignar** → selecciona `Carlos Funcionario`.
4. El estado pasa a **Asignada** y se registra la asignación en el historial.
5. El funcionario recibe una notificación interna.

### Paso 3 — El funcionario atiende y resuelve

1. Ingresa con `funcionario@sigesu.test`.
2. El icono 🔔 del encabezado muestra la notificación de asignación.
3. **Solicitudes asignadas** → abre la solicitud.
4. **Iniciar atención** → estado **En proceso**.
5. Registra una **Nueva observación** si quieres dejar constancia.
6. En **Marcar como resuelta**, escribe la solución y confirma → estado **Resuelta**.
7. El supervisor recibe la notificación *“Solicitud resuelta pendiente de cierre”*.

### Paso 4 — El supervisor cierra y reabre

1. Como supervisor, abre la solicitud → **Autorizar cierre** → estado **Cerrada**.
2. Cambia al funcionario e intenta agregar una observación: debe fallar (solicitud cerrada).
3. Vuelve al supervisor → **Reabrir solicitud** con una justificación → estado **Reabierta**.
4. El funcionario puede volver a **Iniciar atención**.

### Paso 5 — Rechazo (opcional)

Sobre una solicitud en **Registrada** o **Asignada**, el supervisor usa **Rechazar solicitud** e indica el motivo → estado **Rechazada** (terminal).

### Paso 6 — Administración

Como `admin@sigesu.test`:

- **Usuarios**: crea uno, cámbiale el rol, actívalo/desactívalo (no puedes desactivarte a ti mismo).
- **Categorías**: crea una, edítala, desactívala. Una categoría inactiva no aparece al crear solicitudes.
- **Reportes**: totales por estado y por categoría.

### Paso 7 — Verifica los permisos

- Un estudiante **no ve** usuarios, categorías ni reportes: no aparecen en su menú y la API responde `403`.
- Un estudiante **no puede** ver la solicitud de otro estudiante (`403`).
- Un funcionario **no puede** atender una solicitud que no le fue asignada.

---

## 8. Reglas de negocio que se aplican automáticamente

- Solo usuarios autenticados y activos pueden operar.
- La categoría debe estar activa al registrar.
- El código es único y se genera automáticamente.
- El estado inicial es siempre `REGISTRADA`.
- Solo administrador o supervisor pueden asignar/reasignar.
- El funcionario solo puede actuar sobre solicitudes asignadas.
- `RESUELTA` exige una solución u observación final.
- `RECHAZADA` exige motivo; `REABIERTA` exige justificación.
- Solo el supervisor puede cerrar y reabrir.
- `CERRADA` no admite más cambios salvo reapertura.
- Todo cambio de estado y asignación queda en el historial (actor, fecha, valores y nota).
- Notificaciones internas: al estudiante (cambio de estado), al funcionario (asignación), a supervisores (resuelta pendiente de cierre). No se envían correos ni mensajes externos.

---

## 9. Probar la API directamente (opcional)

```powershell
$body = @{ email = 'estudiante@sigesu.test'; password = 'password123' } | ConvertTo-Json
$login = Invoke-RestMethod http://127.0.0.1:8000/api/login -Method Post -ContentType 'application/json' -Body $body

# Token Bearer
$login.token

# Solicitudes del usuario autenticado
Invoke-RestMethod http://127.0.0.1:8000/api/requests -Headers @{ Authorization = "Bearer $($login.token)" }
```

Rutas principales:

| Método | Ruta | Descripción |
|---|---|---|
| POST | `/api/login` | Obtener token |
| POST | `/api/logout` | Revocar token actual |
| GET | `/api/me` | Usuario autenticado |
| GET/POST | `/api/requests` | Listar / crear solicitudes |
| GET | `/api/requests/{id}` | Detalle con historial |
| POST | `/api/requests/{id}/assignments` | Asignar (admin/supervisor) |
| POST | `/api/requests/{id}/observations` | Observación (funcionario) |
| PATCH | `/api/requests/{id}/status` | Cambiar estado |
| GET | `/api/notifications` | Notificaciones propias |
| GET | `/api/reports/summary` | Reportes (admin/supervisor) |

---

## 10. Solución de problemas

| Síntoma | Acción |
|---|---|
| Página en blanco o sin estilos | `npm run build` y verifica `public/build/manifest.json` |
| Error 500 / problema de BD | Confirma que PostgreSQL esté activo y que `DB_PASSWORD` en `.env` sea correcto |
| Error 401 en la API | El token venció o se revocó; inicia sesión de nuevo |
| 403 al acceder | El rol no tiene permiso para esa operación |
| Puerto 8000 ocupado | `php artisan serve --port=8001` (si usas Vite, ajusta el proxy en `vite.config.js`) |
| Cambios de configuración sin efecto | `php artisan optimize:clear` |
| Quieres empezar de cero | `php artisan migrate:fresh --seed --force` |

---

## 11. Comandos útiles de Git

```bash
git add .
git commit -m "descripcion del cambio"
git push
```

Rama principal: `main`. Remoto: `origin` → `https://github.com/LuisDaniel15/solicitudes_academicas.git`.

# Wellness Circle Academy — Manual Maestro

Documento de operación, mantenimiento y referencia técnica del sistema. Producido al cierre del MVP. Lenguaje directo, orientado a ejecución. Si algo cambia en el futuro, este manual debe actualizarse junto con el código.

Última actualización: 2026-05-25 (commit base: `9fb138a`).

---

## 1. Qué es este proyecto

Plataforma web privada de duplicación para equipos de bienestar y network marketing. Los administradores cargan programas (Arranque, X39, Cellergize, Glutathione, Silent Night, Liderazgo, Redes Sociales, Testimonios, Herramientas, etc.); cada programa contiene lecciones diarias con publicación lista para copiar, story sugerida, conversación ejemplo, imagen descargable, video, checklist y link de descarga. Los miembros entran cada día, copian el contenido y ejecutan acciones desde su celular.

URL pública: `https://wellnessca.martavilla.com.mx`
Repositorio: `git@github-personal:rubmar007/wellness-circle-academy.git`

---

## 2. Stack técnico

### Backend
- PHP 8.3 (fijado en `composer.json` con `^8.3` y `config.platform.php = 8.3.13`).
- Composer con PSR-4 (`App\` → `src/`).
- Dependencias:
  - `vlucas/phpdotenv ^5.6` — carga variables desde `.env` en local. En producción Railway inyecta env vars directo.
  - `openspout/openspout ^5.3` — lectura/escritura de XLSX para batch import.
- Extensiones PHP requeridas: `pdo`, `pdo_pgsql`, `mbstring`, `fileinfo`, `curl`, `zip`.

### Base de datos
- PostgreSQL 17 en Neon (https://neon.tech).
- Conexión vía variable de entorno `DATABASE_URL`.
- Conexión "pooled" de Neon (URL con `-pooler` en el host).
- PDO configurado con `EMULATE_PREPARES=true`. Ver sección 12 y 14 para entender por qué.

### Frontend
- HTML + CSS moderno (mobile-first, paleta del documento de alcance: azul marino, azul celeste, blanco, dorado suave).
- Sin frameworks de cliente (React, Vue, Angular están prohibidos por el CLAUDE.md del proyecto).
- Único archivo JavaScript permitido: `public/assets/js/copy.js` (~25 líneas, vanilla, solo para el botón "Copiar texto" via Clipboard API). Cualquier otro JS requiere autorización explícita.
- HTMX disponible para casos futuros que requieran reactividad servidor-cliente. Actualmente no se usa.

### Email
- Mailgun (https://mailgun.com).
- Dominio configurado en Mailgun: `mail.5t4d10.com` (subdominio del dominio personal de Rub).
- Región: US (`api.mailgun.net`).
- Sender: `info@5t4d10.com`.
- Implementación: `src/Mailer.php` llama `POST /v3/{domain}/messages` con auth básica vía cURL directo. Sin SDK.

### Hosting
- Railway (https://railway.com).
- Plan: Hobby.
- Builder: Railpack (no Nixpacks).
- Custom domain: `wellnessca.martavilla.com.mx` con certificado Let's Encrypt automático.
- Region del servicio: US West (California).
- Storage persistente: Railway Volume de 5 GB montado en `/app/public/assets/uploads`.

---

## 3. Estructura del repositorio

```
.
├── CLAUDE.md                Contrato de trabajo con la asistencia IA del proyecto.
├── MANUAL.md                Este documento.
├── README.md                Resumen corto del proyecto.
├── Procfile                 Comando de arranque para Railway/Railpack.
├── nixpacks.toml            Config alternativa para Nixpacks (Railway no la usa, queda como fallback).
├── composer.json            Dependencias, PSR-4, platform PHP fijado.
├── composer.lock            Lock file con versiones exactas.
├── .env.example             Plantilla de variables de entorno (versionada, sin valores reales).
├── .gitignore               Excluye .env, vendor/, uploads/ (salvo .gitkeep y .htaccess).
├── bin/
│   └── create-user.php      Script CLI interactivo para crear usuarios (único camino fuera del panel admin).
├── database/
│   ├── schema.sql           Esquema inicial (users, programs, lessons, user_progress, login_attempts).
│   ├── seed.sql             Datos de ejemplo (programa Arranque con Día 1).
│   └── migrations/
│       ├── 2026-05-21-add-password-resets.sql
│       ├── 2026-05-21-add-lesson-video-and-download.sql
│       └── 2026-05-25-add-program-presentation.sql
├── public/                  Document root (lo único expuesto al web).
│   ├── index.php            Front controller + router HTTP + redirect HTTP->HTTPS en producción.
│   ├── .htaccess            Rewrite + headers de seguridad (Apache; ignorado por php -S de Railway).
│   └── assets/
│       ├── css/styles.css   Estilos completos (mobile-first).
│       ├── js/copy.js       Único JS del proyecto.
│       └── uploads/         Carpeta de imágenes subidas. En Railway está montado el Volume sobre esta ruta.
│           ├── .gitkeep     Mantiene la carpeta en git.
│           └── .htaccess    Niega ejecución de PHP en uploads (defensa anti-upload de scripts).
├── src/                     Código PHP (PSR-4 App\).
│   ├── Auth.php             Sesiones, login, logout, hashing Argon2id, rate-limit.
│   ├── BatchImporter.php    Parser de XLSX para batch import de lecciones.
│   ├── Csrf.php             Generación y validación de token CSRF.
│   ├── Embed.php            Parser de URLs de YouTube/Vimeo y validador de URLs de Drive.
│   ├── Mailer.php           Envío de email vía Mailgun (cURL directo).
│   ├── RemoteImage.php      Descarga de imágenes desde URL (whitelist Drive, anti-SSRF).
│   ├── Router.php           Router HTTP minimal con placeholders {var}.
│   ├── Security.php         Genera nonce CSP, emite headers de seguridad, detecta HTTPS, extrae IP cliente.
│   ├── Upload.php           Subida segura de imágenes (MIME real con finfo, whitelist, UUID rename).
│   ├── View.php             Render de templates con layout, redirect, JSON response.
│   ├── helpers.php          Funciones globales e() y e_nl2br() (HTML escape).
│   ├── Database/
│   │   └── Connection.php   PDO singleton hacia Neon con EMULATE_PREPARES=true.
│   ├── Support/
│   │   └── Env.php          Wrapper de variables de entorno (require, bool, int, get).
│   └── Controllers/
│       ├── AdminBatchController.php       Upload de XLSX, plantilla descargable, procesar batch.
│       ├── AdminController.php            Dashboard de admin con stats y atajos.
│       ├── AdminLessonsController.php     CRUD de lecciones (manual).
│       ├── AdminProgramsController.php    CRUD de programas + cover image.
│       ├── AdminUsersController.php       CRUD de usuarios + activar/desactivar.
│       ├── AuthController.php             Login, logout.
│       ├── DashboardController.php        Dashboard del miembro.
│       ├── HomeController.php             Home (redirige a /login o /dashboard).
│       ├── LessonController.php           Vista de la lección para miembros.
│       ├── PasswordResetController.php    Recuperación por email vía /ctoadmin.
│       ├── ProgramController.php          Vista de un programa para miembros.
│       └── ProgressController.php         Toggle de items del checklist (persistente).
└── templates/               Vistas PHP (sin lógica de negocio).
    ├── layout.php           Layout principal (header, footer, includes CSS/JS).
    ├── dashboard.php        Dashboard del miembro.
    ├── auth/
    │   ├── login.php
    │   ├── forgot.php           Pantalla de "Recuperar acceso" en /ctoadmin.
    │   ├── forgot-sent.php      Confirmación de "email enviado".
    │   ├── reset.php            Formulario de nueva contraseña.
    │   ├── reset-invalid.php    Token inválido/expirado.
    │   └── reset-done.php       Contraseña actualizada.
    ├── admin/
    │   ├── index.php            Dashboard admin (stats + atajos).
    │   ├── users/{index,form}.php
    │   ├── programs/{index,form,delete}.php
    │   ├── lessons/{index,form,delete}.php
    │   └── batch/{upload,result}.php
    ├── programs/show.php
    ├── lessons/show.php
    ├── email/
    │   ├── reset.html.php       Plantilla HTML del email de reset (CSS inline).
    │   └── reset.txt.php        Plantilla texto plano del email.
    └── errors/
        ├── 403.php, 404.php, 500.php
```

---

## 4. Servicios externos

### 4.1 Neon (PostgreSQL)

- Cuenta: rubmar007 (vinculada vía GitHub).
- Proyecto: `wellness-circle-academy`.
- Versión: PostgreSQL 17.10.
- Región: `aws-us-east-1` (N. Virginia).
- Base de datos: `neondb`.
- Rol: `neondb_owner`.
- URL pooled (la que se usa en producción y local):
  `postgresql://neondb_owner:PASSWORD@ep-late-cake-aqeydnsa-pooler.c-8.us-east-1.aws.neon.tech/neondb?sslmode=require&channel_binding=require`
- Password real vive en `.env` local y en variable de entorno de Railway. Nunca se commitea.

#### Resetear password de Neon
1. Login en https://console.neon.tech.
2. Project → Connect → modal "Connect to your database".
3. Click en "Reset password" arriba a la derecha del select de Role.
4. Confirma. La nueva password aparece en la connection string del modal.
5. Actualizar `DATABASE_URL` tanto en `.env` local como en las variables de entorno de Railway. Sin esto la app deja de conectar.

### 4.2 Mailgun (envío de email)

- Cuenta: del usuario (Rub).
- Dominio configurado: `mail.5t4d10.com` (subdominio).
- Región: US.
- Sender autorizado: `info@5t4d10.com`. Aunque el sender NO está en el subdominio `mail.5t4d10.com`, sino en el dominio raíz, funciona porque los DNS del dominio raíz (SPF, DKIM, DMARC) están configurados correctamente.
- API key vive en `MAILGUN_API_KEY` (en `.env` local y env vars de Railway).
- Plan: free durante los primeros 3 meses (5,000 emails), después es de pago. Verificar plan actual antes de campañas grandes.

#### Cómo rotar la API key
1. Login en https://app.mailgun.com.
2. Send → Sending → Domain Settings → API Keys.
3. Generate a new key.
4. Actualizar `MAILGUN_API_KEY` en `.env` local y en Railway. Re-deployar.

### 4.3 Railway (hosting)

- Cuenta vinculada con GitHub.
- Proyecto: `dependable-spirit` (Railway le asignó este nombre random; el servicio dentro se llama `wellness-circle-academy`).
- Plan: Hobby.
- Builder: **Railpack** (no Nixpacks). Detecta PHP por `composer.json`.
- Region: US West (California).
- Public domain: `wellnessca.martavilla.com.mx` (custom domain). Certificado Let's Encrypt automático.
- Internal domain: `wellness-circle-academy.railway.internal` (no expuesto).
- Volume: `wellness-circle-academy-volume`, 5 GB, montado en `/app/public/assets/uploads`.

---

## 5. Variables de entorno

Plantilla en `.env.example`. Significado de cada una:

| Variable | Valores | Para qué sirve |
|---|---|---|
| `APP_ENV` | `local` (dev) o `production` (Railway) | En producción activa redirect HTTP→HTTPS y oculta detalles de errores. |
| `APP_DEBUG` | `true` (dev) o `false` (producción) | Activa `display_errors` y mensajes detallados. **NUNCA `true` en producción**. |
| `APP_URL` | URL completa con scheme | Solo fallback. El sistema construye URLs absolutas desde `HTTP_HOST` del request, así que esta variable casi no se usa. Se mantiene por compatibilidad. |
| `APP_NAME` | `"Wellness Circle Academy"` | Título visible en header, emails y `<title>` del browser. |
| `APP_KEY` | base64 de 32 bytes random | Reservado para futuros usos de firma. Generar con `php -r "echo base64_encode(random_bytes(32));"`. |
| `DATABASE_URL` | URL postgres completa | Conexión a Neon. Formato: `postgresql://user:pass@host:port/db?sslmode=require&channel_binding=require`. |
| `UPLOAD_MAX_BYTES` | entero, default `5242880` (5 MB) | Tamaño máximo de imagen subida (admin y batch). |
| `UPLOAD_ALLOWED_MIME` | CSV de MIME | `image/jpeg,image/png,image/webp`. Solo documentación, los MIME reales se validan en código. |
| `MAILGUN_API_KEY` | private API key | Para enviar emails. |
| `MAILGUN_DOMAIN` | dominio configurado en Mailgun | `mail.5t4d10.com`. |
| `MAILGUN_REGION` | `us` o `eu` | Endpoint que se usa: `api.mailgun.net` o `api.eu.mailgun.net`. |
| `MAIL_FROM_ADDRESS` | email completo | Remitente del email. Debe estar autorizado en Mailgun. |
| `MAIL_FROM_NAME` | nombre | Nombre visible en el "From" del email. |
| `PORT` | NO definir manualmente | Railway la inyecta automáticamente. En local PHP usa el puerto del comando `php -S`. |

---

## 6. Esquema de la base de datos

### 6.1 Tabla `users`
| Campo | Tipo | Notas |
|---|---|---|
| `id` | BIGSERIAL PK | |
| `name` | VARCHAR(120) NOT NULL | |
| `email` | VARCHAR(190) NOT NULL UNIQUE | Siempre se guarda en minúsculas. |
| `password_hash` | VARCHAR(255) NOT NULL | Argon2id. Irreversible. |
| `role` | VARCHAR(20) | `admin` o `member`. |
| `is_active` | BOOLEAN | TRUE por default. FALSE desactiva la cuenta sin borrar (auditoría). |
| `created_at`, `updated_at` | TIMESTAMPTZ | Auto-updated por trigger. |

### 6.2 Tabla `programs`
| Campo | Tipo | Notas |
|---|---|---|
| `id` | BIGSERIAL PK | |
| `slug` | VARCHAR(80) UNIQUE | Regex `^[a-z0-9-]+$`. Aparece en URLs públicas. |
| `title` | VARCHAR(160) | |
| `presentation` | VARCHAR(200) NULL | Texto corto (máx 200) que aparece SOLO en la tarjeta del dashboard como tagline bajo la portada. Independiente de `description`. |
| `description` | TEXT | Descripción larga que aparece en la vista interna del programa (`/programas/{slug}`). |
| `cover_image` | VARCHAR(500) | Path interno `/assets/uploads/UUID.ext`. |
| `display_order` | INT | Menor = aparece primero en el dashboard. |
| `is_published` | BOOLEAN | Si está en FALSE no es visible a los miembros. |
| `created_at`, `updated_at` | TIMESTAMPTZ | |

### 6.3 Tabla `lessons`
| Campo | Tipo | Notas |
|---|---|---|
| `id` | BIGSERIAL PK | |
| `program_id` | BIGINT FK → programs(id) ON DELETE CASCADE | |
| `day_number` | INTEGER > 0 | UNIQUE por programa. |
| `title` | VARCHAR(200) | |
| `objective` | TEXT | "Objetivo del día". |
| `post_text` | TEXT | Texto principal a copiar. |
| `story_text` | TEXT | Story sugerida. |
| `conversation_text` | TEXT | Conversación ejemplo. |
| `action_text` | TEXT | Acciones del día (texto libre, normalmente lista con `- `). |
| `tip_text` | TEXT | Tip del día. |
| `image_url` | VARCHAR(500) | `/assets/uploads/UUID.ext`. |
| `video_url` | VARCHAR(500) | URL pública de YouTube o Vimeo. Si está vacío, no se muestra la sección de video. |
| `download_url` | VARCHAR(500) | URL pública de Google Drive a un archivo descargable. Si está vacío, no se muestra la sección. |
| `checklist_items` | JSONB | Array de strings (máx 20 ítems, 200 chars c/u). |
| `is_published` | BOOLEAN | |
| `created_at`, `updated_at` | TIMESTAMPTZ | |

### 6.4 Tabla `user_progress`
| Campo | Tipo | Notas |
|---|---|---|
| `user_id`, `lesson_id`, `item_index` | PK compuesta | item_index es base 0 dentro de `lessons.checklist_items`. |
| `completed_at` | TIMESTAMPTZ | |

### 6.5 Tabla `login_attempts`
| Campo | Tipo | Notas |
|---|---|---|
| `id` | BIGSERIAL PK | |
| `email`, `ip_address` | VARCHAR | Para rate limit por email e IP. |
| `succeeded` | BOOLEAN | |
| `attempted_at` | TIMESTAMPTZ | |

Rate limit: máx 5 intentos fallidos por email y 20 por IP en una ventana de 15 minutos. Tras login exitoso o reset, los fallos se limpian.

### 6.6 Tabla `password_resets`
| Campo | Tipo | Notas |
|---|---|---|
| `id` | BIGSERIAL PK | |
| `user_id` | BIGINT FK → users(id) ON DELETE CASCADE | |
| `token_hash` | CHAR(64) UNIQUE | SHA-256 hex. El token plano NO se guarda. |
| `expires_at` | TIMESTAMPTZ | Default 15 min después de `requested_at`. |
| `used_at` | TIMESTAMPTZ NULLABLE | Marca single-use. |
| `ip_address` | VARCHAR(45) | |
| `requested_at` | TIMESTAMPTZ DEFAULT NOW | |

Rate limit: máx 3 solicitudes por hora por usuario.

### 6.7 Aplicar el esquema a una base nueva

```bash
psql "$DATABASE_URL" -f database/schema.sql
psql "$DATABASE_URL" -f database/seed.sql                              # opcional, programa Arranque + Día 1
psql "$DATABASE_URL" -f database/migrations/2026-05-21-add-password-resets.sql
psql "$DATABASE_URL" -f database/migrations/2026-05-21-add-lesson-video-and-download.sql
psql "$DATABASE_URL" -f database/migrations/2026-05-25-add-program-presentation.sql
```

Si no se tiene `psql` instalado, se puede aplicar desde PHP:

```bash
php -r 'require __DIR__."/vendor/autoload.php"; \App\Support\Env::load(__DIR__); \App\Database\Connection::get()->exec(file_get_contents("database/schema.sql"));'
```

---

## 7. Rutas y controllers

Todas las rutas se definen en `public/index.php`. Resumen:

### Públicas
| Método | URL | Controller::action | Notas |
|---|---|---|---|
| GET | `/` | HomeController::index | Redirige a `/login` o `/dashboard`. |
| GET | `/login` | AuthController::showLogin | |
| POST | `/login` | AuthController::login | CSRF + rate limit. |
| POST | `/logout` | AuthController::logout | CSRF. |

### Recuperación de contraseña (URL "secreta")
| GET | `/ctoadmin` | PasswordResetController::show | Sin link público en la app. |
| POST | `/ctoadmin` | PasswordResetController::request | Genera token y envía email. |
| GET | `/restablecer/{token}` | PasswordResetController::showReset | |
| POST | `/restablecer/{token}` | PasswordResetController::reset | |

### Área de miembro (requiere login)
| GET | `/dashboard` | DashboardController::index | Lista de programas publicados. |
| GET | `/programas/{slug}` | ProgramController::show | Lista de días publicados. |
| GET | `/programas/{slug}/dia/{day}` | LessonController::show | Vista completa de la lección. |
| POST | `/progreso` | ProgressController::toggle | Marcar/desmarcar ítem del checklist. |

### Admin (requiere login + rol admin)
| GET | `/admin` | AdminController::index | |
| GET | `/admin/usuarios` | AdminUsersController::index | |
| GET/POST | `/admin/usuarios/nuevo`, `/admin/usuarios` | AdminUsersController::create/store | |
| GET/POST | `/admin/usuarios/{id}/editar`, `/admin/usuarios/{id}` | AdminUsersController::edit/update | |
| POST | `/admin/usuarios/{id}/toggle` | AdminUsersController::toggleActive | |
| GET | `/admin/programas` | AdminProgramsController::index | |
| GET/POST | `/admin/programas/nuevo`, `/admin/programas` | AdminProgramsController::create/store | |
| GET/POST | `/admin/programas/{id}/editar`, `/admin/programas/{id}` | AdminProgramsController::edit/update | |
| GET/POST | `/admin/programas/{id}/eliminar` | AdminProgramsController::confirmDestroy/destroy | |
| GET | `/admin/programas/{programId}/lecciones` | AdminLessonsController::index | |
| GET/POST | `/admin/programas/{programId}/lecciones/nueva`, `.../lecciones` | AdminLessonsController::create/store | |
| GET/POST | `/admin/lecciones/{id}/editar`, `/admin/lecciones/{id}` | AdminLessonsController::edit/update | |
| GET/POST | `/admin/lecciones/{id}/eliminar` | AdminLessonsController::confirmDestroy/destroy | |
| GET | `/admin/programas/{id}/batch` | AdminBatchController::show | |
| POST | `/admin/programas/{id}/batch` | AdminBatchController::process | |
| GET | `/admin/programas/{id}/batch/plantilla` | AdminBatchController::downloadTemplate | Genera y descarga XLSX. |

---

## 8. Roles y permisos

- **member**: puede ver dashboard, programas y lecciones publicadas. Puede marcar progreso. NO puede acceder a `/admin`.
- **admin**: todo lo del member + acceso completo a `/admin/*`. Puede crear usuarios, editar programas y lecciones, hacer batch import, eliminar contenido.

Protecciones específicas:
- El **último admin activo** no puede degradarse a `member` ni desactivarse a sí mismo (evita bloqueo del sistema).
- Cuentas inactivas (`is_active = FALSE`) no pueden hacer login, y si una sesión existente se vuelve inactiva, la sesión se invalida en el siguiente request.

**No hay registro público**. Cuentas nuevas solo se crean desde:
- Panel admin (`/admin/usuarios/nuevo`).
- Script CLI `bin/create-user.php`.

---

## 9. Operación día-a-día

### 9.1 Crear un usuario

#### Desde el panel admin
1. Login como admin.
2. Header → Admin → tarjeta "Usuarios" → botón "Nuevo usuario".
3. Llenar nombre, email, rol (admin o member), contraseña (≥ 10 caracteres).
4. La contraseña se guarda con Argon2id y no se puede recuperar después. Apuntarla en gestor de contraseñas o avisar al usuario para que la cambie en su primera entrada.

#### Desde CLI (necesario solo si no hay ningún admin todavía)
```bash
php bin/create-user.php
```
Pide nombre, email, rol y contraseña interactivamente. La contraseña no se muestra al teclear ni queda en el historial del shell.

### 9.2 Crear un programa
1. Admin → "Programas y lecciones" → "Nuevo programa".
2. Llenar título, **presentación** (texto corto ≤ 200 chars que solo aparece como tagline en la tarjeta del dashboard), slug (solo `a-z 0-9 -`, ej. `arranque`), descripción (texto largo que se ve dentro del programa), imagen de portada, orden y checkbox de publicado.
3. El slug aparece en URLs públicas (`/programas/{slug}`); cambiarlo después rompe links que la gente tenga guardados.
4. `presentation` y `description` son independientes: `presentation` rige las tarjetas del dashboard; `description` rige la vista interna del programa.

### 9.3 Crear una lección manualmente
1. Admin → Programas → click "Lecciones" del programa.
2. "Nueva lección".
3. Día (entero, único por programa), título, objetivo, publicación, story, conversación, acción, tip, imagen, video URL, download URL, checklist (un ítem por línea, máx 20), checkbox publicado.
4. Guardar.

### 9.4 Cargar lecciones en batch (XLSX)

Lo más eficiente cuando hay muchos días de un programa.

1. Admin → Programas → "Lecciones" del programa → botón "Cargar batch (XLSX)".
2. Click "Descargar plantilla XLSX". Trae 13 columnas con encabezados y dos filas de ejemplo (Día 1 con texto del seed, Día 2 con placeholders).
3. Abrir el XLSX en Google Sheets (Archivo → Importar) o Excel.
4. Editar las filas. Reglas:
   - Una fila por día. Día único por programa.
   - Filas completamente vacías se ignoran.
   - Checklist: separar ítems con `|` o saltos de línea dentro de la celda.
   - Publicado: `1`, `sí`, `true` para publicar; `0` o vacío para borrador.
   - URL imagen: link de Google Drive con permisos "cualquier persona con el enlace puede ver". El servidor descarga la imagen al Volume y la sirve desde el dominio.
   - URL video: link de YouTube o Vimeo (cualquier formato común). El sistema lo convierte a embed automáticamente.
   - URL descarga: link de Google Drive. Se sirve como botón "Abrir descarga en Drive" (target=_blank).
   - Si una celda de URL viene vacía, se conserva el valor actual de BD para esa lección. Para limpiar el campo, editar la lección desde el form manual.
5. Descargar el sheet como `.xlsx` (Sheets: Archivo → Descargar → Microsoft Excel).
6. Volver al admin batch, subir el XLSX, "Procesar archivo".
7. Resultado: pantalla con lista de lecciones creadas y actualizadas, o lista de errores con número de fila exacto si algo falló.

**Comportamiento upsert no destructivo**:
- Si la combinación (programa, día) ya existe → UPDATE.
- Si no existe → INSERT.
- **Nunca se borran lecciones** que no estén en el archivo. Esto preserva el progreso de los miembros.
- **Si una fila tiene error, rollback TOTAL del batch**: no se importa nada parcial. Se corrige el XLSX y se reintenta.

### 9.5 Subida de imágenes

Tres caminos:
1. **Manual desde admin** (form de edición de programa o lección): elige archivo local, se valida MIME real con finfo, se renombra con UUID, se guarda en `/public/assets/uploads/`.
2. **Batch XLSX con URL de Drive**: el server descarga la imagen desde Drive y la trata igual que la manual.
3. (No usado actualmente) URL externa de cualquier otro origen está deshabilitada por whitelist.

Validaciones de upload (mismas en los tres caminos):
- HTTPS only (para URLs remotas).
- Whitelist de dominios para URLs remotas: solo Google Drive (drive.google.com, docs., drive.usercontent., *.googleusercontent.com).
- Tamaño máx: `UPLOAD_MAX_BYTES` (default 5 MB).
- MIME real verificado con `finfo` sobre el archivo (no se confía en Content-Type del cliente ni del remote).
- Solo `image/jpeg`, `image/png`, `image/webp` aceptados.
- Renombrado con `bin2hex(random_bytes(16)) . '.ext'`. El nombre original se descarta.

### 9.6 Video embebido

- URLs aceptadas: YouTube (watch, embed, shorts, youtu.be, m.youtube con cualquier query) y Vimeo (vimeo.com, player.vimeo.com).
- YouTube se sirve desde `youtube-nocookie.com` (sin tracking).
- CSP permite frames solo de esos dominios.
- Si la URL no pasa la validación, se rechaza con error en el form admin / fila de error en batch.
- Si `video_url` está vacío en la lección, la sección "Video del día" no se renderiza.

### 9.7 Links de descarga
- Solo Google Drive (mismo whitelist que imágenes).
- Se renderiza como botón "Abrir descarga en Drive", `target=_blank rel="noopener noreferrer"`.
- Si vacío, la sección no aparece.

### 9.8 Recuperación de contraseña

URL secreta: `https://wellnessca.martavilla.com.mx/ctoadmin`. No hay link visible desde ningún lado de la app — debe guardarse aparte.

Flujo:
1. Visitar `/ctoadmin`, escribir email.
2. Si el email es de un admin activo, llega un email con link.
3. La respuesta es genérica para no filtrar enumeration ("Si tu email es de administrador, recibirás...").
4. Click en el link → form de nueva contraseña.
5. Definir nueva contraseña ≥ 10 caracteres.

Reglas del token:
- 32 bytes random → 64 chars hex.
- Hash SHA-256 en BD; el token plano solo vive en el email.
- Expira en 15 minutos.
- Single-use (se marca `used_at` al usarlo).
- Rate limit: máx 3 solicitudes por hora por usuario.

**Importante**: el link del email se construye desde `HTTP_HOST` del request actual, no desde `APP_URL`. Esto significa que si solicitas el reset desde `https://wellnessca.martavilla.com.mx`, el link te lleva al mismo dominio. Si en el futuro se accede al sitio desde otro dominio (multi-tenant, staging), el email se ajusta solo.

#### Reset manual de password (último recurso si /ctoadmin falla)
```bash
php -r '
require __DIR__."/vendor/autoload.php";
\App\Support\Env::load(__DIR__);
$pdo = \App\Database\Connection::get();
$hash = \App\Auth::hashPassword("NUEVA_PASSWORD_AQUI");
$pdo->prepare("UPDATE users SET password_hash = :h WHERE email = :e")
    ->execute([":h" => $hash, ":e" => "email@ejemplo.com"]);
echo "OK\n";'
```

---

## 10. Seguridad

Defensas activas en el sistema:

### 10.1 Autenticación
- Passwords con **Argon2id** (`password_hash` con `PASSWORD_ARGON2ID`).
- Re-hash automático si el algoritmo recomendado cambia.
- Comparación constant-time para CSRF (`hash_equals`).
- Dummy password verify para evitar timing attacks que diferencien email inexistente vs password incorrecto.
- Rate limit: 5 fallos/email + 20 fallos/IP en ventana de 15 min. Lockout temporal.
- Sesión regenerada (`session_regenerate_id(true)`) tras login (evita session fixation).
- Sesión invalidada si el usuario queda inactivo.

### 10.2 Sesiones
- Cookies con `HttpOnly`, `SameSite=Lax`, `Secure` en HTTPS.
- `use_strict_mode=1`, `sid_length=48`, `sid_bits_per_character=6`.
- Nombre de cookie: `wca_session`.

### 10.3 CSRF
- Token por sesión, 32 bytes hex.
- En todos los POST. Validación con `hash_equals`.
- Si falla: HTTP 419 con mensaje claro y `exit`.

### 10.4 Headers de seguridad
Aplicados en cada respuesta vía `Security::applyHeaders`:
- `Content-Security-Policy`: estricta con nonce dinámico por request. Solo permite frames de YouTube y Vimeo.
- `X-Content-Type-Options: nosniff`.
- `X-Frame-Options: DENY` (nosotros no permitimos ser embebidos).
- `Referrer-Policy: same-origin`.
- `Permissions-Policy`: camera, microphone, geolocation, interest-cohort negados.
- `Strict-Transport-Security: max-age=31536000; includeSubDomains` en HTTPS.
- `Cross-Origin-Opener-Policy: same-origin`.
- `Cross-Origin-Resource-Policy: same-origin`.
- `Cache-Control: no-store, no-cache, must-revalidate, max-age=0` (en respuestas dinámicas).

### 10.5 Base de datos
- PDO con prepared statements parametrizados en cada consulta. **Jamás concatenar SQL**.
- `EMULATE_PREPARES=true` (ver sección 14 para el motivo).
- Errores en transacciones siempre causan rollback completo. Cleanup de archivos descargados/subidos antes del fallo.

### 10.6 Subida de archivos
- MIME real con `finfo` sobre el archivo, no confiando en headers del cliente.
- Whitelist estricta: JPG, PNG, WebP.
- Tamaño máx vía `UPLOAD_MAX_BYTES`.
- Renombrado con UUID. Nombre original se descarta.
- `is_uploaded_file` antes de mover el archivo subido.
- `.htaccess` en `/public/assets/uploads/` que niega ejecución de PHP (defensa adicional si en el futuro se usa Apache; con `php -S` no aplica pero la protección real es que solo se guardan `.jpg/.png/.webp`).

### 10.7 Descargas de imágenes remotas (RemoteImage)
- Solo HTTPS (CURLOPT_PROTOCOLS y REDIR_PROTOCOLS).
- Whitelist de hosts para anti-SSRF: drive.google.com, docs.google.com, drive.usercontent.google.com, *.googleusercontent.com.
- Validación del host TANTO en URL inicial COMO en el host final tras los redirects (max 5).
- Hard cap de tamaño durante la descarga vía `CURLOPT_WRITEFUNCTION` (aborta el download cuando excede el límite).
- MIME real con `finfo` sobre el archivo descargado, no Content-Type del remoto.

### 10.8 Embeds y URLs externas
- `Embed::parseVideo` solo acepta YouTube y Vimeo en formato HTTPS.
- `Embed::sanitizeDownloadUrl` solo acepta hosts de Drive en HTTPS.
- Regex estrictas de IDs/paths.
- iframes con `referrerpolicy=strict-origin-when-cross-origin` y `allowfullscreen`.

### 10.9 Redirect HTTP→HTTPS
- Solo en `APP_ENV=production`.
- Detección con `Security::isHttps()` que considera `HTTP_X_FORWARDED_PROTO` del proxy de Railway.
- Redirect 301 (permanent) al mismo path con scheme cambiado a HTTPS.
- Host validado con regex antes de redirigir (anti header injection).

### 10.10 Output escape
- Helper global `e()` con `htmlspecialchars(ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`.
- Helper global `e_nl2br()` para textos con saltos de línea.
- TODAS las plantillas usan estos helpers para output de datos externos.

### 10.11 Errores
- En producción (`APP_DEBUG=false`): `display_errors=0`, errores logueados con prefijo `[wca]` en stderr.
- Exception handler global muestra página 500 genérica al usuario.
- Logs van al stdout/stderr del proceso PHP → Railway los captura en su panel de Logs.

---

## 11. Deploy y CI

### 11.1 Disparador
Cualquier push a `main` en GitHub dispara automáticamente un deploy en Railway. No hay CI/tests todavía (TODO).

### 11.2 Build (Railpack)
Railpack detecta PHP por `composer.json` y arma:
- Instala PHP exactamente a la versión que pide `config.platform.php` en composer.json (8.3.13).
- Compila las extensiones listadas en `require` (`pdo_pgsql`, `mbstring`, `fileinfo`, `curl`, `zip`).
- Corre `composer install --no-dev --optimize-autoloader --no-scripts`.
- Lee `Procfile` para el start command.

### 11.3 Start command
Definido en `Procfile`:
```
web: php -S 0.0.0.0:$PORT -t public public/index.php
```
- `php -S`: servidor web built-in de PHP. No es nginx ni Apache. Para volumen actual (decenas-cientos de miembros) es suficiente. Si crece a miles, migrar a php-fpm + nginx.
- `$PORT`: lo inyecta Railway.
- `-t public`: document root.
- `public/index.php` como router: cuando una URL no es un archivo físico, ejecuta el front controller. Cuando SÍ es archivo físico dentro de `public/`, el router devuelve `false` y `php -S` sirve el estático directo (CSS, JS, imágenes).

### 11.4 Volume
- Tipo: bind-mount.
- Mount path: `/app/public/assets/uploads`.
- Tamaño: 5 GB (límite). Live-resize disponible desde el dashboard.
- Se crea desde el canvas del proyecto (Add → Volume → asociar al servicio).
- **Cuando el volume se monta, oculta cualquier contenido previo en esa ruta del filesystem efímero**. Por eso las imágenes subidas ANTES de crear el volume aparecen como rotas tras montarlo. Solución: re-subir o restaurar desde backup.

### 11.5 Custom domain
- `wellnessca.martavilla.com.mx`.
- DNS apuntando a Railway (CNAME o A según UI).
- Certificado emitido automáticamente por Let's Encrypt cuando Railway valida que el DNS responde correctamente. Tarda 1-10 min desde la propagación del DNS.
- HSTS de 1 año activo.

### 11.6 Forzar redeploy sin cambios de código
```bash
git commit --allow-empty -m "Trigger redeploy" && git push origin main
```

### 11.7 Variables de entorno en producción
- Idénticas a `.env` local salvo:
  - `APP_ENV=production`
  - `APP_DEBUG=false`
  - `APP_URL=https://wellnessca.martavilla.com.mx` (en realidad opcional, ver sección 5)
- Se editan en Railway → servicio → tab Variables → Raw Editor.
- Cambiar una variable dispara redeploy automático.

---

## 12. Troubleshooting

### "No puedo entrar, mi password no funciona"
- Verificar Caps Lock.
- Verificar que el rate limit no esté bloqueando: si hubo ≥ 5 fallos en 15 min, la cuenta queda bloqueada.
- Para limpiar el rate limit manualmente:
  ```bash
  php -r 'require __DIR__."/vendor/autoload.php"; \App\Support\Env::load(__DIR__);
  $pdo = \App\Database\Connection::get();
  $pdo->prepare("DELETE FROM login_attempts WHERE email = :e AND succeeded = FALSE")
      ->execute([":e" => "email@ejemplo.com"]);'
  ```
- Si la password se olvidó, usar `/ctoadmin` para reset por email.
- Como último recurso, reset manual con el snippet de sección 9.8.

### "El email de recuperación no llega"
- Revisar Spam, Promociones, Updates de Gmail.
- Verificar que el dominio Mailgun esté configurado correctamente (DNS).
- Verificar logs de Railway para errores tipo `[wca] Mailer: Mailgun HTTP 4xx`.
- Verificar que la cuenta de Mailgun no haya alcanzado su límite mensual.
- Comprobar variables de entorno: `MAILGUN_API_KEY`, `MAILGUN_DOMAIN`, `MAIL_FROM_ADDRESS`.

### "El link del email lleva a placeholder.up.railway.app"
- Es un email VIEJO generado cuando `APP_URL` aún era placeholder. Los emails nuevos (commit `55d8027` en adelante) usan `HTTP_HOST` y no tienen este problema.
- Fix inmediato del email viejo: reemplazar `placeholder.up.railway.app` por `wellnessca.martavilla.com.mx` en la URL del navegador (el token sigue siendo válido si no expiró).
- Fix permanente: pedir un nuevo reset.

### "Las imágenes subidas se borran tras un push"
- Si esto pasa, el Volume no está montado correctamente.
- Verificar en Railway → servicio → Settings → Volumes:
  - Existe el Volume `wellness-circle-academy-volume`.
  - Mount path es EXACTAMENTE `/app/public/assets/uploads`.
  - Estado: Online y vinculado al servicio.
- Si el mount path está mal, editarlo y forzar redeploy.

### "El navegador dice Not secure"
- Verificar primero en otro navegador (Opera, Firefox, Edge). Si en otro está bien, es local de Chrome.
- En Chrome, limpiar HSTS cache: `chrome://net-internals/#hsts` → Delete domain → `wellnessca.martavilla.com.mx`.
- Limpiar caché del sitio: `chrome://settings/clearBrowserData` (cookies y caché, 24h).
- Probar en incógnito; si funciona ahí, es una extensión.
- En el servidor, verificar:
  ```bash
  curl -sI https://wellnessca.martavilla.com.mx/login | grep -i strict-transport
  openssl s_client -servername wellnessca.martavilla.com.mx -connect wellnessca.martavilla.com.mx:443 < /dev/null 2>&1 | grep "Verify return code"
  ```
  Debe responder con HSTS y `Verify return code: 0 (ok)`.

### "Build de Railway falla con 'ext-zip missing'"
- Causa: en algún momento se quitó `ext-zip` de `composer.json`. Es requerido por openspout.
- Fix: añadir `"ext-zip": "*"` a `require` en composer.json y `composer update --lock`.

### "Build de Railway agarra PHP 8.5 preview"
- Causa: `composer.json` tiene `"php": ">=8.1"` (sin upper bound) y Railpack toma la versión más nueva disponible.
- Fix: cambiar a `"php": "^8.3"` y añadir `config.platform.php = "8.3.13"`.

### "SQLSTATE[25P02] In failed sql transaction"
- Causa: el pooler de Neon no maneja bien múltiples prepared statements server-side en una transacción que toca tablas distintas.
- Fix: `Connection.php` ya usa `EMULATE_PREPARES=true`. No revertir esto. Si alguien lo hace por "best practice", probar el flujo de reset de password (que toca users, password_resets, login_attempts en una transacción) y verás el error.

### "Call to undefined function e()"
- Causa: las funciones globales `e()` y `e_nl2br()` están en `src/helpers.php`. Si composer no las carga (autoload.files), no están disponibles.
- Fix: confirmar que `composer.json` tiene:
  ```json
  "autoload": { "files": ["src/helpers.php"], "psr-4": {...} }
  ```
  Y correr `composer dump-autoload`.

### "El checklist hace scroll al inicio de la página tras tocar un ítem"
- Causa: el POST `/progreso` hacía redirect al path de la lección sin anchor.
- Fix: ya implementado. Cada `<li>` del checklist tiene `id="chk-N"` y el `back` del POST incluye `#chk-N`. Si vuelve a pasar, revisar `templates/lessons/show.php` y `ProgressController::sanitizeBack`.

### "El admin puede subir un archivo .php con extensión .jpg"
- No es vulnerabilidad real: el archivo se renombra con UUID + extensión .jpg, y `php -S` no ejecuta `.jpg`. La defensa adicional del `.htaccess` solo aplica si en el futuro se usa Apache.
- Para reforzar, verificar que `Upload::ALLOWED_IMAGE_MIME` mantenga whitelist estricta.

---

## 13. Decisiones de diseño

### 13.1 Sin frameworks de cliente (React, Vue, Angular)
Razón: regla 6 de CLAUDE.md. La superficie de ataque es menor con HTML+CSS puro. Todo el JS del proyecto cabe en 25 líneas (clipboard). HTMX está disponible para futuras necesidades de reactividad.

### 13.2 Sin Apache ni nginx en producción
Razón: simplicidad. `php -S` cubre el tráfico actual. La opción de migrar a php-fpm + nginx queda abierta cuando crezca el tráfico (señal: latencias elevadas o errores 502).

### 13.3 `EMULATE_PREPARES=true` en PDO
Razón: el pooler de Neon no maneja bien múltiples prepared statements server-side dentro de una transacción que toca tablas distintas. El escape client-side de PDO_pgsql es probado y seguro contra SQL injection. **No revertir** sin antes probar el flujo completo de reset de password.

### 13.4 URL "secreta" `/ctoadmin` sin links públicos
Razón: petición explícita del owner. Es "security through obscurity" (capa adicional contra bots y escaneo automático), no es la seguridad principal. La seguridad real está en el token + expiración + single-use + rate limit + whitelist de admin.

### 13.5 Argon2id en lugar de bcrypt
Razón: Argon2id es el estándar recomendado por OWASP desde 2021. Resiste mejor ataques con GPU/ASIC que bcrypt. PHP lo soporta nativo desde 7.2. Si el sistema corre en PHP que no lo tiene, hay fallback automático a `PASSWORD_DEFAULT`.

### 13.6 Mailgun en lugar de PHP `mail()`
Razón: `mail()` requiere MTA local (sendmail/postfix) que no existe en Railway. Mailgun ofrece API REST, deliverability gestionada (SPF/DKIM/DMARC en el dominio), y el free tier alcanza para el MVP.

### 13.7 Whitelist de Drive en lugar de cualquier URL para imágenes/descargas
Razón: anti-SSRF. Sin whitelist, un admin malicioso podría meter `http://internal.network/admin` como URL de imagen y forzar al servidor a hacer requests internos. Restringir a Drive elimina ese vector. Si en el futuro se necesitan otros orígenes (Dropbox, S3 propio), añadir al whitelist explícitamente.

### 13.8 Sin storage local de uploads en producción
Decisión inicial fallida: confiar en filesystem del container de Railway. Resultado: cada redeploy borraba todas las imágenes. Solución implementada: Railway Volume montado en `/app/public/assets/uploads`. Para futuro (si crece): migrar a Cloudinary o R2 con CDN.

### 13.9 Upsert no destructivo en batch import
Razón: si se borran lecciones que no están en el XLSX, se pierde el progreso de los miembros (FK con CASCADE). Mejor conservativo: actualizar lo que existe, crear lo nuevo, jamás borrar.

### 13.10 Rollback total si una fila del batch falla
Razón: importar parcialmente lleva a estados ambiguos donde es difícil saber qué se procesó y qué no. Mejor todo o nada, y el admin corrige el XLSX y reintenta.

### 13.11 No invalidar sesiones existentes tras password reset
Trade-off conocido: si un atacante tiene sesión activa y el admin resetea la password, el atacante mantiene su sesión. Para el MVP es aceptable; el admin puede cerrar sesión manualmente desde cada dispositivo. Fix futuro: agregar `password_version` en users y validar en `Auth::user()`.

---

## 14. Errores y aprendizajes durante el desarrollo

Esta sección documenta los problemas reales que ocurrieron, su síntoma, su causa raíz y la solución. Conservar para que no se repitan.

### 14.1 `Call to undefined function e()` al renderizar templates
- Síntoma: fatal error en la home tras el primer deploy local.
- Causa: las funciones `e()` y `e_nl2br()` estaban declaradas al final de `src/View.php` que tiene `namespace App;`. PHP las registró como `App\e` y `App\e_nl2br`, no como funciones globales. Las plantillas las llaman sin namespace.
- Fix: extraídas a `src/helpers.php` (sin namespace) y cargadas vía Composer `autoload.files`. Commit `b11289c`.

### 14.2 SQLSTATE[25P02] en el flujo de reset de password
- Síntoma: "current transaction is aborted, commands ignored until end of transaction block". La primera query del UPDATE reportaba `rowCount=1` pero la siguiente fallaba.
- Causa: el pooler de Neon no maneja bien múltiples prepared statements server-side en una transacción cuando tocan tablas distintas. PHP no propagaba el error real de la primera query.
- Fix: `EMULATE_PREPARES=true` en `Connection.php`. Commit `171a145`.

### 14.3 Anchor scroll del checklist mandaba a `/dashboard`
- Síntoma: tocar un ítem del checklist te llevaba al dashboard en lugar de quedarte en la lección.
- Causa: la regex de `sanitizeBack` en `ProgressController` usaba `#` como delimitador Y también `#` dentro de la char class. PHP cerraba el regex prematuro y `preg_match` devolvía `false`, lo que activaba el fallback a `/dashboard`.
- Fix: cambiar el delimitador a `~`. Commit `7c9395c`.

### 14.4 Email de reset apuntaba a `placeholder.up.railway.app`
- Síntoma: el link del email llevaba al placeholder en lugar del dominio real.
- Causa: la variable `APP_URL` estaba como placeholder cuando se generaron los primeros emails. Aunque después se actualizó, los emails ya estaban cocinados con la URL vieja.
- Fix: el link se construye desde `HTTP_HOST` del request actual con scheme detectado, sin depender de `APP_URL`. Validación regex de host anti header injection. Commit `55d8027`.

### 14.5 Build de Railway falla por `ext-zip` faltante
- Síntoma: `Your lock file does not contain a compatible set of packages... openspout v5.3.0 requires ext-zip * -> it is missing from your system`.
- Causa: `composer.json` no declaraba `ext-zip` como requirement. openspout lo necesita para procesar XLSX (que son ZIP internamente). Railpack compila solo las extensiones declaradas en `require`.
- Fix: añadir `"ext-zip": "*"` a `require`. Commit `4a7a23c`.

### 14.6 Railpack agarró PHP 8.5.6 preview en lugar de 8.3 estable
- Síntoma: build descargaba una versión inestable de PHP.
- Causa: `composer.json` tenía `"php": ">=8.1"` sin upper bound. Railpack tomó la versión más nueva disponible en su catálogo, que era una preview de 8.5.
- Fix: cambiar a `"php": "^8.3"` y añadir `config.platform.php = "8.3.13"`. Commit `4a7a23c`.

### 14.7 Railway usa Railpack, no Nixpacks
- Asumí inicialmente que Railway usaba Nixpacks y creé `nixpacks.toml`. Railway ignoró la config y el build falló.
- Fix: añadir `Procfile` con el start command. Railpack lo respeta. `nixpacks.toml` queda como fallback inofensivo. Commit `4a7a23c`.

### 14.8 Imágenes desaparecieron tras montar el Volume
- Síntoma: la imagen subida previamente quedó como link roto (404).
- Causa esperada (no es bug): el Volume se monta sobre la ruta `/app/public/assets/uploads` y oculta cualquier contenido previo que estaba en esa ruta del filesystem efímero. El Volume nace vacío.
- Fix: re-subir las imágenes. A partir de ese momento todo lo nuevo persiste.

### 14.9 Chrome muestra "Not secure" aunque el cert es válido
- Síntoma: en Chrome aparece "Not secure" pero "Certificate is valid". En Opera/Firefox sale candado normal.
- Causa: extensión del Chrome local o HSTS cache corrupto que recuerda la conexión anterior por HTTP.
- Fix: limpiar HSTS cache (`chrome://net-internals/#hsts`), limpiar caché del sitio, o desactivar extensiones. No es problema del servidor.

---

## 15. Pendientes y siguientes fases

### Corto plazo
- Cargar contenido real de los demás programas del documento de alcance (X39, Cellergize, Glutathione, Silent Night, Liderazgo, Redes Sociales, Testimonios, Herramientas). Hacerlo vía batch XLSX para eficiencia.
- Invitar a admins adicionales si los hay (vía `/admin/usuarios/nuevo` o `bin/create-user.php`).

### Mediano plazo
- Tests automatizados (PHPUnit), CI en GitHub Actions.
- Pulir UX según feedback de uso real del equipo.
- Si los volúmenes crecen, migrar de `php -S` a php-fpm + nginx (mejor concurrencia).

### Largo plazo (fases 2-4 del documento de alcance)
- Videos internos (más allá de YouTube/Vimeo embed).
- Biblioteca de testimonios texto+video.
- Calendario de Zooms y eventos.
- IA: sugerir publicaciones, generar captions, responder prospectos.
- Gamificación: puntos, ranking, medallas, niveles, retos.

### Operativos
- Backup periódico de la BD de Neon (Neon ofrece point-in-time recovery).
- Backup del Volume (Railway tiene snapshots).
- Monitoring: ahora mismo se confía en los logs de Railway. Si crece, integrar Sentry o equivalente para errores.

---

## 16. Comandos útiles

```bash
# Arrancar local
composer install
cp .env.example .env  # rellenar con valores reales
php -S 0.0.0.0:8080 -t public public/index.php

# Lint de toda la base de código
find . -type f -name '*.php' ! -path './vendor/*' -print0 \
    | xargs -0 -I{} php -l {} | grep -v 'No syntax errors detected'

# Aplicar migración
psql "$DATABASE_URL" -f database/migrations/ARCHIVO.sql
# o sin psql:
php -r 'require __DIR__."/vendor/autoload.php";
\App\Support\Env::load(__DIR__);
\App\Database\Connection::get()->exec(file_get_contents("database/migrations/ARCHIVO.sql"));'

# Crear usuario por CLI
php bin/create-user.php

# Generar APP_KEY
php -r "echo base64_encode(random_bytes(32)).PHP_EOL;"

# Forzar redeploy en Railway sin cambios de código
git commit --allow-empty -m "Redeploy" && git push origin main

# Probar envío de email
php -r '
require __DIR__."/vendor/autoload.php";
\App\Support\Env::load(__DIR__);
\App\Mailer::send("destinatario@ejemplo.com", "Test", "Texto", "<p>HTML</p>");
echo "OK\n";'

# Verificar SSL de producción
openssl s_client -servername wellnessca.martavilla.com.mx \
    -connect wellnessca.martavilla.com.mx:443 < /dev/null 2>&1 \
    | grep -E "subject=|issuer=|Verify return code"
```

---

## 17. Referencias rápidas

| Concepto | Valor |
|---|---|
| URL pública | https://wellnessca.martavilla.com.mx |
| URL secreta de reset | https://wellnessca.martavilla.com.mx/ctoadmin |
| Repo | git@github-personal:rubmar007/wellness-circle-academy.git |
| Cuenta admin inicial | rub.martinez@gmail.com |
| Neon proyecto | wellness-circle-academy (region us-east-1, Postgres 17) |
| Mailgun dominio | mail.5t4d10.com (region US, sender info@5t4d10.com) |
| Railway proyecto | dependable-spirit (servicio wellness-circle-academy, region US West, plan Hobby) |
| Volume mount | /app/public/assets/uploads (5 GB) |
| PHP version | 8.3.13 (fijado en composer.json) |
| Único JS permitido | public/assets/js/copy.js |

---

## 18. Reglas de oro

1. Toda lógica crítica vive en PHP del servidor. **Nunca** confiar en el cliente para validación o seguridad.
2. Antes de añadir cualquier JavaScript, preguntar. La regla 6 del CLAUDE.md sigue vigente.
3. PDO siempre con prepared statements parametrizados. **Nunca** concatenar SQL.
4. CSRF en todos los POST.
5. Variables de entorno para secretos. `.env` jamás se commitea.
6. Cuando algo no funciona en producción pero sí en local, revisar variables de entorno primero, builder/Procfile segundo, logs de Railway tercero.
7. Cuando se cambia el esquema de BD, escribir migración en `database/migrations/` con fecha en el nombre. Aplicarla manualmente en Neon antes del deploy del código que la usa.
8. Cuando se añade una nueva dependencia con extensión PHP, declararla en `require` de `composer.json` para que Railpack la compile.
9. El CLAUDE.md es contrato de trabajo, no decoración. Releerlo cuando llegue una persona nueva al proyecto.

---

## 19. Look and feel

Rediseño visual aplicado el 2026-05-25 sobre el commit `9fb138a`. Inspiración: artwork de invitación a Zoom del propio equipo (negro profundo con acento dorado, tipografía script para títulos elegantes, sans-serif uppercase para títulos secundarios). Aplica a TODA la app (área de miembro + área admin).

### 19.1 Paleta

Definida como CSS custom properties en `public/assets/css/styles.css` (`:root`).

| Variable | Valor | Uso |
|---|---|---|
| `--color-bg` | `#0a0a0a` | Fondo principal (body) |
| `--color-bg-elev` | `#141414` | Cards, paneles |
| `--color-bg-elev-2` | `#1c1c1c` | Hover de cards |
| `--color-bg-input` | `#0f0f0f` | Inputs, textareas, checklist |
| `--color-gold` | `#d4af37` | Acento principal, borders, títulos secundarios |
| `--color-gold-bright` | `#f0c870` | Hover, focus, highlights |
| `--color-gold-dim` | `#8a7a3d` | Scrollbar, estados secundarios |
| `--color-gold-soft` | `rgba(212,175,55,0.12)` | Fondos translúcidos dorados |
| `--color-text` | `#f5f5f5` | Texto principal |
| `--color-text-muted` | `#b8b8b8` | Texto secundario |
| `--color-text-faint` | `#888888` | Footer, placeholders |
| `--color-border` | `rgba(212,175,55,0.18)` | Borde sutil dorado translúcido |
| `--color-border-strong` | `rgba(212,175,55,0.45)` | Borde dorado más visible (hover) |
| `--color-error-fg` | `#ef9a8e` | Texto de error sobre fondo oscuro |
| `--color-success-fg` | `#8fdba9` | Texto de éxito sobre fondo oscuro |

El body usa dos gradientes radiales dorados (uno arriba-derecha, otro abajo-izquierda) sobre el negro base. `background-attachment: fixed` para que el spotlight se quede pegado al viewport al hacer scroll.

Variables antiguas (`--color-navy`, `--color-sky`, `--color-cream`, etc.) están aliased a las nuevas para no romper selectores legacy.

### 19.2 Tipografías

Dos familias, **self-hosted** desde `/public/assets/fonts/` (no se carga nada de Google Fonts — no toca CSP, no expone IPs a Google):

| Familia | Archivo | Tamaño | Uso |
|---|---|---|---|
| `Allura` (script) | `allura-regular.woff2` | 26 KB | Títulos elegantes: h1 de `.auth-card`, h1 de `.page-head`, h1 de `.error-page`. Aplica con `font-family: var(--font-script)`. |
| `Montserrat` (variable) | `montserrat-vf.woff2` | 38 KB | Todo lo demás (body, botones, navegación, títulos secundarios). Variable font con eje wght 100–900. |

Ambas con `font-display: swap` para que el texto renderee de inmediato con la fuente del sistema mientras carga el woff2. Subset latin (cubre español: acentos, ñ, signos de puntuación europeos).

License: ambas SIL Open Font License (OFL). Pueden redistribuirse libremente con el proyecto.

#### Cómo regenerar / actualizar las fuentes

Si en el futuro hace falta otro peso, otro subset, o renovar a una versión nueva:

```bash
# Pide a Google Fonts el CSS y extrae las URLs woff2
UA="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
curl -sS -A "$UA" "https://fonts.googleapis.com/css2?family=Allura&family=Montserrat:wght@400;500;600;700&display=swap"

# Descarga las URLs latin (las del comentario "/* latin */") a public/assets/fonts/
# Necesita User-Agent moderno para que sirva woff2.
```

### 19.3 Componentes clave

- **Logo (`/assets/img/logo.png`)**: pieza dorada sobre fondo negro, ya optimizada para tema oscuro. Se sirve tal cual en `.brand-mark` (header, 44×44), `.auth-logo` (login, 200×200) y `.dashboard-logo` (dashboard, 160×160). NO se le aplica `border-radius: 50%` porque cortaría las esquinas del logo cuadrado.
- **Botones primary**: gradiente dorado (`#d4af37` → `#b89028`), texto casi negro `#1a1206`, sombra dorada sutil. Hover: gradiente más claro + glow + `translateY(-1px)`. Border-radius 999px (pill).
- **Botones ghost**: transparentes con borde dorado translúcido, texto dorado. Hover: fondo `--color-gold-soft`.
- **Inputs / textareas / selects**: fondo `#0f0f0f`, borde dorado tenue, focus con `box-shadow: 0 0 0 3px rgba(212,175,55,0.18)` + borde dorado pleno. Sin outline nativo.
- **Cards (`.program-card`, `.feature-card`, etc.)**: fondo `#141414`, borde dorado al 18%. Hover suma `border-color: rgba(212,175,55,0.45)`, sombra y `translateY(-2px)`.
- **Lesson-link / admin-shortcut**: border-left dorado de 3px. Hover desplaza 2px a la derecha y aclara el borde dorado.
- **Checklist box**: 24×24 con borde dorado de 2px, fondo transparente. Estado done: fondo dorado pleno.
- **Tablas admin**: thead con fondo dorado 8%, texto dorado, font-size pequeño y uppercase. Hover de fila con tinte dorado 4%.
- **Selección de texto** (`::selection`) y **scrollbar**: ambos dorados (thumb `#8a7a3d`, track `#0a0a0a`).
- **Sticky header**: `rgba(10,10,10,0.85)` con `backdrop-filter: blur(12px)` para que el contenido se vea debajo difuminado.

### 19.4 Tipografía aplicada por elemento

| Selector | Familia | Pesos / estilos |
|---|---|---|
| `body` | Montserrat | 400 |
| `h1` (genérico) | Montserrat | 600, letter-spacing 0.02em |
| `h2` (genérico) | Montserrat | 600, uppercase, letter-spacing 0.08em |
| `h3` (genérico) | Montserrat | 600, uppercase, letter-spacing 0.06em, color dorado |
| `.auth-card h1` | **Allura** | 400, font-size 2.6rem mobile / 3rem desktop |
| `.page-head h1` | **Allura** | 400, font-size 2.4rem mobile / 3rem desktop |
| `.error-page h1` | **Allura** | 400, font-size 5rem |
| `.brand` (header) | Montserrat | 600, uppercase, letter-spacing 0.1em, 0.85rem |
| `.button` | Montserrat | 600, uppercase, letter-spacing 0.08em, 0.875rem |
| `.lesson-day` | Montserrat | 600, uppercase, letter-spacing 0.14em, dorado, 0.75rem |
| `.stat-card-label` | Montserrat | uppercase, letter-spacing 0.12em, 0.75rem |
| `.badge` | Montserrat | 600, uppercase, letter-spacing 0.1em, 0.7rem |
| `.admin-table thead th` | Montserrat | 600, uppercase, letter-spacing 0.1em, dorado, 0.75rem |

### 19.5 Accesibilidad

- Contraste texto principal `#f5f5f5` sobre fondo `#0a0a0a`: ratio ≈ 18.7:1 (supera AAA con holgura).
- Dorado `#d4af37` sobre `#0a0a0a`: ratio ≈ 9.1:1 (supera AAA en texto grande, AA en texto pequeño).
- Texto muted `#b8b8b8` sobre `#0a0a0a`: ratio ≈ 10.4:1 (supera AAA).
- Focus visible: todos los inputs interactivos tienen `box-shadow: 0 0 0 3px rgba(212,175,55,0.18)` + borde dorado pleno. Botones primary usan `outline: 3px solid var(--color-gold-bright)` con offset.
- `prefers-reduced-motion: reduce`: anula todas las transitions y desactiva los `translateY/translateX` de los hovers.
- `meta name="color-scheme" content="dark"`: indica al navegador que use UI nativa oscura (scrollbars, form controls cuando no se sobreescriben).

### 19.6 Qué NO se cambió

- Plantillas PHP: ninguna se tocó estructuralmente. Todos los selectores CSS existentes se mantienen, solo cambia su look. Si una plantilla tenía `class="auth-card"`, sigue funcionando.
- Lógica JavaScript: `copy.js` no se tocó. Sigue siendo el único JS del proyecto.
- Esquema de BD: cero cambios.
- CSP, headers de seguridad, autenticación, sesiones, CSRF, uploads, embeds: cero cambios.

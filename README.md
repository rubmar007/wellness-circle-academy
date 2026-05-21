# Wellness Circle Academy

Plataforma privada de duplicación para equipos de bienestar y network marketing. Backend en PHP 8.1+, base de datos PostgreSQL (Neon), deploy en Railway. Frontend HTML + CSS moderno; el único JavaScript permitido es un snippet aislado para "Copiar texto" al portapapeles.

## Stack

- PHP 8.1+ con Composer y PSR-4 (namespace `App\` → `src/`).
- PostgreSQL 14+ (Neon).
- HTML5 + CSS moderno (mobile-first). HTMX se introducirá solo cuando una funcionalidad concreta lo requiera.
- Sin frameworks de cliente. Sin bundler. Sin pasos de build de frontend.
- JS de cliente reducido a `public/assets/js/copy.js` (clipboard API).

## Estructura

```
.
├── public/                 Document root (lo único expuesto al web).
│   ├── index.php           Front controller.
│   ├── .htaccess           Rewrite + headers de seguridad (Apache).
│   └── assets/
│       ├── css/styles.css
│       ├── js/copy.js      Único JS permitido.
│       └── uploads/        Imágenes subidas (vacío en repo).
├── src/                    Código PHP (PSR-4 App\).
│   ├── Router.php
│   ├── Auth.php
│   ├── View.php
│   ├── Csrf.php
│   ├── Security.php
│   ├── Support/Env.php
│   ├── Database/Connection.php
│   └── Controllers/
├── templates/              Vistas PHP (sin lógica de negocio).
├── database/
│   ├── schema.sql          Esquema inicial.
│   └── seed.sql            Datos de ejemplo (programa Arranque, Día 1).
├── composer.json
├── .env.example            Plantilla de variables de entorno.
├── CLAUDE.md               Contrato de trabajo del proyecto.
└── README.md
```

## Requisitos locales

- PHP 8.1 o superior con extensiones: `pdo`, `pdo_pgsql`, `mbstring`, `fileinfo`.
- Composer 2.x.
- Acceso a una base PostgreSQL (Neon u otra) para ejecutar `database/schema.sql`.

## Arranque local

1. Clonar el repo y entrar al directorio:
   ```bash
   git clone git@github-personal:rubmar007/wellness-circle-academy.git
   cd wellness-circle-academy
   ```

2. Instalar dependencias PHP:
   ```bash
   composer install
   ```

3. Copiar el archivo de entorno y rellenar valores reales:
   ```bash
   cp .env.example .env
   php -r "echo 'APP_KEY=' . base64_encode(random_bytes(32)) . PHP_EOL;" >> .env
   ```
   Editar `.env` y poner `DATABASE_URL` apuntando a Neon (formato `postgresql://user:pass@host/db?sslmode=require`).

4. Cargar el esquema en Neon:
   ```bash
   psql "$DATABASE_URL" -f database/schema.sql
   psql "$DATABASE_URL" -f database/seed.sql   # opcional: datos de ejemplo
   ```

5. Levantar el servidor PHP integrado:
   ```bash
   php -S localhost:8080 -t public
   ```

6. Abrir `http://localhost:8080` en el navegador.

## Seguridad

Defensas aplicadas en el código base:

- Sesiones con `HttpOnly`, `SameSite=Lax`, `Secure` (en HTTPS), `use_strict_mode`. Regeneración de `session_id` tras login.
- CSRF token por sesión, validado con `hash_equals` en cada POST.
- Passwords con `password_hash()` usando `PASSWORD_ARGON2ID` (fallback `PASSWORD_DEFAULT`).
- PDO con prepared statements parametrizados. Prohibida la concatenación de SQL.
- Headers en cada respuesta: `Content-Security-Policy` estricta con `nonce`, `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `Referrer-Policy: same-origin`, `Permissions-Policy`.
- Escape de salida con `e()` (`htmlspecialchars` con `ENT_QUOTES | ENT_SUBSTITUTE`).
- Subida de imágenes: validación de MIME real con `finfo`, tamaño máximo, extensión whitelist, renombrado con UUID. `.htaccess` en `uploads/` que niega ejecución de PHP.
- Rate limiting básico en login (intentos por email/IP, bloqueo temporal tras N fallos).
- Errores sin detalles cuando `APP_DEBUG=false`.

## Deploy

Pendiente de configurar Railway. Cuando exista la cuenta:

- Variables de entorno en Railway: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, `APP_NAME`, `APP_KEY`, `DATABASE_URL` (Neon).
- Document root: `public/`.
- Almacenamiento de imágenes en producción: definir entre Railway Volumes o servicio externo (Cloudinary/R2). No usar el filesystem efímero del contenedor.

## Roadmap del MVP

Fases implementadas en el esqueleto:

- [x] Estructura PSR-4, front controller, router minimal.
- [x] Conexión PostgreSQL (Neon) desde `DATABASE_URL`.
- [x] Esquema de BD: `users`, `programs`, `lessons`, `user_progress`, `login_attempts`.
- [x] Auth: login, logout, sesión, CSRF, rate limit. **Sin registro público** — los usuarios se crean por CLI (`bin/create-user.php`) o desde el panel admin (próxima fase).
- [x] Layout, CSS mobile-first con la paleta del documento. La primera pantalla es el login.
- [x] Dashboard de programas y vista de día con checklist.
- [x] Botón "Copiar texto" (único JS permitido).
- [x] Botón "Descargar imagen" (HTML puro).
- [ ] Panel admin completo (crear/editar programas, lecciones e usuarios, subir imágenes).
- [ ] Despliegue en Railway.

### Crear usuarios (mientras no exista la UI de admin)

```bash
php bin/create-user.php
```

El script pide nombre, email, rol (`admin` o `member`) y contraseña de forma interactiva (la contraseña no se muestra en pantalla ni queda en el historial del shell).

## Documentación final

Al cierre del proyecto se entregará un manual maestro en `.md` con todo lo necesario para operar, mantener y extender el sistema, según la sección "Documentación de cada proyecto" del `CLAUDE.md`.

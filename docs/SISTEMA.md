# Wellness Circle Academy — Descripción del sistema

**URL de producción:** https://wellnessca.martavilla.com.mx
**Stack:** PHP 8.1+ · PostgreSQL (Neon) · Deploy en Railway
**Repo local:** `/mnt/c/Users/Rub/Documents/PersonalClaude/wellness-circle-academy`

---

## Qué es

Plataforma privada de aprendizaje para miembros de Wellness Circle Academy. El acceso es exclusivo: no hay registro público. Los usuarios son creados por el administrador. El sistema tiene dos roles: `admin` y `member`.

---

## Roles

| Rol | Acceso |
|-----|--------|
| `member` | Área de miembro: dashboard, programas, lecciones, feed, noticias, entrenamiento, materiales, eventos, promociones, normas, página de cliente, perfil |
| `admin` | Todo lo anterior + panel de administración completo |

---

## Módulos del área de miembro

### Dashboard (`/dashboard`)
Pantalla de bienvenida tras login. Punto de entrada al área privada.

### Programas y Lecciones (`/programas/{slug}`, `/programas/{slug}/dia/{day}`)
El núcleo del sistema. Cada programa agrupa una secuencia de lecciones numeradas por día.

Cada lección contiene:
- Objetivo del día
- Texto de post
- Texto de historia
- Texto de conversación
- Texto de respuesta
- Texto de seguimiento
- Texto de acción
- Consejo
- Imagen
- Video incrustado
- Archivo descargable
- Checklist de ítems (array JSON)

El miembro puede marcar y desmarcar cada ítem del checklist. El progreso se guarda en la tabla `user_progress` por usuario, lección e índice de ítem.

### Feed / Inicio (`/inicio`)
Muro de publicaciones del miembro. Puede escribir entradas. El admin puede eliminar cualquier entrada. No hay interacción entre miembros (sin likes ni comentarios).

### Noticias (`/noticias`)
Listado de noticias publicadas por el admin. Solo lectura para el miembro.

### Entrenamiento (`/entrenamiento`)
Sección de recursos de entrenamiento publicados por el admin. Solo lectura.

### Materiales (`/materiales`)
Archivos descargables organizados en carpetas. El admin los gestiona; el miembro los descarga.

### Eventos (`/eventos`)
Listado de eventos con imagen y descripción. Solo lectura para el miembro.

### Promociones (`/promociones`)
Sección de ofertas o promociones publicadas por el admin. Solo lectura.

### Normas y Reglamentos (`/normas`)
Página de contenido único (no listado). El admin edita el texto desde el panel. El miembro lo lee.

### Soy Cliente (`/soy-cliente`)
Página de contenido especial para clientes. Soporta texto enriquecido y PDF. El admin la edita desde `/admin/cliente`.

### Perfil (`/perfil`)
El miembro puede ver y actualizar sus datos de perfil.

---

## Panel de administración (`/admin`)

CRUD completo para todos los módulos de contenido:

| Sección | Ruta base |
|---------|-----------|
| Usuarios | `/admin/usuarios` |
| Programas | `/admin/programas` |
| Lecciones | `/admin/programas/{id}/lecciones` / `/admin/lecciones/{id}` |
| Carga batch de lecciones (XLSX) | `/admin/programas/{id}/batch` |
| Entrenamiento | `/admin/entrenamiento` |
| Materiales | `/admin/materiales` |
| Eventos | `/admin/eventos` |
| Noticias | `/admin/noticias` |
| Promociones | `/admin/promociones` |
| Normas y Reglamentos | `/admin/normas` |
| Soy Cliente | `/admin/cliente` |

La carga batch permite subir un XLSX con lecciones de un programa completo en un solo paso, en lugar de crearlas una a una.

---

## Autenticación y seguridad

- Login con email + contraseña (bcrypt).
- Rate limiting de intentos de login por email y por IP (tabla `login_attempts`).
- Sesiones PHP con cookies `HttpOnly`, `SameSite=Lax`, y `Secure` en producción.
- HTTPS forzado en producción (redirect 301).
- CSRF tokens en todos los formularios POST.
- Recuperación de contraseña vía URL oculta `/ctoadmin` (no hay enlace visible en la app). Envía email con token. El token expira.
- Headers de seguridad aplicados globalmente.
- Sin JavaScript de cliente: toda la lógica es PHP en servidor.

---

## Diseño visual

Tema oscuro fijo. Paleta `#0a0a0a` (fondo) con acento dorado `#d4af37`. Tipografías self-hosted: Allura (títulos decorativos h1) + Montserrat variable (todo el resto). Sin dependencias externas de fuentes en runtime.

---

## Base de datos — tablas principales

| Tabla | Propósito |
|-------|-----------|
| `users` | Cuentas del sistema (admin / member) |
| `programs` | Programas de aprendizaje |
| `lessons` | Lecciones diarias por programa |
| `user_progress` | Ítems de checklist completados por usuario |
| `login_attempts` | Rate limiting de login |
| `sections` (y relacionadas) | Contenido de las secciones (entrenamiento, materiales, eventos, noticias, promociones, etc.) |
| `password_resets` | Tokens de recuperación de contraseña |

---

## Operación local

```bash
cd /mnt/c/Users/Rub/Documents/PersonalClaude/wellness-circle-academy
php -S localhost:8080 -t public
```

Variables de entorno en `.env` (ver `.env.example`). Deploy: `git push origin main` → Railway detecta el push y redeploya automáticamente.

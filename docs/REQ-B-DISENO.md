# Req B — Diseño de la aplicación por secciones

Documento de diseño para la fase 2 de Wellness Circle Academy, basado en los requerimientos de Marta (`Req Marta/aplicacion con nuevas secciones.docx`). Pendiente de aprobación antes de tocar código de producción.

Acompaña a este documento un **mockup local navegable** (ver sección 7) para que Marta vea la propuesta visual.

---

## 1. Qué cambia respecto a hoy

Hoy la app es **admin empuja, miembro consume**: el admin carga programas y lecciones, el miembro los lee y copia. La navegación es mínima (Dashboard, Admin, Cerrar sesión).

Req B convierte la app en un **portal por secciones** e introduce dos cosas nuevas:

1. **Arquitectura de navegación por secciones** — un menú lateral (desktop) / desplegable (móvil) con todas las secciones. Todo lo demás cuelga de esto.
2. **Contenido generado por miembros** — el feed de Inicio y la foto de perfil. Por primera vez un miembro sube contenido. Es el cambio más delicado en seguridad.

Lo bueno: ~70% reutiliza lo que ya existe y está probado (parser de YouTube/Vimeo, subida segura de imágenes, whitelist de Drive anti-SSRF, autenticación, CSRF, escape de salida).

---

## 2. Secciones (orden del menú)

| # | Sección | Qué hace | Quién publica | Reutiliza |
|---|---|---|---|---|
| 1 | **Inicio** | Muro/feed: el miembro comparte avance, historia o logro con texto + foto opcional | Miembros (moderado por admin) | Subida segura de imágenes |
| 2 | **Tus primeros pasos** | Los planes a 30 días (Arranque, Negocio, X39, Aeon…) | Admin | **Ya existe** (programas + lecciones) |
| 3 | **Entrenamiento** | Videos de YouTube por tema: Plan de Compensación, Clientes, Autoenvío, Oficina virtual, Doctores | Admin | `Embed.php` (YouTube/Vimeo) |
| 4 | **Materiales** | Subsecciones: PDFs, Imágenes, Enlaces | Admin | Whitelist de Drive |
| 5 | **Eventos** | Calendario semanal con enlace para entrar; filtro por tipo (Taller, Entrenamiento, Presentación de oportunidad, Todos) | Admin | — (tabla nueva) |
| 6 | **Notificaciones** | Reconocimientos a líderes y avisos | Admin | — (tabla nueva) |
| 7 | **Normas y Reglamentos** | Reglas del equipo (contenido estático editable) | Admin | Patrón de lección sin checklist |
| 8 | **Perfil** | Datos del miembro + foto propia | Miembro (la suya) | Subida segura de imágenes |
| — | **WhatsApp** | Botón que abre chat directo al celular de Marta (`wa.me`) | — | — (un enlace) |

---

## 3. Modelo de datos (tablas nuevas)

Cada sección es, en esencia, *una tabla + un controller + una vista* con los patrones de seguridad ya existentes. Tablas nuevas propuestas:

- `posts` — feed de Inicio: `id, user_id, body, image_url, is_hidden, created_at`. Comentarios/aplausos: fase posterior (`post_comments`, `post_reactions`) si se aprueban.
- `trainings` — Entrenamiento: `id, category, title, video_url, display_order, is_published, created_at`. `category` enum: plan_compensacion, clientes, autoenvio, oficina_virtual, doctores.
- `materials` — Materiales: `id, type, title, url_or_path, display_order, is_published`. `type` enum: pdf, image, link.
- `events` — Eventos: `id, title, event_type, starts_at, join_url, description, is_published`. `event_type` enum: taller, entrenamiento, oportunidad.
- `announcements` — Notificaciones: `id, kind, title, body, created_at`. `kind`: reconocimiento, aviso.
- `pages` — Normas y Reglamentos (y futuros textos estáticos): `id, slug, title, body, updated_at`.
- `users` — agregar `photo_url` para la foto de perfil.

Programas y lecciones (Tus primeros pasos) no cambian de esquema.

---

## 4. Permisos por sección

- **Miembro**: ve todas las secciones publicadas; publica en Inicio; edita su propio Perfil/foto.
- **Admin**: todo lo del miembro + crea/edita/elimina en Entrenamiento, Materiales, Eventos, Notificaciones, Normas; modera (oculta/elimina) publicaciones del feed.

---

## 5. Seguridad (lo crítico de esta fase)

El salto de seguridad es el **contenido de miembros** (Inicio y foto de Perfil). Reglas:

- Toda imagen subida por miembro pasa por la **misma** validación que ya existe: MIME real con `finfo`, whitelist JPG/PNG/WebP, tamaño máximo, renombrado con UUID. Sin excepciones.
- Todo texto de miembro se escapa en salida con `e()` / `e_nl2br()` (anti-XSS). Ya es el estándar del proyecto.
- **Moderación**: el admin puede ocultar o eliminar cualquier publicación. Campo `is_hidden`.
- Rate limit de publicación por usuario (evitar spam/flood), reutilizando el patrón de `login_attempts`.
- CSRF en todos los POST (ya es obligatorio en el proyecto).

---

## 6. Plan de ejecución por fases

Cada sección se entrega y despliega **una por una**, verificable antes de la siguiente (paso a paso).

**Fase 0 — Esqueleto de navegación.** Menú de secciones, layout móvil/desktop, routing y permisos. Sin features. Es el cimiento. *(El mockup ya muestra cómo se verá.)*

**Fase 1 — Secciones "push" baratas (reusan lo existente):**
- Tus primeros pasos (reubicar programas — casi gratis)
- Normas y Reglamentos (contenido estático)
- Botón WhatsApp (enlace `wa.me`)
- Entrenamiento (videos por tema — reusa `Embed.php`)
- Materiales (PDFs/Imágenes/Enlaces vía Drive)

**Fase 2 — Push con más modelo de datos:**
- Eventos (calendario + filtro por tipo en CSS puro)
- Notificaciones (reconocimientos)

**Fase 3 — Contenido de miembros (el salto de seguridad):**
- Perfil (foto propia — estrena el patrón de subida de miembro, acotado)
- Inicio / feed — lo más grande y al final

---

## 7. Mockup local

Prototipo visual navegable, **solo local** (no se despliega; está en `.gitignore`). Sirve para que Marta apruebe la estructura y el look antes de programar.

Para verlo:

```bash
php -S localhost:8081 -t public
```

Luego abrir: `http://localhost:8081/mockup/index.php`

El mockup usa el tema real (paleta oscura + dorado, tipografías Allura/Montserrat) y es 100% HTML + CSS, sin JavaScript. Los botones no guardan nada: es solo demostración visual.

---

## 8. Ambigüedades a resolver antes de programar el feed (Fase 3)

Estas decisiones cambian el diseño del feed y conviene cerrarlas con Marta:

1. **Alcance del feed**: ¿es un muro global de todo el equipo, o separado por sub-equipos/líder?
2. **Moderación**: ¿quién puede ocultar/eliminar publicaciones — solo Marta, o cualquier admin?
3. **Interacciones**: ¿el MVP del feed incluye aplausos/comentarios, o solo publicaciones en una primera versión?
4. **Visibilidad**: ¿los miembros se ven entre sí (perfiles), o el directorio es privado?

---

## 9. Nota sobre JavaScript

Todo lo anterior se puede construir con **HTML + CSS** (regla 6). Donde un refresco sin recargar mejore la experiencia (ej. publicar en el feed y ver el post aparecer sin recargar), se usaría **HTMX** (una sola dependencia, la lógica vive en PHP del servidor, sin escribir JS a mano). **JavaScript a mano solo si Rub lo autoriza explícitamente**, caso por caso. Ninguna validación ni seguridad depende del cliente.

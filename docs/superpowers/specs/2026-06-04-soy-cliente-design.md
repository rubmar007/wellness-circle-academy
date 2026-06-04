# Diseño: Sección "Soy Cliente"

## Resumen

Agregar una nueva sección "Soy Cliente" en la navegación, inmediatamente después de "Tus primeros pasos". Esta sección es una página de contenido fijo (no una lista de ítems) dirigida a personas que solo son clientes del producto. Se introduce un nuevo rol `cliente` que restringe el acceso a un subconjunto de secciones.

---

## Rol `cliente`

Se agrega `'cliente'` como valor válido en `users.role` (VARCHAR existente, sin migración de esquema).

### Secciones accesibles para `cliente`

| Sección | Acceso |
|---|---|
| Inicio | Sí |
| Tus primeros pasos | Sí |
| **Soy Cliente** | Sí (nueva) |
| Eventos | Sí |
| Normas y Reglamentos | Sí |
| Perfil | Sí |
| Entrenamiento | No — 403 |
| Materiales | No — 403 |
| Notificaciones | No — 403 |
| Admin | No — 403 |

El sidebar en `layout.php` oculta los ítems a los que el rol `cliente` no tiene acceso. Los controladores de Entrenamiento, Materiales y Notificaciones también lanzan 403 si `$auth['role'] === 'cliente'` para proteger acceso directo por URL.

---

## Base de datos

### Migración: `2026-06-04-add-client-page.sql`

```sql
CREATE TABLE client_page (
    id                        SMALLINT PRIMARY KEY DEFAULT 1,
    welcome_image_url         VARCHAR(500),
    uso_texto                 TEXT,
    activar_texto             TEXT,
    activar_video_url         VARCHAR(500),
    desactivar_texto          TEXT,
    desactivar_video_url      VARCHAR(500),
    preferente_texto          TEXT,
    preferente_video_url      VARCHAR(500),
    beneficios_autoenvio_url  VARCHAR(500),
    beneficios_preferente_url VARCHAR(500),
    texto_libre               TEXT,
    updated_at                TIMESTAMPTZ DEFAULT NOW()
);

INSERT INTO client_page (id) VALUES (1);
```

El `id = 1` fijo garantiza exactamente una fila. El admin edita esa fila — no hay crear ni eliminar.

Los campos `*_video_url` se validan con `Embed::parseVideo()` (YouTube/Vimeo, ya existe). Los campos `*_image_url` se gestionan con `Upload::save()` (ya existe). `texto_libre` no se renderiza en la vista si está vacío o es NULL.

---

## Rutas

```
GET  /soy-cliente           ClientController::show
GET  /admin/cliente         AdminClientController::edit
POST /admin/cliente         AdminClientController::update
```

---

## Controladores

### `ClientController::show`

- Requiere login. Cualquier rol puede acceder.
- Carga la fila única de `client_page`.
- Pasa `$page` y `$auth` al template `client/show.php`.

### `AdminClientController::edit` (GET)

- Requiere rol `admin`.
- Carga la fila única de `client_page`.
- Renderiza `admin/client/edit.php`.

### `AdminClientController::update` (POST)

- Requiere rol `admin`. Valida CSRF.
- Procesa campos de texto y URLs de video (vía `Embed::parseVideo()`).
- Si se sube imagen de bienvenida o imágenes de beneficios, las guarda con `Upload::save()`.
- Hace UPDATE de la fila id=1 en `client_page`.
- Redirige a `GET /admin/cliente` con mensaje de éxito.

---

## Templates

### `templates/client/show.php`

Estructura de la página (en orden):

1. **Bloque bienvenida** — imagen `welcome_image_url` + título "Bienvenid@ {nombre del usuario" (texto PHP sobre la imagen, CSS posicionado).
2. **Bloque uso** — título "¿Cómo utilizar el producto? / Importancia de la Hidratación" + cuerpo `uso_texto`. Solo se renderiza si `uso_texto` no es vacío.
3. **Bloque activar** — título "¿Cómo activar autoenvío?" + `activar_texto` + embed `activar_video_url`. Cada sub-elemento se renderiza solo si tiene contenido.
4. **Bloque desactivar** — título "¿Cómo desactivar autoenvío?" + `desactivar_texto` + embed `desactivar_video_url`.
5. **Bloque preferente** — título "¿Cómo convertirte en cliente preferente plus?" + `preferente_texto` + embed `preferente_video_url`.
6. **Bloque beneficios autoenvío** — título "Beneficios del autoenvío / Regalos" + imagen `beneficios_autoenvio_url`.
7. **Bloque beneficios preferente** — título "Beneficios del cliente preferente plus" + imagen `beneficios_preferente_url`.
8. **Bloque texto libre** — sin título fijo, solo cuerpo `texto_libre`. No se renderiza si es NULL o vacío.

Cada bloque usa las clases CSS existentes de `sections.css` (`.section-card`, `.lesson-block`, etc.) para mantener consistencia visual.

Los embeds de video usan `Embed::iframe()` (ya existe).

### `templates/admin/client/edit.php`

Formulario único con:
- Upload de imagen de bienvenida (preview de la actual si existe)
- Textarea para `uso_texto`
- Textarea + input URL para `activar_texto` / `activar_video_url`
- Textarea + input URL para `desactivar_texto` / `desactivar_video_url`
- Textarea + input URL para `preferente_texto` / `preferente_video_url`
- Upload de imagen para `beneficios_autoenvio_url`
- Upload de imagen para `beneficios_preferente_url`
- Textarea para `texto_libre`
- Botón guardar

---

## Navegación (`layout.php`)

Se agrega "Soy Cliente" al array `$sectionNav` después de "Tus primeros pasos":

```php
['href' => '/soy-cliente', 'label' => 'Soy Cliente', 'icon' => '⭐', 'match' => '/soy-cliente'],
```

Los ítems de Entrenamiento, Materiales y Notificaciones se envuelven en una condición:

```php
if (($auth['role'] ?? '') !== 'cliente')
```

---

## Guards en controladores existentes

`TrainingController`, `MaterialController`, `AnnouncementController`: al inicio del método público, si `$auth['role'] === 'cliente'` → `View::abort(403)`.

---

## Panel admin de usuarios

`AdminUsersController` — formulario de crear/editar usuario: agregar `<option value="cliente">Cliente</option>` en el selector de rol.

---

## Lo que NO cambia

- Tabla `users` — sin cambio de esquema.
- Sistema de autenticación — sin cambio.
- Cualquier sección existente para roles `admin` y `member`.
- Estructura de lecciones, programas, materiales — sin cambio.

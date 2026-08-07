# Wellness Circle Academy — Descripción del sistema (ampliada vía crawl)

**URL de producción:** https://academiawca.com
**Stack:** PHP 8.1+ · PostgreSQL (Neon) · Deploy en Railway
**Estado real (visto en `/admin`):** 26 usuarios · 7 programas · 156 lecciones

---

## Correcciones/actualizaciones vs. SISTEMA.md original

- El dominio real en uso es `academiawca.com` (no `wellnessca.martavilla.com.mx`).
- Hay **3 roles**, no 2: `admin`, `member` y **`cliente`** (visible en el selector de rol al crear/editar usuario).
- El tema visual **no es fijo**: hay un selector en el footer con 4 temas — Oscuro (default), Claro, Lifewave, Marino.
- El hash de contraseña para usuarios nuevos usa **Argon2id** (el formulario de "Nuevo usuario" lo indica explícitamente), no solo bcrypt.
- Cada lección tiene además un toggle individual "Publicada" (no solo el programa completo).

---

## Roles (confirmado en `/admin/usuarios`)

| Rol | Descripción |
|---|---|
| `admin` | Acceso total + panel `/admin` |
| `member` | Área de miembro estándar (distribuidor/afiliado) |
| `cliente` | Ve una versión reducida enfocada en `/soy-cliente` |

---

## Navegación del área de miembro (confirmada)

Inicio · Tus primeros pasos (`/dashboard`) · Soy Cliente · Noticias · Entrenamiento · Materiales · Eventos · Promociones · Normas y Reglamentos · Perfil · Contactar por WhatsApp (enlace externo a wa.me).

## Programas existentes (`/dashboard` → `/programas/{slug}`)

| # | Programa | Slug | Lecciones | Estado |
|---|---|---|---|---|
| 1 | Arranque | `arranque` | 31 | Publicado |
| 2 | Negocio | `negocio` | 31 | Publicado |
| 3 | X39 | `x39` | 31 | Publicado |
| 4 | Aeon | `aeon` | 31 | Publicado |
| 5 | Distribuidores | `distribuidores` | 0 | **Borrador** (sin lecciones aún) |
| 6 | X20 - Hidratación Inteligente | `x20` | 1 | Publicado (solo Día 2 cargado) |
| 7 | Centro de Conocimiento | `centrodeconocimiento` | 31 | Publicado |

Cada lección (`/programas/{slug}/dia/{n}`) confirmé que contiene: objetivo del día, publicación principal (con botón "copiar texto"), story sugerida, conversación ejemplo, respuesta, seguimiento, acción del día, tip del día, imagen, video embebido (YouTube opcional), archivo descargable (Drive opcional) y checklist de ítems marcables que persiste el progreso.

**Temática de cada programa (resumen, no el contenido literal de las lecciones):**
- **Arranque**: guion de 31 días para presentarse en redes, generar curiosidad, compartir testimonios propios y ajenos, introducir la oportunidad de negocio y cerrar el mes con celebración.
- **Negocio**: guion de 31 días orientado a visión de industria, manejo de objeciones, plan de compensación, libertad de tiempo/ingreso residual y reclutamiento.
- **X39**: 31 variaciones de contenido segmentadas por audiencia (jóvenes, deportistas, bienestar femenino, menopausia, adultos mayores, etc.), cada una con su propio enfoque de beneficio.
- **Aeon**: 31 días centrados en manejo del estrés, sistema nervioso, cortisol, estrés oxidativo y testimonios de calma/sueño.
- **X20 - Hidratación Inteligente**: programa en construcción; solo existe el Día 2 (importancia del agua/hidratación), enfocado en generar conversación, no venta directa.
- **Centro de Conocimiento**: 31 días de contenido educativo más "científico/biohacking" (glutatión, células madre, mitocondrias, inflamación, epigenética, GHK-Cu/AHK-Cu, fototerapia), con referencias a estudios 2024-2026.
- **Distribuidores**: programa creado en el admin pero aún vacío (borrador, 0 lecciones).

---

## Módulos de contenido (confirmado en front + admin)

### Soy Cliente (`/soy-cliente`, editable en `/admin/cliente`)
Página con más subsecciones de las documentadas originalmente: imagen de bienvenida, texto + PDF de "cómo usar el producto / hidratación", texto + video de "cómo activar autoenvío", texto + video de "cómo desactivar autoenvío", texto + video de "cómo convertirte en cliente preferente plus", imagen de "beneficios del autoenvío/regalos", imagen de "beneficios del cliente preferente plus" y un bloque de texto libre opcional.

### Noticias (`/noticias`, admin en `/admin/noticias`)
2 noticias publicadas actualmente. Cada una admite imagen/video y botón de enlace. Ejemplo de temas vistos: aviso de contacto oficial de servicio al cliente y felicitación a una manager por rango alcanzado.

### Entrenamiento (`/entrenamiento`, admin en `/admin/entrenamiento`)
Videos agrupados por **tema** (no es una lista plana): Plan de Compensación, Clientes, Autoenvío, Oficina Virtual, Doctores. Actualmente hay 12 videos publicados repartidos en esos temas (todos alojados en YouTube).

### Materiales (`/materiales`, admin en `/admin/materiales`)
16 materiales activos, organizados por **tipo** (PDF, Imagen, Enlace) y **carpeta** opcional con **orden** manual. Ejemplos: Plan de Compensación, guías paso a paso, scripts (Doctores, Emprendedores, Dolor Crónico), brochures de X2O, enlace al Drive del equipo y al "Influencer Playbook" de LifeWave.

### Eventos (`/eventos`, admin en `/admin/eventos`)
Calendario con **tipo** (Taller, Entrenamiento, Presentación de oportunidad), fecha/hora, enlace de Zoom/Meet opcional, imagen/flyer y estado publicado/borrador. Actualmente hay ~22 eventos entre junio y agosto 2026, varios con presentadores externos (doctores e invitados) y llamadas de equipo.

### Promociones (`/promociones`, admin en `/admin/promociones`)
3 promociones activas (imagen + enlace cada una), ligadas a un sistema de puntos y ventanas de calificación (ej. una promoción hacia la conferencia Luminate 2026 en Anaheim con 500 puntos requeridos).

### Normas y Reglamentos (`/normas`, admin en `/admin/normas`)
Editor simple de título + contenido enriquecido. El contenido actual son 10 normas de convivencia de la comunidad (participación, respeto, enfoque temático, celebrar avances, etc.).

### Perfil (`/perfil`)
Permite editar nombre y foto de perfil (JPG/PNG/WebP, máx. 5 MB). Muestra nombre, email y rol de la cuenta autenticada.

### Feed / Inicio (`/inicio`)
Muro tipo timeline donde los miembros publican texto + foto opcional; el admin puede eliminar cualquier publicación. Sin likes/comentarios, confirmado como en el documento original. (No reproduzco las publicaciones de otros miembros por ser contenido personal de terceros).

---

## Panel de administración (`/admin`) — detalle de formularios

| Sección | Campos observados |
|---|---|
| Usuarios (`/admin/usuarios`) | Nombre, Email, Rol (Miembro/Administrador/Cliente), Contraseña (Argon2id, mín. 10 caracteres), Estado (Activo/Desactivar) |
| Programas (`/admin/programas/{id}/editar`) | Título, Presentación (corta, máx. 200 caracteres), Slug, Descripción, Imagen de portada, Orden, Publicado (bool) |
| Lecciones (`/admin/lecciones/{id}/editar`) | Día, Publicada (bool), Título, Objetivo del día, Publicación para redes, Story sugerida, Conversación ejemplo, Respuesta, Seguimiento, Acción del día, Tip del día, Imagen, URL de video, URL de descarga (Drive, requiere permiso "cualquiera con el enlace"), Checklist (máx. 20 ítems, 200 caracteres c/u) |
| Carga batch (`/admin/programas/{id}/batch`) | Plantilla XLSX descargable con encabezados y ejemplo del Día 1 precargado |
| Entrenamiento (`/admin/entrenamiento/nuevo`) | Tema (Plan de Compensación/Clientes/Autoenvío/Oficina virtual/Doctores), Título, URL de video, Orden, Publicado |
| Materiales (`/admin/materiales/nuevo`) | Tipo (PDF/Imagen/Enlace), Título, URL (Drive o enlace externo), Imagen (si tipo=Imagen), Carpeta opcional, Orden, Publicado |
| Eventos (`/admin/eventos/nuevo`) | Título, Tipo (Taller/Entrenamiento/Presentación de oportunidad), Fecha y hora, Enlace para entrar, Descripción, Imagen, Publicado |
| Noticias / Promociones | Formularios similares: título + imagen/video + botón de enlace + estado |
| Normas (`/admin/normas`) | Título + Contenido (texto enriquecido) |
| Soy Cliente (`/admin/cliente`) | Ver desglose de campos arriba en la sección "Soy Cliente" |

---

## Autenticación y seguridad (confirmado, sin cambios sustanciales)

Login con email + contraseña, rate limiting, cookies seguras, HTTPS forzado, CSRF en formularios, recuperación de contraseña vía URL oculta, headers de seguridad, sin JS de cliente para lógica de negocio. Nuevo dato: hashing con **Argon2id** para cuentas nuevas.

---

## Notas de la exploración

- No incluí en este documento las publicaciones personales de otros miembros en el feed, ni la lista completa de los 26 usuarios con sus correos, para no exponer datos personales de terceros innecesariamente. Si necesitas ese detalle específico dime y lo reviso contigo directamente.
- El programa "Distribuidores" existe en la base de datos pero está vacío y en borrador — probablemente en desarrollo.
- El programa "X20" está incompleto (solo 1 de 31 días cargado), también parece en desarrollo activo.
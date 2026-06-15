# WELLNESS CIRCLE ACADEMY  
## Plataforma de Duplicación para Equipos de Multinivel y Bienestar

---

# VISIÓN GENERAL

La plataforma será un sistema privado de entrenamiento y duplicación para equipos de bienestar y network marketing enfocados en productos como:

- X39
- Cellergize
- Glutathione
- Silent Night
- Protocolos de bienestar
- Liderazgo
- Redes sociales
- Conversaciones y cierres

El objetivo es que cualquier persona del equipo pueda entrar diariamente, copiar contenido listo para usar y ejecutarlo de forma simple desde su celular.

---

# OBJETIVO PRINCIPAL

Crear un sistema donde:

✅ El líder sube contenido UNA sola vez  
✅ El equipo entra diariamente  
✅ Copia y pega publicaciones  
✅ Descarga imágenes  
✅ Aprende conversaciones  
✅ Duplica el sistema fácilmente  

---

# TIPO DE PLATAFORMA

## Web App privada con login

Compatible con:
- Celular
- Tablet
- Computadora

---

# TECNOLOGÍAS RECOMENDADAS

## Frontend
- React
- Next.js
- TailwindCSS

## Backend
- Firebase o Supabase

## Hosting
- Vercel

## Base de datos
- Firebase Firestore
o
- Supabase PostgreSQL

## Almacenamiento de imágenes
- Firebase Storage
o
- Cloudinary

---

# SISTEMA DE USUARIOS

## Tipos de usuario

### 1. Administrador
Puede:
- Crear programas
- Editar programas
- Subir imágenes
- Agregar días
- Crear usuarios
- Ver estadísticas

### 2. Miembro
Puede:
- Entrar a programas
- Ver contenido
- Descargar imágenes
- Copiar textos
- Marcar progreso

---

# LOGIN

## Funciones necesarias

- Registro de usuarios
- Inicio de sesión
- Recuperación de contraseña
- Seguridad básica
- Sesión persistente

---

# ESTRUCTURA PRINCIPAL

```plaintext
Dashboard
│
├── Arranque
├── X39
├── Cellergize
├── Glutathione
├── Silent Night
├── Liderazgo
├── Redes Sociales
├── Testimonios
└── Herramientas
```

---

# ESTRUCTURA DE PROGRAMAS

Cada programa tendrá:

```plaintext
Programa
│
├── Día 1
├── Día 2
├── Día 3
└── Día 30
```

---

# ESTRUCTURA INTERNA DE CADA DÍA

```plaintext
Día 1
│
├── Objetivo del día
├── Publicación principal
├── Texto para redes
├── Story sugerida
├── Imagen descargable
├── Conversación ejemplo
├── Acción del día
├── Tip del día
└── Checklist
```

---

# EJEMPLO DE CONTENIDO — DÍA 1

# ARRANQUE — DÍA 1

## Objetivo
Presentarte de forma natural en redes.

---

## Publicación para Facebook/Instagram

Muchas veces creemos que el bienestar es solamente ejercicio o alimentación…

Pero también es energía, descanso, enfoque mental y sentirte bien contigo mismo ✨

Estoy aprendiendo muchísimo sobre tecnologías de bienestar y biohacking natural y me emociona compartir este proceso 🙌

---

## Story sugerida

“Algo grande está cambiando en mi vida ✨”

---

## Conversación ejemplo

Amiga, últimamente he estado aprendiendo muchísimo sobre bienestar celular y energía natural.

Y sinceramente me ha sorprendido muchísimo cómo pequeños cambios pueden ayudarte a sentirte mejor 🙌

---

## Acción del día

- Publicar el post
- Subir 2 stories
- Hablar con 3 personas

---

# FUNCIONES IMPORTANTES

## 1. Botón “Copiar texto”

Cada publicación debe tener un botón:

```plaintext
[ COPIAR TEXTO ]
```

Para copiar automáticamente.

---

## 2. Botón “Descargar imagen”

```plaintext
[ DESCARGAR IMAGEN ]
```

---

## 3. Checklist diario

```plaintext
☐ Ya publiqué
☐ Ya subí stories
☐ Ya hablé con 3 personas
☐ Ya vi el entrenamiento
```

---

# PANEL DE ADMINISTRADOR

## Funciones

### Crear programas

Ejemplo:
- Arranque
- Cellergize
- X39

---

### Crear días

Ejemplo:
- Día 1
- Día 2
- Día 3

---

### Subir imágenes

Formatos:
- JPG
- PNG
- MP4

---

### Editar contenido

Campos:
- Título
- Texto
- Story
- Conversación
- Acción
- Imagen

---

# BASE DE DATOS — ESTRUCTURA

## Tabla: users

```sql
id
name
email
password
role
created_at
```

---

## Tabla: programs

```sql
id
title
description
cover_image
created_at
```

---

## Tabla: lessons

```sql
id
program_id
day_number
title
objective
post_text
story_text
conversation_text
action_text
image_url
created_at
```

---

# DISEÑO VISUAL

## Estilo

- Moderno
- Premium
- Wellness
- Minimalista

## Colores

```css
Azul marino
Azul celeste
Blanco
Dorado suave
```

---

# EXPERIENCIA MÓVIL

MUY IMPORTANTE.

El 90% de usuarios usarán celular.

La experiencia debe ser:
- rápida,
- simple,
- limpia,
- intuitiva.

---

# FUNCIONES FUTURAS

## FASE 2

### Videos
Entrenamientos internos.

### Biblioteca de testimonios
Texto + video.

### Recursos descargables
PDFs y guías.

### Calendario
Zooms y eventos.

---

# FASE 3

## Inteligencia Artificial

IA integrada para:

- responder prospectos,
- sugerir publicaciones,
- crear captions,
- generar mensajes.

---

# FASE 4

## Gamificación

- puntos,
- ranking,
- medallas,
- niveles,
- retos.

---

# IDEA DE FLUJO DEL USUARIO

```plaintext
Usuario entra
↓
Login
↓
Dashboard
↓
Selecciona programa
↓
Selecciona día
↓
Copia publicación
↓
Descarga imagen
↓
Publica
↓
Marca tarea completada
```

---

# ESTRUCTURA DE CARPETAS — FRONTEND

```plaintext
/ src
│
├── components
├── pages
├── layouts
├── services
├── hooks
├── styles
└── utils
```

---

# COMPONENTES IMPORTANTES

## DashboardCard
Muestra programas.

## LessonCard
Muestra días.

## CopyButton
Copia texto automáticamente.

## DownloadButton
Descarga imágenes.

## ProgressTracker
Guarda progreso.

---

# PROMPT PARA PROGRAMADOR O IA

```markdown
Crea una plataforma web moderna tipo academy para equipos de network marketing y bienestar.

La plataforma debe incluir:

- Login de usuarios
- Roles (admin y miembro)
- Dashboard con programas
- Programas divididos por días
- Cada día debe contener:
  - texto para redes,
  - imagen descargable,
  - conversación ejemplo,
  - checklist,
  - acción del día.

Tecnologías:
- Next.js
- Tailwind
- Firebase/Supabase

Diseño:
- Premium
- Wellness
- Responsive
- Mobile first

Agregar:
- botón copiar texto
- botón descargar imagen
- progreso del usuario
- panel administrador
```

---

# NOMBRES RECOMENDADOS

- Wellness Circle Academy
- Vida en Equilibrio Academy
- Wellness Leaders Hub
- Biohack Academy
- Elevate Wellness
- The Wellness Circle
- Team Light System

---

# PRIORIDAD PARA LANZAMIENTO

## MVP (Versión 1)

### NECESARIO
- Login
- Usuarios
- Programas
- Días
- Textos
- Imágenes
- Descargas

### NO necesario al inicio
- IA
- Rankings
- App móvil
- Videos avanzados

---

# TIEMPO ESTIMADO

## MVP
2–4 semanas

## Plataforma avanzada
2–4 meses

---

# META FINAL

Crear un sistema de duplicación simple donde cualquier persona pueda:

- entrar,
- aprender,
- copiar,
- publicar,
- conversar,
- crecer.

Sin complicarse técnicamente.

---

# RESULTADO

Un sistema escalable para:
- liderazgo,
- duplicación,
- entrenamiento,
- crecimiento orgánico,
- branding profesional,
- expansión internacional.

---

Tu luz, al brillar, inspira a otros a recordar que ellos también tienen luz para brillar… compártela. ✨

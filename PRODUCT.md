# Product

## Register

product

## Users

- **Promotores/líderes** (role `member`): distribuidores del equipo LifeWave que dan seguimiento a sus propios clientes, ven contenido de entrenamiento, materiales, comisiones y su red. Usan la app tanto en escritorio como en celular (PWA instalable).
- **Clientes** (role `cliente`): compraron un Experience Kit, dan seguimiento diario a su propio kit (checklist, diario, peso).
- **Admin** (role `admin`, Marta y Rub): dueños/operadores de la academia — publican contenido, asignan kits, programan notificaciones, y también actúan como promotores con su propia red.

Contexto de uso: sesiones cortas y frecuentes (revisar el kit del día, marcar el parche, ver una noticia), a menudo desde el celular, a veces mientras están con un cliente en persona.

## Product Purpose

Plataforma privada de duplicación y capacitación para un equipo de network marketing de LifeWave (Wellness Circle Academy). Centraliza: contenido de entrenamiento y materiales, seguimiento diario de "Experience Kits" (calendario de parches, checklist, diario/encuesta, insignias), la relación Promotor responsable ↔ cliente, y ahora notificaciones push. Éxito = que el equipo vuelva todos los días a marcar su progreso y que los promotores dejen de perseguir a sus clientes por WhatsApp para saber si siguieron el protocolo.

## Brand Personality

Premium y aspiracional, pero clara y funcional — no decorativa a costa de la legibilidad. Cálida y de equipo, no corporativa fría. El dorado sobre fondo oscuro y la tipografía cursiva de la marca ("Wellness Circle Academy") comunican exclusividad tipo club; el resto de la interfaz (contenido funcional: saludos, formularios, listas, checklists) debe ser limpio y altamente legible, sin heredar la cursiva decorativa. Esa tensión (marca decorativa vs. contenido funcional) es exactamente lo que motivó esta sesión de trabajo.

## Anti-references

- SaaS corporativo genérico (azul-morado-gradiente, look de startup de software sin personalidad).
- Estética "MLM vendehumos" — chillona, amarillista, tipo landing de esquema piramidal.
- Cualquier elemento decorativo que sacrifique legibilidad (la cursiva del saludo con nombres largos es el ejemplo concreto que disparó esta revisión).

## Design Principles

- La cursiva de marca se reserva para el logo y momentos ceremoniales puntuales (títulos de sección grandes); nunca para contenido dinámico/funcional como nombres de usuario, listas o formularios.
- Legibilidad primero: el contraste y la jerarquía tipográfica no se sacrifican por el "look" premium.
- Cero JavaScript de cliente salvo excepciones explícitamente autorizadas (copiar al portapapeles, Service Worker de push) — toda interactividad nueva debe resolverse con HTML+CSS primero.
- Los 4 temas (Oscuro, Claro, Lifewave, Marino) deben sentirse igual de premium y legibles entre sí — ninguno es "el tema feo que nadie usa".
- Mobile-first: la mayoría de las sesiones reales ocurren en celular.

## Accessibility & Inclusion

Sin nivel WCAG formal declarado. Debe funcionar bien en modo oscuro (tema por defecto histórico) y con nombres reales largos (ej. "Marta B. Villa Del Valle") sin romper el layout ni la legibilidad.

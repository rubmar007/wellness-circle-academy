-- Wellness Circle Academy — datos de ejemplo (programa Arranque, Día 1)
-- Ejecutar con:  psql "$DATABASE_URL" -f database/seed.sql
-- Idempotente: usa ON CONFLICT DO NOTHING para no duplicar.

BEGIN;

-- Programa de ejemplo: Arranque.
INSERT INTO programs (slug, title, description, display_order, is_published)
VALUES (
    'arranque',
    'Arranque',
    'Programa de bienvenida para iniciar tu camino en redes sociales y bienestar.',
    1,
    TRUE
)
ON CONFLICT (slug) DO NOTHING;

-- Lección de ejemplo: Día 1 del programa Arranque.
INSERT INTO lessons (
    program_id,
    day_number,
    title,
    objective,
    post_text,
    story_text,
    conversation_text,
    action_text,
    tip_text,
    checklist_items,
    is_published
)
SELECT
    p.id,
    1,
    'Día 1 — Presentación natural',
    'Presentarte de forma natural en redes.',
    E'Muchas veces creemos que el bienestar es solamente ejercicio o alimentación…\n\nPero también es energía, descanso, enfoque mental y sentirte bien contigo mismo.\n\nEstoy aprendiendo muchísimo sobre tecnologías de bienestar y biohacking natural y me emociona compartir este proceso.',
    'Algo grande está cambiando en mi vida.',
    E'Amiga, últimamente he estado aprendiendo muchísimo sobre bienestar celular y energía natural.\n\nY sinceramente me ha sorprendido muchísimo cómo pequeños cambios pueden ayudarte a sentirte mejor.',
    E'- Publicar el post\n- Subir 2 stories\n- Hablar con 3 personas',
    'Cuanto más natural sea tu publicación, más conexión genera. No fuerces el tono comercial.',
    '["Ya publiqué", "Ya subí stories", "Ya hablé con 3 personas", "Ya vi el entrenamiento"]'::jsonb,
    TRUE
FROM programs p
WHERE p.slug = 'arranque'
ON CONFLICT (program_id, day_number) DO NOTHING;

COMMIT;

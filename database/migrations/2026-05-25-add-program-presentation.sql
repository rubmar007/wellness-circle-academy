-- Wellness Circle Academy
-- Añade columna `presentation` (texto corto ≤ 200 chars) a programs.
-- Uso: aparece como tagline en la tarjeta del dashboard, en lugar de description.
-- La columna `description` se mantiene para la vista interna del programa.

BEGIN;

ALTER TABLE programs
    ADD COLUMN IF NOT EXISTS presentation VARCHAR(200);

COMMIT;

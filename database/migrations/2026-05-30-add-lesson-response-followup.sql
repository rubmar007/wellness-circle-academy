-- Añade dos campos de texto a las lecciones: Respuesta y Seguimiento.
-- En la UI aparecen después de "Conversación ejemplo" y antes de "Acción del día".
-- Postgres añade columnas al final de la tabla; el orden lógico lo da la UI,
-- no la posición física de la columna.

ALTER TABLE lessons ADD COLUMN IF NOT EXISTS response_text TEXT;
ALTER TABLE lessons ADD COLUMN IF NOT EXISTS followup_text TEXT;

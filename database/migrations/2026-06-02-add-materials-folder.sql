-- Agrupa materiales en carpetas visibles para los miembros.
-- Null = sin carpeta (comportamiento anterior intacto).
ALTER TABLE materials ADD COLUMN IF NOT EXISTS folder VARCHAR(100);

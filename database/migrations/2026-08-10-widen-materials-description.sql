-- Rub pidió ampliar el límite de la descripción de materiales de 120 a
-- 500 caracteres (ver migración 2026-08-10-add-materials-description.sql).
ALTER TABLE materials ALTER COLUMN description TYPE VARCHAR(500);

-- Rub pidió mostrar hasta 3 descripciones breves por material tipo Imagen
-- (antes solo había una) y ampliar el límite de 500 a 1000 caracteres cada
-- una. Aplicado directo en Neon vía MCP el 2026-08-12.
ALTER TABLE materials ALTER COLUMN description TYPE VARCHAR(1000);
ALTER TABLE materials ADD COLUMN description2 VARCHAR(1000);
ALTER TABLE materials ADD COLUMN description3 VARCHAR(1000);

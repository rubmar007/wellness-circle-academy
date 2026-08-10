-- Descripción breve (120 caracteres) que se muestra debajo de cada
-- material de tipo Imagen en /materiales, con un botón de copiar rápido
-- (reusa public/assets/js/copy.js, ya existente).
ALTER TABLE materials ADD COLUMN description VARCHAR(120) NULL;

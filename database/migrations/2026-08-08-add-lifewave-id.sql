-- Agrega el ID de distribuidor/cliente de LifeWave al usuario. Opcional,
-- se muestra en el panel admin entre Email y Rol.
ALTER TABLE users ADD COLUMN IF NOT EXISTS lifewave_id VARCHAR(40);

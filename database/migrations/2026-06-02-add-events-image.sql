-- Imagen opcional para eventos (flyer, banner, etc.)
ALTER TABLE events ADD COLUMN IF NOT EXISTS image_url VARCHAR(500);

-- Promociones: añadir medios a la tabla existente de anuncios
ALTER TABLE announcements
    ADD COLUMN IF NOT EXISTS image_path TEXT        NULL,
    ADD COLUMN IF NOT EXISTS video_url  TEXT        NULL,
    ADD COLUMN IF NOT EXISTS link_url   TEXT        NULL,
    ADD COLUMN IF NOT EXISTS link_label VARCHAR(100) NULL;

-- Noticias: tabla nueva con misma estructura que announcements
CREATE TABLE IF NOT EXISTS noticias (
    id           BIGSERIAL PRIMARY KEY,
    kind         VARCHAR(50)  NOT NULL DEFAULT 'noticia',
    title        VARCHAR(200) NOT NULL,
    body         TEXT         NULL,
    image_path   TEXT         NULL,
    video_url    TEXT         NULL,
    link_url     TEXT         NULL,
    link_label   VARCHAR(100) NULL,
    is_published BOOLEAN      NOT NULL DEFAULT FALSE,
    created_at   TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

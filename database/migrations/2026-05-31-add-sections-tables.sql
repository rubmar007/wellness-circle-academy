-- Req B — Tablas para la app por secciones.
-- Feed (posts), Entrenamiento (trainings), Materiales (materials),
-- Eventos (events), Notificaciones (announcements), Normas/textos (pages),
-- y foto de perfil del usuario (users.photo_url).
-- Reusa la función set_updated_at() del schema base.

BEGIN;

-- Feed de Inicio: publicaciones de miembros (texto + imagen opcional).
CREATE TABLE IF NOT EXISTS posts (
    id          BIGSERIAL PRIMARY KEY,
    user_id     BIGINT       NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    body        TEXT         NOT NULL,
    image_url   VARCHAR(500),
    is_hidden   BOOLEAN      NOT NULL DEFAULT FALSE,
    created_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS posts_created_idx ON posts (created_at DESC);

-- Entrenamiento: videos de YouTube/Vimeo agrupados por tema.
CREATE TABLE IF NOT EXISTS trainings (
    id            BIGSERIAL PRIMARY KEY,
    category      VARCHAR(40)  NOT NULL,   -- plan_compensacion, clientes, autoenvio, oficina_virtual, doctores
    title         VARCHAR(200) NOT NULL,
    video_url     VARCHAR(500) NOT NULL,
    display_order INT          NOT NULL DEFAULT 0,
    is_published  BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at    TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at    TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS trainings_cat_idx ON trainings (category, display_order);

-- Materiales: PDFs, Imágenes y Enlaces.
CREATE TABLE IF NOT EXISTS materials (
    id            BIGSERIAL PRIMARY KEY,
    type          VARCHAR(20)  NOT NULL,   -- pdf, image, link
    title         VARCHAR(200) NOT NULL,
    url           VARCHAR(500),            -- para pdf/link (Google Drive)
    image_url     VARCHAR(500),            -- para imágenes subidas al servidor
    display_order INT          NOT NULL DEFAULT 0,
    is_published  BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at    TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at    TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS materials_type_idx ON materials (type, display_order);

-- Eventos: calendario con tipo y enlace para entrar.
CREATE TABLE IF NOT EXISTS events (
    id            BIGSERIAL PRIMARY KEY,
    title         VARCHAR(200) NOT NULL,
    event_type    VARCHAR(30)  NOT NULL,   -- taller, entrenamiento, oportunidad
    starts_at     TIMESTAMPTZ  NOT NULL,
    join_url      VARCHAR(500),
    description   TEXT,
    is_published  BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at    TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at    TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS events_starts_idx ON events (starts_at);

-- Notificaciones: reconocimientos y avisos (solo admin publica).
CREATE TABLE IF NOT EXISTS announcements (
    id           BIGSERIAL PRIMARY KEY,
    kind         VARCHAR(20)  NOT NULL DEFAULT 'reconocimiento', -- reconocimiento, aviso
    title        VARCHAR(200) NOT NULL,
    body         TEXT,
    is_published BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at   TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS announcements_created_idx ON announcements (created_at DESC);

-- Páginas de texto estático (Normas y Reglamentos, y futuros textos).
CREATE TABLE IF NOT EXISTS pages (
    id         BIGSERIAL PRIMARY KEY,
    slug       VARCHAR(80)  NOT NULL UNIQUE,
    title      VARCHAR(160) NOT NULL,
    body       TEXT,
    updated_at TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

-- Foto de perfil del miembro.
ALTER TABLE users ADD COLUMN IF NOT EXISTS photo_url VARCHAR(500);

-- Triggers de updated_at para las tablas que lo tienen.
DROP TRIGGER IF EXISTS trainings_set_updated_at ON trainings;
DROP TRIGGER IF EXISTS materials_set_updated_at ON materials;
DROP TRIGGER IF EXISTS events_set_updated_at    ON events;
DROP TRIGGER IF EXISTS pages_set_updated_at     ON pages;

CREATE TRIGGER trainings_set_updated_at BEFORE UPDATE ON trainings
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE TRIGGER materials_set_updated_at BEFORE UPDATE ON materials
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE TRIGGER events_set_updated_at BEFORE UPDATE ON events
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();
CREATE TRIGGER pages_set_updated_at BEFORE UPDATE ON pages
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

-- Página de Normas inicial (vacía) para que la sección siempre exista.
INSERT INTO pages (slug, title, body)
VALUES ('normas', 'Normas y Reglamentos', NULL)
ON CONFLICT (slug) DO NOTHING;

COMMIT;

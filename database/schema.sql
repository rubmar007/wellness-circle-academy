-- Wellness Circle Academy — esquema inicial (PostgreSQL / Neon)
-- Ejecutar con:  psql "$DATABASE_URL" -f database/schema.sql

BEGIN;

-- ============================================================
-- Tabla: users
-- Almacena las cuentas del sistema. Roles: admin | member.
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id              BIGSERIAL PRIMARY KEY,
    name            VARCHAR(120) NOT NULL,
    email           VARCHAR(190) NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    role            VARCHAR(20)  NOT NULL DEFAULT 'member'
                    CHECK (role IN ('admin', 'member')),
    is_active       BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at      TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    CONSTRAINT users_email_unique UNIQUE (email)
);

CREATE INDEX IF NOT EXISTS users_role_idx ON users (role);

-- ============================================================
-- Tabla: programs
-- Cada programa agrupa una secuencia de lecciones (días).
-- ============================================================
CREATE TABLE IF NOT EXISTS programs (
    id              BIGSERIAL PRIMARY KEY,
    slug            VARCHAR(80)  NOT NULL,
    title           VARCHAR(160) NOT NULL,
    presentation    VARCHAR(200),
    description     TEXT,
    cover_image     VARCHAR(500),
    display_order   INTEGER      NOT NULL DEFAULT 0,
    is_published    BOOLEAN      NOT NULL DEFAULT FALSE,
    created_at      TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    CONSTRAINT programs_slug_unique UNIQUE (slug),
    CONSTRAINT programs_slug_format CHECK (slug ~ '^[a-z0-9-]+$')
);

CREATE INDEX IF NOT EXISTS programs_published_idx ON programs (is_published, display_order);

-- ============================================================
-- Tabla: lessons
-- Una lección por día dentro de un programa.
-- checklist_items: array JSON de strings con los ítems del día.
-- ============================================================
CREATE TABLE IF NOT EXISTS lessons (
    id                  BIGSERIAL PRIMARY KEY,
    program_id          BIGINT       NOT NULL REFERENCES programs(id) ON DELETE CASCADE,
    day_number          INTEGER      NOT NULL CHECK (day_number > 0),
    title               VARCHAR(200) NOT NULL,
    objective           TEXT,
    post_text           TEXT,
    story_text          TEXT,
    conversation_text   TEXT,
    action_text         TEXT,
    tip_text            TEXT,
    image_url           VARCHAR(500),
    checklist_items     JSONB        NOT NULL DEFAULT '[]'::jsonb,
    is_published        BOOLEAN      NOT NULL DEFAULT FALSE,
    created_at          TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    CONSTRAINT lessons_program_day_unique UNIQUE (program_id, day_number)
);

CREATE INDEX IF NOT EXISTS lessons_program_idx ON lessons (program_id, day_number);

-- ============================================================
-- Tabla: user_progress
-- Marca qué ítems del checklist ha completado cada usuario.
-- item_index: índice base 0 dentro de lessons.checklist_items.
-- ============================================================
CREATE TABLE IF NOT EXISTS user_progress (
    user_id         BIGINT      NOT NULL REFERENCES users(id)   ON DELETE CASCADE,
    lesson_id       BIGINT      NOT NULL REFERENCES lessons(id) ON DELETE CASCADE,
    item_index      INTEGER     NOT NULL CHECK (item_index >= 0),
    completed_at    TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    PRIMARY KEY (user_id, lesson_id, item_index)
);

CREATE INDEX IF NOT EXISTS user_progress_user_idx ON user_progress (user_id);

-- ============================================================
-- Tabla: login_attempts
-- Rate limiting de login. Se purga periódicamente.
-- ============================================================
CREATE TABLE IF NOT EXISTS login_attempts (
    id              BIGSERIAL PRIMARY KEY,
    email           VARCHAR(190) NOT NULL,
    ip_address      VARCHAR(45)  NOT NULL,
    succeeded       BOOLEAN      NOT NULL DEFAULT FALSE,
    attempted_at    TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS login_attempts_email_time_idx
    ON login_attempts (email, attempted_at DESC);

CREATE INDEX IF NOT EXISTS login_attempts_ip_time_idx
    ON login_attempts (ip_address, attempted_at DESC);

-- ============================================================
-- Trigger genérico para mantener updated_at fresco.
-- ============================================================
CREATE OR REPLACE FUNCTION set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS users_set_updated_at    ON users;
DROP TRIGGER IF EXISTS programs_set_updated_at ON programs;
DROP TRIGGER IF EXISTS lessons_set_updated_at  ON lessons;

CREATE TRIGGER users_set_updated_at
    BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER programs_set_updated_at
    BEFORE UPDATE ON programs
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TRIGGER lessons_set_updated_at
    BEFORE UPDATE ON lessons
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

COMMIT;

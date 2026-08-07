-- WCA Experience Kit (Feature 2 del plan de Marta): kit asignado a un
-- cliente + bitácora diaria de los 7 días (parche, hidratación, movimiento,
-- diario/encuesta). Reusa set_updated_at() ya definida en schema.sql.

BEGIN;

CREATE TABLE IF NOT EXISTS client_kits (
    id            BIGSERIAL PRIMARY KEY,
    user_id       BIGINT       NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    kit_slug      VARCHAR(40)  NOT NULL
                  CHECK (kit_slug IN (
                      'performance', 'menopause', 'menopause-premium', 'sleep',
                      'heart-wellness', 'balance', 'senior'
                  )),
    weight_kg     NUMERIC(5,2),
    started_at    DATE         NOT NULL DEFAULT CURRENT_DATE,
    is_active     BOOLEAN      NOT NULL DEFAULT TRUE,
    created_at    TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at    TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS client_kits_user_idx ON client_kits (user_id, is_active);

-- Un solo kit activo por cliente a la vez.
CREATE UNIQUE INDEX IF NOT EXISTS client_kits_one_active_idx
    ON client_kits (user_id) WHERE is_active;

CREATE TABLE IF NOT EXISTS client_kit_logs (
    id                  BIGSERIAL PRIMARY KEY,
    client_kit_id       BIGINT       NOT NULL REFERENCES client_kits(id) ON DELETE CASCADE,
    day_number          INTEGER      NOT NULL CHECK (day_number BETWEEN 1 AND 7),
    log_date            DATE         NOT NULL,
    patch_applied       BOOLEAN      NOT NULL DEFAULT FALSE,
    water_count         INTEGER      NOT NULL DEFAULT 0,
    water_last_at       TIMESTAMPTZ,
    electrolytes_taken  BOOLEAN      NOT NULL DEFAULT FALSE,
    steps               INTEGER,
    exercise_done       BOOLEAN      NOT NULL DEFAULT FALSE,
    exercise_type       VARCHAR(20)  CHECK (exercise_type IN ('cardio', 'fuerza', 'ambos') OR exercise_type IS NULL),
    diary               JSONB,
    notes                TEXT,
    created_at          TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at          TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    CONSTRAINT client_kit_logs_unique UNIQUE (client_kit_id, day_number)
);

CREATE INDEX IF NOT EXISTS client_kit_logs_kit_idx ON client_kit_logs (client_kit_id, day_number);

DROP TRIGGER IF EXISTS client_kits_set_updated_at ON client_kits;
CREATE TRIGGER client_kits_set_updated_at
    BEFORE UPDATE ON client_kits
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

DROP TRIGGER IF EXISTS client_kit_logs_set_updated_at ON client_kit_logs;
CREATE TRIGGER client_kit_logs_set_updated_at
    BEFORE UPDATE ON client_kit_logs
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

COMMIT;

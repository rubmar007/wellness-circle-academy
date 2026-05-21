-- Migración: tabla password_resets
-- Aplicar con:  psql "$DATABASE_URL" -f database/migrations/2026-05-21-add-password-resets.sql

BEGIN;

CREATE TABLE IF NOT EXISTS password_resets (
    id              BIGSERIAL PRIMARY KEY,
    user_id         BIGINT       NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    token_hash      CHAR(64)     NOT NULL,                   -- SHA-256 hex
    expires_at      TIMESTAMPTZ  NOT NULL,
    used_at         TIMESTAMPTZ,
    ip_address      VARCHAR(45),
    requested_at    TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    CONSTRAINT password_resets_token_hash_unique UNIQUE (token_hash)
);

CREATE INDEX IF NOT EXISTS password_resets_user_recent_idx
    ON password_resets (user_id, requested_at DESC);

CREATE INDEX IF NOT EXISTS password_resets_expires_idx
    ON password_resets (expires_at);

COMMIT;

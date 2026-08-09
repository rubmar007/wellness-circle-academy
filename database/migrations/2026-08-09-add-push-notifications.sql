-- Módulo de Push Notifications: suscripciones del navegador (una por
-- dispositivo/navegador instalado) y notificaciones programadas desde admin.
--
-- audience_type define a quién se dirige la notificación:
--   'all'  -> todos los usuarios activos (no usa las columnas audience_*)
--   'role' -> audience_role (admin | member | cliente)
--   'kit'  -> audience_kit_slug (usuarios con ese kit activo en client_kits)
--   'user' -> audience_user_id (una persona específica)
--
-- scheduled_at se interpreta en UTC (igual que el resto del proyecto, que no
-- tiene manejo de zona horaria por usuario — el servidor corre en UTC).
--
-- Recurrencia: is_recurring + recurrence_freq. El cron (bin/send-scheduled-
-- notifications.php) recalcula scheduled_at sumando 1 día o 7 días después
-- de cada envío exitoso, en vez de crear una fila nueva por ocurrencia.

CREATE TABLE push_subscriptions (
    id          BIGSERIAL PRIMARY KEY,
    user_id     BIGINT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    endpoint    TEXT NOT NULL UNIQUE,
    p256dh_key  TEXT NOT NULL,
    auth_key    TEXT NOT NULL,
    user_agent  VARCHAR(255) NULL,
    created_at  TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at  TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_push_subscriptions_user_id ON push_subscriptions(user_id);

CREATE TABLE push_notifications (
    id                  BIGSERIAL PRIMARY KEY,
    title               VARCHAR(150) NOT NULL,
    body                TEXT NOT NULL,
    url                 VARCHAR(255) NULL,

    audience_type       VARCHAR(10) NOT NULL
        CHECK (audience_type IN ('all', 'role', 'kit', 'user')),
    audience_role       VARCHAR(20) NULL
        CHECK (audience_role IN ('admin', 'member', 'cliente')),
    audience_kit_slug   VARCHAR(30) NULL,
    audience_user_id    BIGINT NULL REFERENCES users(id) ON DELETE CASCADE,

    scheduled_at        TIMESTAMPTZ NOT NULL,
    is_recurring        BOOLEAN NOT NULL DEFAULT FALSE,
    recurrence_freq     VARCHAR(10) NULL
        CHECK (recurrence_freq IN ('daily', 'weekly')),

    status              VARCHAR(10) NOT NULL DEFAULT 'pending'
        CHECK (status IN ('pending', 'sent', 'cancelled')),
    last_sent_at         TIMESTAMPTZ NULL,
    last_sent_count      INT NULL,
    last_failed_count    INT NULL,

    created_by          BIGINT NULL REFERENCES users(id) ON DELETE SET NULL,
    created_at          TIMESTAMPTZ NOT NULL DEFAULT now(),
    updated_at          TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_push_notifications_pending_due
    ON push_notifications(status, scheduled_at)
    WHERE status = 'pending';

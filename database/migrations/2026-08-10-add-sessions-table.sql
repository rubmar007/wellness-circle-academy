-- Sesiones de PHP guardadas en la base en vez de archivos locales del
-- contenedor de Railway (disco efímero: cada deploy/reinicio las borraba y
-- desconectaba a todo mundo). Ver src/Session/DatabaseSessionHandler.php.

CREATE TABLE sessions (
    id            VARCHAR(128) PRIMARY KEY,
    data          TEXT NOT NULL DEFAULT '',
    last_activity TIMESTAMPTZ NOT NULL DEFAULT now()
);

CREATE INDEX idx_sessions_last_activity ON sessions(last_activity);

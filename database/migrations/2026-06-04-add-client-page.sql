CREATE TABLE client_page (
    id                        SMALLINT PRIMARY KEY DEFAULT 1,
    welcome_image_url         VARCHAR(500),
    uso_texto                 TEXT,
    activar_texto             TEXT,
    activar_video_url         VARCHAR(500),
    desactivar_texto          TEXT,
    desactivar_video_url      VARCHAR(500),
    preferente_texto          TEXT,
    preferente_video_url      VARCHAR(500),
    beneficios_autoenvio_url  VARCHAR(500),
    beneficios_preferente_url VARCHAR(500),
    texto_libre               TEXT,
    updated_at                TIMESTAMPTZ DEFAULT NOW()
);

INSERT INTO client_page (id) VALUES (1);

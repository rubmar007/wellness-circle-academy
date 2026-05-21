-- Migración: añadir video_url y download_url a lessons
-- Aplicar con:  psql "$DATABASE_URL" -f database/migrations/2026-05-21-add-lesson-video-and-download.sql

BEGIN;

ALTER TABLE lessons
    ADD COLUMN IF NOT EXISTS video_url    VARCHAR(500),
    ADD COLUMN IF NOT EXISTS download_url VARCHAR(500);

COMMIT;

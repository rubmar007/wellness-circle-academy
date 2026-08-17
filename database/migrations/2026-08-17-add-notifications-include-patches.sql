-- Rub pidió que los recordatorios push (ej. "¿Ya te pusiste tu parche?")
-- incluyan el nombre del parche o parches que le corresponden ese día a
-- cada destinatario según su kit activo y en qué día de 7 va (calendario de
-- ExperienceKitData). Como cada usuario puede ir en un día distinto del
-- mismo kit, el texto no puede ser estático en push_notifications.body: se
-- calcula por destinatario al momento del envío (PushService::send) cuando
-- include_patches = TRUE. Aplicado directo en Neon vía MCP el 2026-08-17.
ALTER TABLE push_notifications ADD COLUMN include_patches BOOLEAN NOT NULL DEFAULT FALSE;

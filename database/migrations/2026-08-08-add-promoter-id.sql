-- WCA Experience Kit — Promotor Responsable (docs/SCOPE OF WORK.md).
-- promoter_id: member responsable de dar seguimiento a este Experience.
-- Nullable a propósito — "Sin asignar" es un estado válido, y los
-- registros existentes antes de esta migración quedan con NULL sin
-- romper nada (RULE-12 / AC-17).
ALTER TABLE client_kits ADD COLUMN IF NOT EXISTS promoter_id BIGINT REFERENCES users(id) ON DELETE SET NULL;

CREATE INDEX IF NOT EXISTS client_kits_promoter_idx ON client_kits (promoter_id);
CREATE INDEX IF NOT EXISTS client_kits_promoter_active_idx ON client_kits (promoter_id, is_active);

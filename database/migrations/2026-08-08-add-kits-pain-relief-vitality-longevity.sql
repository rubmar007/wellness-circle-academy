-- Agrega los 3 kits que faltaban del calendario (docs/agregado2.docx):
-- Pain Relief, Vitality, Longevity. Antes solo existían como nombre sin
-- calendario de 7 días, por eso no se habían dado de alta (ver nota en
-- src/Support/ExperienceKitData.php).
ALTER TABLE client_kits DROP CONSTRAINT client_kits_kit_slug_check;
ALTER TABLE client_kits ADD CONSTRAINT client_kits_kit_slug_check
    CHECK (kit_slug IN (
        'performance', 'menopause', 'menopause-premium', 'sleep',
        'heart-wellness', 'balance', 'senior',
        'pain-relief', 'vitality', 'longevity'
    ));

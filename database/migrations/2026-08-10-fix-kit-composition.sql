-- Corrige la composición de los Experience Kits contra la tabla oficial
-- "WCA Experience Kits – 7 días". Menopause Premium se separa en dos kits
-- (Internacional / México). El slug viejo 'menopause-premium' se deja
-- permitido en el CHECK (no se borra) porque ya existe 1 cliente asignado
-- con ese slug (client_kits.id=15, Marta) — se reasigna manualmente desde
-- el admin a uno de los dos kits nuevos, ya no aparece en el selector
-- porque src/Support/ExperienceKitData.php ya no lo incluye en calendar().
ALTER TABLE client_kits DROP CONSTRAINT client_kits_kit_slug_check;
ALTER TABLE client_kits ADD CONSTRAINT client_kits_kit_slug_check
    CHECK (kit_slug IN (
        'performance', 'menopause', 'menopause-premium', 'sleep',
        'heart-wellness', 'balance', 'senior',
        'pain-relief', 'vitality', 'longevity',
        'menopause-premium-intl', 'menopause-premium-mx'
    ));

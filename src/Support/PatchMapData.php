<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Catálogo estático del Mapa de colocación de parches.
 *
 * Contenido verificado contra docs/PLAN_A_1act (sección 3 y 5.2, horarios y
 * catálogo real de parches WCA) y docs/MAPA-TACTICO-CORPORAL-REPLICAR.md
 * (puntos de acupuntura reutilizables: X39, SP6, Aeon, Carnosine, Glutation,
 * Silent Nights y Alavida comparten protocolo con el mapa de referencia).
 *
 * X49 no tiene coordenadas oficiales verificadas en ninguna fuente entregada
 * — se muestra en el selector sin puntos, con nota explícita en vez de
 * inventar una ubicación.
 *
 * Coordenadas x/y en porcentaje (0-100) sobre el contenedor de la figura,
 * calibradas visualmente contra front.jpg / back.jpg (ver docs/crawl.md para
 * contexto del proyecto). "der"/"izq" en las descripciones es el lado
 * anatómico del cliente (convención clínica): en la vista frontal el lado
 * derecho del cliente cae a la IZQUIERDA de la imagen; en la vista trasera,
 * a la DERECHA de la imagen.
 */
final class PatchMapData
{
    /**
     * @return array<int, array{
     *   id: string, name: string, subtitle: string, desc: string,
     *   schedule: string, color: string,
     *   points: array<int, array{label: string, desc: string, view: string, x: float, y: float}>
     * }>
     */
    public static function patches(): array
    {
        return [
            [
                'id' => 'x39',
                'name' => 'X39',
                'subtitle' => 'Reparación celular',
                'desc' => 'Estimula el péptido GHK-Cu. Usa 1 parche diario donde haya dolor o en los puntos generales de bienestar.',
                'schedule' => 'Día, 12h puesto / 12h descanso, todos los días (uso nocturno solo durante fase de detox). Aplicar sobre piel limpia y seca. Mantener buena hidratación.',
                'color' => '#22d3ee',
                'points' => [
                    ['label' => 'GV 14', 'desc' => 'Base del cuello', 'view' => 'back', 'x' => 50, 'y' => 15],
                    ['label' => 'CV 6', 'desc' => 'Dos dedos bajo el ombligo', 'view' => 'front', 'x' => 50, 'y' => 43],
                ],
            ],
            [
                'id' => 'x49',
                'name' => 'X49',
                'subtitle' => 'Performance',
                'desc' => 'Parche de rendimiento físico. Puntos oficiales de aplicación pendientes de confirmar con la guía LifeWave — consulta el inserto del producto mientras tanto.',
                'schedule' => 'Día, 12h puesto / 12h descanso, cada día o según necesidad, uso diurno.',
                'color' => '#fb923c',
                'points' => [],
            ],
            [
                'id' => 'sp6',
                'name' => 'SP6',
                'subtitle' => 'Antojos y hormonas',
                'desc' => 'Control de apetito y regulación metabólica. Optimiza la digestión. Balance hormonal, control de antojos.',
                'schedule' => 'Día, 12h puesto / 12h descanso, cada día o según necesidad, uso diurno.',
                'color' => '#34d399',
                'points' => [
                    ['label' => 'SP 6 (izq)', 'desc' => '4 dedos sobre el hueso interno del tobillo', 'view' => 'front', 'x' => 55, 'y' => 83],
                    ['label' => 'ST 36 (izq)', 'desc' => 'Bajo la rodilla, por fuera', 'view' => 'front', 'x' => 61, 'y' => 72],
                    ['label' => 'KD 3 (izq)', 'desc' => 'Tobillo interno, junto al tendón', 'view' => 'front', 'x' => 54, 'y' => 86],
                    ['label' => 'CV 6', 'desc' => 'Dos dedos bajo el ombligo', 'view' => 'front', 'x' => 50, 'y' => 43],
                ],
            ],
            [
                'id' => 'aeon',
                'name' => 'Aeon',
                'subtitle' => 'Estrés e inflamación',
                'desc' => 'Equilibra el sistema nervioso autónomo y reduce la inflamación celular causante del envejecimiento.',
                'schedule' => '12h si es uso diario (día o noche); 24h corridas si es día por medio o cada tercer día.',
                'color' => '#60a5fa',
                'points' => [
                    ['label' => 'GV 14', 'desc' => 'Base del cuello', 'view' => 'back', 'x' => 50, 'y' => 15],
                    ['label' => 'CV 6', 'desc' => 'Bajo el ombligo', 'view' => 'front', 'x' => 50, 'y' => 43],
                    ['label' => 'LU 9 (der)', 'desc' => 'Muñeca interna', 'view' => 'front', 'x' => 32, 'y' => 52],
                    ['label' => 'SP 6 (der)', 'desc' => 'Tobillo interno', 'view' => 'front', 'x' => 47, 'y' => 84],
                    ['label' => 'LV 3 (der)', 'desc' => 'Empeine del pie', 'view' => 'front', 'x' => 44, 'y' => 88],
                ],
            ],
            [
                'id' => 'carnosine',
                'name' => 'Carnosine',
                'subtitle' => 'Cerebro y circulación',
                'desc' => 'Reparación muscular, mejora cognitiva y longevidad celular.',
                'schedule' => '12h si es uso diario (día o noche); 24h corridas si es día por medio o cada tercer día.',
                'color' => '#818cf8',
                'points' => [
                    ['label' => 'GV 14', 'desc' => 'Base del cuello', 'view' => 'back', 'x' => 50, 'y' => 15],
                    ['label' => 'GV 2', 'desc' => 'Línea media, zona lumbar', 'view' => 'back', 'x' => 50, 'y' => 36],
                    ['label' => 'CV 17', 'desc' => 'Centro del pecho', 'view' => 'front', 'x' => 50, 'y' => 29],
                    ['label' => 'LI 4 (der)', 'desc' => 'Dorso de la mano', 'view' => 'back', 'x' => 68, 'y' => 53],
                    ['label' => 'HT 7 (der)', 'desc' => 'Muñeca, lado del meñique', 'view' => 'front', 'x' => 32, 'y' => 52],
                ],
            ],
            [
                'id' => 'glutation',
                'name' => 'Glutation',
                'subtitle' => 'Antioxidante maestro',
                'desc' => 'Desintoxicación profunda de metales pesados y apoyo al sistema inmunológico.',
                'schedule' => '12h si es uso diario (día o noche); 24h corridas si es día por medio o cada tercer día.',
                'color' => '#2dd4bf',
                'points' => [
                    ['label' => 'CV 22', 'desc' => 'Base de la garganta', 'view' => 'front', 'x' => 50, 'y' => 21],
                    ['label' => 'CV 6', 'desc' => 'Dos dedos bajo el ombligo', 'view' => 'front', 'x' => 50, 'y' => 43],
                    ['label' => 'LU 9 (der)', 'desc' => 'Muñeca interna', 'view' => 'front', 'x' => 32, 'y' => 52],
                    ['label' => 'SP 6 (der)', 'desc' => 'Tobillo interno', 'view' => 'front', 'x' => 47, 'y' => 84],
                    ['label' => 'LV 3 (der)', 'desc' => 'Empeine del pie', 'view' => 'front', 'x' => 44, 'y' => 88],
                ],
            ],
            [
                'id' => 'silentnights',
                'name' => 'Silent Nights',
                'subtitle' => 'Sueño reparador',
                'desc' => 'Regula la producción natural de melatonina y mejora la calidad del sueño profundo.',
                'schedule' => 'Colocar 30 min antes de dormir, retirar al despertar. Uso nocturno.',
                'color' => '#a78bfa',
                'points' => [
                    ['label' => 'TB 23 (der)', 'desc' => 'Sien, junto a la ceja externa', 'view' => 'front', 'x' => 45, 'y' => 13],
                    ['label' => 'GB 14', 'desc' => 'Un dedo sobre la ceja', 'view' => 'front', 'x' => 50, 'y' => 13],
                    ['label' => 'TB 17 (der)', 'desc' => 'Detrás del lóbulo de la oreja', 'view' => 'back', 'x' => 57, 'y' => 11],
                    ['label' => 'ST 36 (der)', 'desc' => 'Bajo la rodilla, por fuera', 'view' => 'front', 'x' => 40, 'y' => 72],
                    ['label' => 'LV 3 (der)', 'desc' => 'Empeine del pie', 'view' => 'front', 'x' => 44, 'y' => 88],
                ],
            ],
            [
                'id' => 'alavida',
                'name' => 'Alavida',
                'subtitle' => 'Regeneración de piel',
                'desc' => 'Reduce el estrés oxidativo, estimula la producción de colágeno y renueva la piel.',
                'schedule' => 'Noche, 12h puesto / 12h descanso. Uso nocturno.',
                'color' => '#e879f9',
                'points' => [
                    ['label' => 'TB 23 (der)', 'desc' => 'Sien, junto a la ceja', 'view' => 'front', 'x' => 45, 'y' => 13],
                    ['label' => 'GV 24.5', 'desc' => 'Tercer ojo (entrecejo)', 'view' => 'front', 'x' => 50, 'y' => 14],
                    ['label' => 'GB 14', 'desc' => 'Un dedo sobre la ceja', 'view' => 'front', 'x' => 50, 'y' => 13],
                    ['label' => 'GV 14', 'desc' => 'Base del cuello', 'view' => 'back', 'x' => 50, 'y' => 15],
                ],
            ],
        ];
    }

    /**
     * Filtro por kit: qué parches corresponden a cada kit, según el
     * calendario de 7 días de docs/PLAN_A_1act sección 5.1.
     *
     * @return array<string, array{label: string, patches: array<int, string>}>
     */
    public static function kits(): array
    {
        return [
            'performance' => ['label' => 'Performance', 'patches' => ['x39', 'x49']],
            'menopause' => ['label' => 'Menopause', 'patches' => ['x39']],
            'menopause-premium' => ['label' => 'Menopause Premium', 'patches' => ['x39', 'alavida', 'sp6']],
            'sleep' => ['label' => 'Sleep', 'patches' => ['silentnights']],
            'heart-wellness' => ['label' => 'Heart & Wellness', 'patches' => ['x39']],
            'balance' => ['label' => 'Balance', 'patches' => ['x39', 'aeon']],
            'senior' => ['label' => 'Senior', 'patches' => ['x39']],
        ];
    }
}

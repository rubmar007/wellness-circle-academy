<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Catálogo estático del Mapa de colocación de parches.
 *
 * Contenido verificado contra docs/PLAN_A_1act (sección 3 y 5.2, horarios y
 * catálogo real de parches WCA), docs/MAPA-TACTICO-CORPORAL-REPLICAR.md
 * (puntos de acupuntura reutilizables: X39, SP6, Aeon, Carnosine, Glutation,
 * Silent Nights y Alavida comparten protocolo con el mapa de referencia) y
 * la guía oficial de LifeWave (academiawca — captura de
 * lifewave.com/.../guia-de-uso-y-colocacion-de-los-parches) para X49: un
 * punto en el abdomen bajo (frente) y uno en la base del cuello (espalda),
 * las mismas coordenadas que ya usa X39.
 *
 * Coordenadas x/y en porcentaje (0-100) sobre el contenedor de la figura,
 * recalculadas a partir del recorte real de front.jpg / back.jpg (ver
 * scripts de recorte en el historial — cuerpo calibrado a la misma altura
 * en píxeles en ambas imágenes para que no "salte" de tamaño al cambiar de
 * vista). "der"/"izq" en las descripciones es el lado anatómico del
 * cliente (convención clínica): en la vista frontal el lado derecho del
 * cliente cae a la IZQUIERDA de la imagen; en la vista trasera, a la
 * DERECHA de la imagen.
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
                    ['label' => 'GV 14', 'desc' => 'Base del cuello', 'view' => 'back', 'x' => 50.0, 'y' => 16.3],
                    ['label' => 'CV 6', 'desc' => 'Dos dedos bajo el ombligo', 'view' => 'front', 'x' => 48.0, 'y' => 43.5],
                ],
            ],
            [
                'id' => 'x49',
                'name' => 'X49',
                'subtitle' => 'Performance',
                'desc' => 'Brinda una sensación de enfoque, fortaleza y resistencia. Apoya la práctica de ejercicio y la recuperación después del esfuerzo.',
                'schedule' => 'Día, 12h puesto / 12h descanso, cada día o según necesidad, uso diurno.',
                'color' => '#fb923c',
                'points' => [
                    ['label' => 'CV 6', 'desc' => 'Abdomen bajo, dos dedos bajo el ombligo', 'view' => 'front', 'x' => 48.0, 'y' => 43.5],
                    ['label' => 'GV 14', 'desc' => 'Base del cuello', 'view' => 'back', 'x' => 50.0, 'y' => 16.3],
                ],
            ],
            [
                'id' => 'sp6',
                'name' => 'SP6',
                'subtitle' => 'Antojos y hormonas',
                'desc' => 'Control de apetito y regulación metabólica. Optimiza la digestión. Balance hormonal, control de antojos.',
                'schedule' => 'Día, 12h puesto / 12h descanso, cada día o según necesidad, uso diurno.',
                'color' => '#34d399',
                'points' => [
                    ['label' => 'SP 6 (izq)', 'desc' => '4 dedos sobre el hueso interno del tobillo', 'view' => 'front', 'x' => 56.0, 'y' => 90.5],
                    ['label' => 'ST 36 (izq)', 'desc' => 'Bajo la rodilla, por fuera', 'view' => 'front', 'x' => 63.2, 'y' => 77.3],
                    ['label' => 'KD 3 (izq)', 'desc' => 'Tobillo interno, junto al tendón', 'view' => 'front', 'x' => 54.8, 'y' => 94.1],
                    ['label' => 'CV 6', 'desc' => 'Dos dedos bajo el ombligo', 'view' => 'front', 'x' => 48.0, 'y' => 43.5],
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
                    ['label' => 'GV 14', 'desc' => 'Base del cuello', 'view' => 'back', 'x' => 50.0, 'y' => 16.3],
                    ['label' => 'CV 6', 'desc' => 'Bajo el ombligo', 'view' => 'front', 'x' => 48.0, 'y' => 43.5],
                    ['label' => 'LU 9 (der)', 'desc' => 'Muñeca interna', 'view' => 'front', 'x' => 28.4, 'y' => 53.3],
                    ['label' => 'SP 6 (der)', 'desc' => 'Tobillo interno', 'view' => 'front', 'x' => 46.4, 'y' => 91.7],
                    ['label' => 'LV 3 (der)', 'desc' => 'Empeine del pie', 'view' => 'front', 'x' => 42.8, 'y' => 96.5],
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
                    ['label' => 'GV 14', 'desc' => 'Base del cuello', 'view' => 'back', 'x' => 50.0, 'y' => 16.3],
                    ['label' => 'GV 2', 'desc' => 'Línea media, zona lumbar', 'view' => 'back', 'x' => 50.0, 'y' => 39.2],
                    ['label' => 'CV 17', 'desc' => 'Centro del pecho', 'view' => 'front', 'x' => 50.0, 'y' => 25.7],
                    ['label' => 'LI 4 (der)', 'desc' => 'Dorso de la mano', 'view' => 'back', 'x' => 73.1, 'y' => 57.7],
                    ['label' => 'HT 7 (der)', 'desc' => 'Muñeca, lado del meñique', 'view' => 'front', 'x' => 28.4, 'y' => 53.3],
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
                    ['label' => 'CV 22', 'desc' => 'Base de la garganta', 'view' => 'front', 'x' => 50.0, 'y' => 16.1],
                    ['label' => 'CV 6', 'desc' => 'Dos dedos bajo el ombligo', 'view' => 'front', 'x' => 48.0, 'y' => 43.5],
                    ['label' => 'LU 9 (der)', 'desc' => 'Muñeca interna', 'view' => 'front', 'x' => 28.4, 'y' => 53.3],
                    ['label' => 'SP 6 (der)', 'desc' => 'Tobillo interno', 'view' => 'front', 'x' => 46.4, 'y' => 91.7],
                    ['label' => 'LV 3 (der)', 'desc' => 'Empeine del pie', 'view' => 'front', 'x' => 42.8, 'y' => 96.5],
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
                    ['label' => 'TB 23 (der)', 'desc' => 'Sien, junto a la ceja externa', 'view' => 'front', 'x' => 44.0, 'y' => 6.5],
                    ['label' => 'GB 14', 'desc' => 'Un dedo sobre la ceja', 'view' => 'front', 'x' => 50.0, 'y' => 6.5],
                    ['label' => 'TB 17 (der)', 'desc' => 'Detrás del lóbulo de la oreja', 'view' => 'back', 'x' => 59.0, 'y' => 12.0],
                    ['label' => 'ST 36 (der)', 'desc' => 'Bajo la rodilla, por fuera', 'view' => 'front', 'x' => 38.0, 'y' => 77.3],
                    ['label' => 'LV 3 (der)', 'desc' => 'Empeine del pie', 'view' => 'front', 'x' => 42.8, 'y' => 96.5],
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
                    ['label' => 'TB 23 (der)', 'desc' => 'Sien, junto a la ceja', 'view' => 'front', 'x' => 44.0, 'y' => 6.5],
                    ['label' => 'GV 24.5', 'desc' => 'Tercer ojo (entrecejo)', 'view' => 'front', 'x' => 50.0, 'y' => 7.7],
                    ['label' => 'GB 14', 'desc' => 'Un dedo sobre la ceja', 'view' => 'front', 'x' => 50.0, 'y' => 6.5],
                    ['label' => 'GV 14', 'desc' => 'Base del cuello', 'view' => 'back', 'x' => 50.0, 'y' => 16.3],
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

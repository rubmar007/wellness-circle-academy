# Mapa Táctico Corporal (U90XBodyMap) — Guía de replicación

Documento autocontenido para replicar el **Mapa Táctico Corporal** de U90X en otro proyecto React + Tailwind. Incluye código completo (drop-in), dependencias, assets, CSS requerido y cómo adaptarlo.

---

## 1. Qué es

Componente de pantalla completa (overlay) que muestra una figura humana (frente / espalda) sobre la que se marcan **puntos de aplicación de parches** (fototerapia LifeWave X39, SP6, Aeon, etc.). Tiene:

- Selector de parche (sidebar en desktop, rail de iconos en móvil).
- Toggle **Frente / Atrás**.
- Puntos animados (pulse) posicionados en % sobre la imagen, con tooltip al hover.
- Tarjeta de información con descripción del parche y lista de coordenadas.
- Soporte de **idioma** (`en` / `es`) y **tema** (`dark` / `light`).
- Caso especial "Energy Enhancer" (parches duales: blanco a la derecha, bronceado a la izquierda).

---

## 2. Dependencias

| Dependencia | Versión usada | Nota |
|---|---|---|
| `react` | ^18.3.1 | Hooks (`useState`), `React.Fragment` |
| `lucide-react` | ^0.462.0 | Íconos |
| `tailwindcss` | ^3.4.17 | Todo el estilo son clases utility |

No usa `clsx`, `tailwind-merge`, framer-motion ni librerías de UI. Solo React + lucide-react + Tailwind.

```bash
npm install react lucide-react
# tailwindcss ya debe estar configurado en el proyecto destino
```

> Íconos usados de lucide-react: `Zap, Brain, Droplets, ShieldCheck, Activity, Moon, Sun, Flame, ChevronRight, Target, ArrowLeft`. (En el original también se importan `Menu, X` pero **no se usan** — puedes quitarlos.)

---

## 3. Assets (imágenes de la figura)

El componente importa dos PNG:

```
src/assets/bodymap/body-front.png
src/assets/bodymap/body-back.png
```

Especificaciones de los originales:

- **Dimensiones:** 1248 × 1872 px (relación **2:3**).
- **Formato:** PNG **RGBA con fondo transparente** (figura recortada).
- El contenedor las muestra con `aspect-[2/3]` y `object-contain`.

> **CRÍTICO:** las coordenadas de los puntos (`x`, `y` en `PATCHES_DB`) están **calibradas a estas figuras específicas** mediante un mapeo (ver sección 6). Si cambias las imágenes por otras con distinta anatomía/encuadre, tendrás que recalibrar. Si reutilizas exactamente las mismas imágenes, funciona tal cual.

---

## 4. CSS requerido (utilidades custom)

El componente usa dos clases que **no vienen en Tailwind por defecto**. Agrégalas a tu CSS global (dentro de `@layer utilities` si usas Tailwind):

```css
@layer utilities {
  /* Respeta el notch en móviles (header del overlay) */
  .safe-area-top {
    padding-top: env(safe-area-inset-top, 0px);
  }

  /* Oculta la barra de scroll (sidebar y rail de iconos) */
  .scrollbar-hide {
    -ms-overflow-style: none;      /* IE/Edge */
    scrollbar-width: none;          /* Firefox */
  }
  .scrollbar-hide::-webkit-scrollbar {
    display: none;                  /* Chrome/Safari */
  }
}
```

> En el proyecto original `scrollbar-hide` no estaba definida (la clase no hacía nada, inofensivo). Con el bloque de arriba sí oculta el scrollbar. `safe-area-top` sí estaba definida.

### Nota sobre Tailwind + clases dinámicas

Los colores por parche (`text-cyan-400`, `bg-cyan-900/20 border-cyan-500`, etc.) viven como **strings literales completos** dentro de `PATCHES_DB`, así que el JIT de Tailwind los detecta al escanear el archivo. Requisito: que la ruta del componente esté incluida en `content` de tu `tailwind.config`:

```ts
content: ["./src/**/*.{ts,tsx}"],
```

Si mueves `PATCHES_DB` a un archivo que Tailwind no escanea, o construyes las clases por concatenación, tendrás que **safelistearlas**. Colores usados: `cyan, emerald, blue, orange, indigo, teal, violet, fuchsia` (tonos 400/500/900) + `amber-600/800`, `white`, `slate-*`.

---

## 5. Código completo (drop-in)

Guárdalo como `src/components/U90XBodyMap.tsx` (ajusta los imports de las imágenes a tu ruta de assets).

```tsx
import React, { useState } from 'react';
import {
  Zap, Brain, Droplets, ShieldCheck, Activity,
  Moon, Sun, Flame, Menu, X, ChevronRight, Target, ArrowLeft
} from 'lucide-react';
import bodyFront from '@/assets/bodymap/body-front.png';
import bodyBack from '@/assets/bodymap/body-back.png';

type AppLang = 'en' | 'es';
type AppTheme = 'dark' | 'light';

const tc = (theme: AppTheme, dark: string, light: string) => theme === 'dark' ? dark : light;

const UI_LABELS = {
  en: {
    title: 'U90X MAP',
    subtitle: 'TACTICAL PHOTOTHERAPY SYSTEM',
    front: 'Front',
    back: 'Back',
    selectPatch: 'Select your Patch',
    arsenal: 'U90X Arsenal',
    protocol: 'U90X Protocol',
    coordinates: 'Application Coordinates:',
    whiteRight: 'White',
    tanLeft: 'Tan',
    rightSide: 'Right Anatomical Side',
    leftSide: 'Left Anatomical Side',
    close: 'Back',
  },
  es: {
    title: 'U90X MAP',
    subtitle: 'SISTEMA TÁCTICO DE FOTOTERAPIA',
    front: 'Frente',
    back: 'Atrás',
    selectPatch: 'Selecciona tu Parche',
    arsenal: 'Arsenal U90X',
    protocol: 'Protocolo U90X',
    coordinates: 'Coordenadas de Aplicación:',
    whiteRight: 'Blanco',
    tanLeft: 'Bronceado',
    rightSide: 'Lado Derecho Anatómico',
    leftSide: 'Lado Izquierdo Anatómico',
    close: 'Volver',
  }
};

interface PatchPoint {
  id: string;
  label: string;
  desc: string;
  view: 'front' | 'back';
  x?: number;
  y: number;
  dual?: boolean;
  xRight?: number;
  xLeft?: number;
}

interface PatchData {
  id: string;
  name: string;
  subtitle: { en: string; es: string };
  colorClass: string;
  bgActive: string;
  bgIcon: string;
  textAccent: string;
  dotColor: string;
  icon: React.ElementType;
  desc: { en: string; es: string };
  points: PatchPoint[];
}

const PATCHES_DB: PatchData[] = [
  {
    id: 'x39',
    name: 'X39',
    subtitle: { en: 'Stem Cell Repair', es: 'Reparación Celular' },
    colorClass: 'text-cyan-400',
    bgActive: 'bg-cyan-900/20 border-cyan-500',
    bgIcon: 'bg-cyan-500',
    textAccent: 'text-cyan-400',
    dotColor: 'bg-cyan-400',
    icon: Zap,
    desc: {
      en: 'Stimulates GHK-Cu peptide. Use 1 patch daily where there is pain or on general wellness points.',
      es: 'Estimula el péptido GHK-Cu. Usa 1 parche diario donde haya dolor o en los puntos generales de bienestar.'
    },
    points: [
      { id: 'gv14', label: 'GV 14', desc: 'Base del cuello', view: 'back', x: 50, y: 14 },
      { id: 'cv6', label: 'CV 6', desc: 'Dos dedos bajo el ombligo', view: 'front', x: 50, y: 48 }
    ]
  },
  {
    id: 'sp6',
    name: 'SP6 Completo',
    subtitle: { en: 'Cravings & Hormones', es: 'Antojos y Hormonas' },
    colorClass: 'text-emerald-400',
    bgActive: 'bg-emerald-900/20 border-emerald-500',
    bgIcon: 'bg-emerald-500',
    textAccent: 'text-emerald-400',
    dotColor: 'bg-emerald-400',
    icon: Droplets,
    desc: {
      en: 'Radical appetite control and metabolic regulation. Optimizes digestion.',
      es: 'Control radical del apetito y regulación metabólica. Optimiza la digestión.'
    },
    points: [
      { id: 'sp6-left', label: 'SP 6 (Izq)', desc: '4 dedos sobre el hueso interno del tobillo', view: 'front', x: 60, y: 88 },
      { id: 'st36-left', label: 'ST 36 (Izq)', desc: 'Bajo la rodilla por fuera', view: 'front', x: 65, y: 72 },
      { id: 'kd3-left', label: 'KD 3 (Izq)', desc: 'Tobillo interno junto al tendón', view: 'front', x: 60, y: 92 },
      { id: 'cv6', label: 'CV 6', desc: 'Dos dedos bajo el ombligo', view: 'front', x: 50, y: 48 }
    ]
  },
  {
    id: 'aeon',
    name: 'Aeon',
    subtitle: { en: 'Stress & Inflammation', es: 'Estrés e Inflamación' },
    colorClass: 'text-blue-400',
    bgActive: 'bg-blue-900/20 border-blue-500',
    bgIcon: 'bg-blue-500',
    textAccent: 'text-blue-400',
    dotColor: 'bg-blue-400',
    icon: ShieldCheck,
    desc: {
      en: 'Balances the autonomic nervous system and reduces cellular inflammation causing aging.',
      es: 'Equilibra el sistema nervioso autónomo y reduce la inflamación celular causante del envejecimiento.'
    },
    points: [
      { id: 'gv14', label: 'GV 14', desc: 'Base del cuello', view: 'back', x: 50, y: 14 },
      { id: 'cv6', label: 'CV 6', desc: 'Bajo el ombligo', view: 'front', x: 50, y: 48 },
      { id: 'lu9-right', label: 'LU 9 (Der)', desc: 'Muñeca interna', view: 'front', x: 18, y: 58 },
      { id: 'sp6-right', label: 'SP 6 (Der)', desc: 'Tobillo interno', view: 'front', x: 40, y: 88 },
      { id: 'lv3-right', label: 'LV 3 (Der)', desc: 'Empeine del pie', view: 'front', x: 37, y: 95 }
    ]
  },
  {
    id: 'energy',
    name: 'Energy Enhancer',
    subtitle: { en: 'Vital Energy (Dual)', es: 'Energía Vital (Dual)' },
    colorClass: 'text-orange-400',
    bgActive: 'bg-orange-900/20 border-orange-500',
    bgIcon: 'bg-orange-500',
    textAccent: 'text-orange-400',
    dotColor: 'bg-orange-400',
    icon: Flame,
    desc: {
      en: 'Burns fat for energy. Always use WHITE on RIGHT and TAN on LEFT.',
      es: 'Quema grasa para producir energía. Siempre usa BLANCO a la DERECHA y BRONCEADO a la IZQUIERDA.'
    },
    points: [
      { id: 'lu1', label: 'LU 1', desc: 'Pecho, cerca del hombro', view: 'front', dual: true, xRight: 35, xLeft: 65, y: 22 },
      { id: 'pc6', label: 'PC 6', desc: 'Tres dedos bajo la muñeca', view: 'front', dual: true, xRight: 18, xLeft: 82, y: 52 },
      { id: 'tb5', label: 'TB 5', desc: 'Tres dedos sobre la muñeca (dorso)', view: 'back', dual: true, xRight: 82, xLeft: 18, y: 52 },
      { id: 'st36', label: 'ST 36', desc: 'Bajo la rodilla por fuera', view: 'front', dual: true, xRight: 35, xLeft: 65, y: 72 },
      { id: 'kd3', label: 'KD 3', desc: 'Tobillo interno', view: 'front', dual: true, xRight: 40, xLeft: 60, y: 92 }
    ]
  },
  {
    id: 'carnosine',
    name: 'Carnosine',
    subtitle: { en: 'Brain & Circulation', es: 'Cerebro y Circulación' },
    colorClass: 'text-indigo-400',
    bgActive: 'bg-indigo-900/20 border-indigo-500',
    bgIcon: 'bg-indigo-500',
    textAccent: 'text-indigo-400',
    dotColor: 'bg-indigo-400',
    icon: Brain,
    desc: {
      en: 'Muscle repair, cognitive improvement and cellular longevity.',
      es: 'Reparación muscular, mejora cognitiva y longevidad celular.'
    },
    points: [
      { id: 'gv14', label: 'GV 14', desc: 'Base del cuello', view: 'back', x: 50, y: 14 },
      { id: 'gv2', label: 'GV 2', desc: 'Línea media zona lumbar', view: 'back', x: 50, y: 52 },
      { id: 'cv17', label: 'CV 17', desc: 'Centro del pecho', view: 'front', x: 50, y: 25 },
      { id: 'li4-right', label: 'LI 4 (Der)', desc: 'Dorso de la mano', view: 'back', x: 88, y: 60 },
      { id: 'ht7-right', label: 'HT 7 (Der)', desc: 'Muñeca, lado meñique', view: 'front', x: 14, y: 60 }
    ]
  },
  {
    id: 'glutathione',
    name: 'Glutathione',
    subtitle: { en: 'Master Antioxidant', es: 'Antioxidante Maestro' },
    colorClass: 'text-teal-400',
    bgActive: 'bg-teal-900/20 border-teal-500',
    bgIcon: 'bg-teal-500',
    textAccent: 'text-teal-400',
    dotColor: 'bg-teal-400',
    icon: Activity,
    desc: {
      en: 'Deep detox from heavy metals and immune system support.',
      es: 'Desintoxicación profunda de metales pesados y apoyo al sistema inmunológico.'
    },
    points: [
      { id: 'cv22', label: 'CV 22', desc: 'Base de la garganta', view: 'front', x: 50, y: 16 },
      { id: 'cv6', label: 'CV 6', desc: 'Dos dedos bajo el ombligo', view: 'front', x: 50, y: 48 },
      { id: 'lu9-right', label: 'LU 9 (Der)', desc: 'Muñeca interna', view: 'front', x: 18, y: 58 },
      { id: 'sp6-right', label: 'SP 6 (Der)', desc: 'Tobillo interno', view: 'front', x: 40, y: 88 },
      { id: 'lv3-right', label: 'LV 3 (Der)', desc: 'Empeine del pie', view: 'front', x: 37, y: 95 }
    ]
  },
  {
    id: 'silentnights',
    name: 'Silent Nights',
    subtitle: { en: 'Restorative Sleep', es: 'Sueño Reparador' },
    colorClass: 'text-violet-400',
    bgActive: 'bg-violet-900/20 border-violet-500',
    bgIcon: 'bg-violet-500',
    textAccent: 'text-violet-400',
    dotColor: 'bg-violet-400',
    icon: Moon,
    desc: {
      en: 'Regulates natural melatonin production and improves deep sleep quality.',
      es: 'Regula la producción natural de melatonina y mejora la calidad del sueño profundo.'
    },
    points: [
      { id: 'tb23-right', label: 'TB 23 (Der)', desc: 'Sien, junto a la ceja externa', view: 'front', x: 45, y: 7 },
      { id: 'gb14', label: 'GB 14', desc: 'Un dedo sobre la ceja', view: 'front', x: 50, y: 5 },
      { id: 'tb17-right', label: 'TB 17 (Der)', desc: 'Detrás del lóbulo de la oreja', view: 'back', x: 55, y: 9 },
      { id: 'st36-right', label: 'ST 36 (Der)', desc: 'Bajo la rodilla por fuera', view: 'front', x: 35, y: 72 },
      { id: 'lv3-right', label: 'LV 3 (Der)', desc: 'Empeine del pie', view: 'front', x: 37, y: 95 }
    ]
  },
  {
    id: 'alavida',
    name: 'Alavida',
    subtitle: { en: 'Skin Regeneration', es: 'Regeneración de Piel' },
    colorClass: 'text-fuchsia-400',
    bgActive: 'bg-fuchsia-900/20 border-fuchsia-500',
    bgIcon: 'bg-fuchsia-500',
    textAccent: 'text-fuchsia-400',
    dotColor: 'bg-fuchsia-400',
    icon: Sun,
    desc: {
      en: 'Reduces oxidative stress, stimulates collagen production and renews skin.',
      es: 'Reduce el estrés oxidativo, estimula la producción de colágeno y renueva la piel.'
    },
    points: [
      { id: 'tb23-right', label: 'TB 23 (Der)', desc: 'Sien, junto a la ceja', view: 'front', x: 45, y: 7 },
      { id: 'gv245', label: 'GV 24.5', desc: 'Tercer ojo (Entrecejo)', view: 'front', x: 50, y: 6 },
      { id: 'gb14', label: 'GB 14', desc: 'Un dedo sobre la ceja', view: 'front', x: 50, y: 5 },
      { id: 'gv14', label: 'GV 14', desc: 'Base del cuello', view: 'back', x: 50, y: 14 }
    ]
  }
];

interface U90XBodyMapProps {
  lang: AppLang;
  theme: AppTheme;
  onClose: () => void;
}

export function U90XBodyMap({ lang, theme, onClose }: U90XBodyMapProps) {
  const [selectedPatch, setSelectedPatch] = useState<PatchData>(PATCHES_DB[0]);
  const [view, setView] = useState<'front' | 'back'>('front');
  const [hoveredPoint, setHoveredPoint] = useState<string | null>(null);
  const [isSidebarOpen, setIsSidebarOpen] = useState(false);
  const t = UI_LABELS[lang];

  const renderPatchDots = () => {
    // Las coords de PATCHES_DB venian calibradas al SVG cuadrado viejo. La figura
    // realista (2:3) tiene el cuerpo mas angosto -> se comprime X hacia el centro.
    const mx = (x: number) => 50 + (x - 50) * 0.72;
    // Y: interpolacion por anclas anatomicas (oldY -> newY) medidas sobre la figura
    // nueva: coronilla~5, garganta~20, pecho~29, ombligo~44, rodilla~72, tobillo~96.
    const ANCHORS: [number, number][] = [[0, 0], [5, 9], [16, 20], [25, 29], [48, 44], [72, 72], [95, 96], [100, 100]];
    const my = (y: number) => {
      for (let i = 1; i < ANCHORS.length; i++) {
        const [x0, v0] = ANCHORS[i - 1];
        const [x1, v1] = ANCHORS[i];
        if (y <= x1) return v0 + ((y - x0) * (v1 - v0)) / (x1 - x0);
      }
      return y;
    };
    return selectedPatch.points.filter(p => p.view === view).map((point, i) => {
      const isHovered = hoveredPoint === point.id;

      if (point.dual) {
        return (
          <React.Fragment key={`${point.id}-${i}`}>
            {/* White - Right */}
            <div
              className={`absolute w-5 h-5 -ml-2.5 -mt-2.5 rounded-full border-[3px] bg-white border-slate-300 shadow-[0_0_15px_rgba(255,255,255,0.9)] z-20 transition-all duration-300 cursor-pointer ${isHovered ? 'scale-[1.8] ring-4 ring-cyan-500/40' : 'animate-pulse'}`}
              style={{ top: `${my(point.y)}%`, left: `${mx(point.xRight!)}%` }}
              onMouseEnter={() => setHoveredPoint(point.id)}
              onMouseLeave={() => setHoveredPoint(null)}
            />
            {/* Tan - Left */}
            <div
              className={`absolute w-5 h-5 -ml-2.5 -mt-2.5 rounded-full border-[3px] bg-amber-600 border-amber-800 shadow-[0_0_15px_rgba(217,119,6,0.9)] z-20 transition-all duration-300 cursor-pointer ${isHovered ? 'scale-[1.8] ring-4 ring-cyan-500/40' : 'animate-pulse'}`}
              style={{ top: `${my(point.y)}%`, left: `${mx(point.xLeft!)}%` }}
              onMouseEnter={() => setHoveredPoint(point.id)}
              onMouseLeave={() => setHoveredPoint(null)}
            />
            {isHovered && (
              <div
                className={`absolute left-1/2 -translate-x-1/2 text-[10px] font-bold px-3 py-1.5 rounded-lg whitespace-nowrap z-30 shadow-2xl pointer-events-none border ${
                  tc(theme, 'bg-slate-900 border-slate-700 text-white', 'bg-white border-slate-200 text-slate-900')
                }`}
                style={{ top: `${my(point.y) + 4}%` }}
              >
                <Target size={12} className="inline mr-1 text-cyan-400" /> {point.label}
              </div>
            )}
          </React.Fragment>
        );
      }

      return (
        <div
          key={point.id}
          className={`absolute w-5 h-5 -ml-2.5 -mt-2.5 rounded-full ${selectedPatch.dotColor} border-[3px] border-white shadow-[0_0_20px_rgba(6,182,212,0.6)] z-20 transition-all duration-300 cursor-pointer flex items-center justify-center ${isHovered ? 'scale-[1.8] ring-4 ring-white/30' : 'animate-pulse'}`}
          style={{ top: `${my(point.y)}%`, left: `${mx(point.x)}%` }}
          onMouseEnter={() => setHoveredPoint(point.id)}
          onMouseLeave={() => setHoveredPoint(null)}
        >
          {isHovered && (
            <div className={`absolute top-6 left-1/2 -translate-x-1/2 text-[11px] font-bold px-3 py-1.5 rounded-lg whitespace-nowrap z-30 shadow-2xl pointer-events-none border ${
              tc(theme, 'bg-slate-900 border-slate-700 text-white', 'bg-white border-slate-200 text-slate-900')
            }`}>
              <Target size={12} className="inline mr-1 text-cyan-400" /> {point.label}
            </div>
          )}
        </div>
      );
    });
  };

  return (
    <div className={`fixed inset-0 z-[60] flex flex-col overflow-hidden transition-colors duration-300 ${
      tc(theme, 'bg-[#050B14] text-slate-200', 'bg-slate-50 text-slate-900')
    } selection:bg-cyan-500 selection:text-black`}>

      {/* Header */}
      <header className={`shrink-0 p-4 border-b backdrop-blur-md flex justify-between items-center shadow-lg safe-area-top ${
        tc(theme, 'bg-[#050B14]/90 border-slate-800', 'bg-white/95 border-slate-200')
      }`}>
        <div className="flex items-center gap-3">
          <button
            onClick={onClose}
            className={`p-2 rounded-xl transition-colors ${tc(theme, 'bg-slate-800 text-slate-400 hover:text-white', 'bg-slate-100 text-slate-500 hover:text-slate-900')}`}
          >
            <ArrowLeft size={18} />
          </button>
          <div>
            <h1 className={`text-lg font-black tracking-tight flex items-center gap-2 ${tc(theme, 'text-white', 'text-slate-900')}`}>
              <Activity className="text-cyan-400" size={20} /> {t.title}
            </h1>
            <p className={`text-[9px] uppercase tracking-[0.2em] font-bold mt-0.5 ${tc(theme, 'text-slate-500', 'text-slate-400')}`}>{t.subtitle}</p>
          </div>
        </div>
      </header>

      {/* Main Content */}
      <div className="flex-1 overflow-y-auto">
        <div className="w-full max-w-[1400px] mx-auto px-4 py-6 flex flex-col lg:flex-row gap-6 min-h-full">

          {/* Left Sidebar - Desktop */}
          <aside className="hidden lg:flex w-[300px] shrink-0 flex-col">
            <h3 className={`text-xs font-bold uppercase tracking-widest mb-4 ${tc(theme, 'text-slate-500', 'text-slate-400')}`}>{t.selectPatch}</h3>
            <div className="flex-1 overflow-y-auto space-y-2 pr-2 scrollbar-hide">
              {PATCHES_DB.map((patch) => {
                const isActive = selectedPatch.id === patch.id;
                const Icon = patch.icon;
                return (
                  <button
                    key={patch.id}
                    onClick={() => setSelectedPatch(patch)}
                    className={`w-full flex items-center gap-3 p-3 rounded-2xl border-2 transition-all text-left group ${
                      isActive
                        ? `${patch.bgActive} shadow-[0_0_20px_rgba(6,182,212,0.1)]`
                        : tc(theme, 'bg-slate-900/40 border-slate-800 hover:border-slate-700 hover:bg-slate-900/80', 'bg-white border-slate-200 hover:border-slate-300 hover:bg-slate-50')
                    }`}
                  >
                    <div className={`w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition-transform ${isActive ? `${patch.bgIcon} text-black scale-110` : tc(theme, 'bg-slate-800 text-slate-400', 'bg-slate-100 text-slate-500')} group-hover:scale-110`}>
                      <Icon size={20} />
                    </div>
                    <div className="flex-1 min-w-0">
                      <h4 className={`font-bold text-sm truncate ${isActive ? tc(theme, 'text-white', 'text-slate-900') : tc(theme, 'text-slate-300', 'text-slate-700')}`}>{patch.name}</h4>
                      <p className={`text-[10px] truncate mt-0.5 ${tc(theme, 'text-slate-500', 'text-slate-400')}`}>{patch.subtitle[lang]}</p>
                    </div>
                    {isActive && <ChevronRight className={`ml-auto ${patch.textAccent} shrink-0`} size={18} />}
                  </button>
                );
              })}
            </div>
          </aside>

          {/* Center - Body Map */}
          <main className="flex-1 flex flex-row items-stretch justify-center gap-2 relative min-h-[400px] lg:min-h-[500px]">
            <div className={`absolute inset-0 pointer-events-none ${tc(theme, 'bg-[radial-gradient(circle_at_center,_rgba(56,189,248,0.08)_0%,_transparent_60%)]', 'bg-[radial-gradient(circle_at_center,_rgba(56,189,248,0.04)_0%,_transparent_60%)]')}`} />

            {/* Selector de parches AL LADO de la imagen (movil): iconos, siempre visible */}
            <div className="lg:hidden flex flex-col gap-2 overflow-y-auto scrollbar-hide py-1 shrink-0 z-10">
              {PATCHES_DB.map((patch) => {
                const isActive = selectedPatch.id === patch.id;
                const Icon = patch.icon;
                return (
                  <button
                    key={patch.id}
                    onClick={() => setSelectedPatch(patch)}
                    aria-label={patch.name}
                    className={`w-11 h-11 rounded-xl flex items-center justify-center shrink-0 border-2 transition-all ${
                      isActive ? `${patch.bgIcon} text-black border-transparent scale-105` : tc(theme, 'bg-slate-900/60 border-slate-800 text-slate-400', 'bg-white border-slate-200 text-slate-500')
                    }`}
                  >
                    <Icon size={18} />
                  </button>
                );
              })}
            </div>

            {/* Columna de la figura: toggle ARRIBA + figura con puntos */}
            <div className="flex-1 flex flex-col items-center justify-center relative z-10">
              <div className={`mb-3 p-1 rounded-full flex gap-1 shadow-inner border ${tc(theme, 'bg-slate-900 border-slate-800', 'bg-slate-100 border-slate-200')}`}>
                <button onClick={() => setView('front')} className={`px-4 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-widest transition-all ${view === 'front' ? 'bg-cyan-500 text-black shadow-md' : tc(theme, 'text-slate-400 hover:text-white', 'text-slate-500 hover:text-slate-900')}`}>{t.front}</button>
                <button onClick={() => setView('back')} className={`px-4 py-1.5 rounded-full text-[10px] font-bold uppercase tracking-widest transition-all ${view === 'back' ? 'bg-cyan-500 text-black shadow-md' : tc(theme, 'text-slate-400 hover:text-white', 'text-slate-500 hover:text-slate-900')}`}>{t.back}</button>
              </div>
              <div className="relative w-full max-w-[240px] sm:max-w-[320px] lg:max-w-[380px] aspect-[2/3]">
                <img
                  src={view === 'front' ? bodyFront : bodyBack}
                  alt={view === 'front' ? t.front : t.back}
                  className="absolute inset-0 w-full h-full object-contain select-none pointer-events-none"
                  draggable={false}
                />
                {renderPatchDots()}
              </div>
            </div>
          </main>

          {/* Right - Info Card */}
          <aside className="w-full lg:w-[360px] shrink-0 flex flex-col justify-start pb-8">
            <div className={`backdrop-blur-xl border p-6 rounded-3xl shadow-2xl lg:sticky lg:top-4 ${
              tc(theme, 'bg-slate-900/80 border-slate-800', 'bg-white border-slate-200 shadow-lg')
            }`}>
              <div className="flex items-center gap-3 mb-5">
                <div className={`p-3 rounded-xl ${selectedPatch.bgIcon}/10 border ${selectedPatch.bgIcon}/30`}>
                  <selectedPatch.icon className={selectedPatch.textAccent} size={28} />
                </div>
                <div>
                  <h2 className={`text-xl font-black leading-tight ${tc(theme, 'text-white', 'text-slate-900')}`}>{selectedPatch.name}</h2>
                  <span className={`text-[10px] font-bold uppercase tracking-widest ${selectedPatch.textAccent}`}>{t.protocol}</span>
                </div>
              </div>

              <p className={`text-sm mb-6 leading-relaxed p-4 rounded-2xl border shadow-inner ${
                tc(theme, 'text-slate-300 bg-[#050B14] border-slate-800', 'text-slate-600 bg-slate-50 border-slate-200')
              }`}>
                {selectedPatch.desc[lang]}
              </p>

              <div className="space-y-2">
                <p className={`text-[10px] uppercase tracking-widest font-bold mb-3 flex items-center gap-2 ${tc(theme, 'text-slate-500', 'text-slate-400')}`}>
                  <Target size={14} /> {t.coordinates}
                </p>
                {selectedPatch.points.map(p => (
                  <div
                    key={p.id}
                    onMouseEnter={() => { setHoveredPoint(p.id); setView(p.view); }}
                    onMouseLeave={() => setHoveredPoint(null)}
                    className={`flex items-start gap-3 p-3 rounded-xl cursor-pointer border transition-all ${
                      hoveredPoint === p.id
                        ? `${selectedPatch.bgActive} shadow-inner`
                        : tc(theme, 'bg-[#050B14] border-slate-800 hover:border-slate-700', 'bg-slate-50 border-slate-200 hover:border-slate-300')
                    }`}
                  >
                    <div className={`mt-1.5 w-2.5 h-2.5 rounded-full shrink-0 ${
                      p.view === view ? `${selectedPatch.dotColor} shadow-[0_0_8px_rgba(6,182,212,0.6)]` : tc(theme, 'bg-slate-700', 'bg-slate-300')
                    }`} />
                    <div className="flex-1 min-w-0">
                      <p className={`text-sm font-bold ${tc(theme, 'text-white', 'text-slate-900')}`}>{p.label}</p>
                      <p className={`text-xs mt-0.5 leading-tight ${tc(theme, 'text-slate-400', 'text-slate-500')}`}>{p.desc}</p>
                    </div>
                    <span className={`text-[9px] uppercase font-bold tracking-wider px-2 py-1 rounded-md border shrink-0 ${
                      tc(theme, 'bg-slate-800 text-slate-400 border-slate-700', 'bg-slate-100 text-slate-500 border-slate-200')
                    }`}>
                      {p.view === 'front' ? t.front : t.back}
                    </span>
                  </div>
                ))}
              </div>

              {selectedPatch.id === 'energy' && (
                <div className={`mt-6 p-4 rounded-2xl flex items-center gap-3 border ${
                  tc(theme, 'bg-orange-950/20 border-orange-900/40', 'bg-orange-50 border-orange-200')
                }`}>
                  <div className="flex -space-x-3 shrink-0">
                    <div className="w-8 h-8 bg-white rounded-full border-2 border-slate-300 shadow-md relative z-10 flex items-center justify-center">
                      <span className="text-[10px] font-bold text-slate-400">D</span>
                    </div>
                    <div className="w-8 h-8 bg-amber-600 rounded-full border-2 border-amber-800 shadow-md flex items-center justify-center">
                      <span className="text-[10px] font-bold text-white">I</span>
                    </div>
                  </div>
                  <p className={`text-[11px] font-medium leading-relaxed ${tc(theme, 'text-orange-200/80', 'text-orange-800')}`}>
                    <strong className={tc(theme, 'text-white', 'text-slate-900')}>{t.whiteRight}</strong> = {t.rightSide}<br/>
                    <strong className="text-amber-500">{t.tanLeft}</strong> = {t.leftSide}
                  </p>
                </div>
              )}
            </div>
          </aside>
        </div>
      </div>
    </div>
  );
}
```

> `isSidebarOpen` quedó declarado del diseño anterior (drawer móvil) y ya no se usa; puedes borrarlo. Igual los imports `Menu, X`.

---

## 6. Cómo funcionan las coordenadas (clave para adaptar)

Cada punto se define con **coordenadas 0–100** (`x`, `y`) que representan porcentajes. Pero **NO** se pintan directo: pasan por dos funciones dentro de `renderPatchDots()` que las adaptan a la figura realista 2:3:

- **`mx(x)`** = `50 + (x - 50) * 0.72` → comprime el eje X hacia el centro (la figura realista es más angosta que el SVG cuadrado original). `0.72` es el factor de angostura.
- **`my(y)`** = interpolación lineal por tramos usando `ANCHORS`, que mapea la Y "vieja" (calibrada al SVG cuadrado) a la Y "real" medida sobre la figura nueva:

| Y vieja | Y real | Ancla anatómica |
|---|---|---|
| 5 | 9 | coronilla |
| 16 | 20 | garganta |
| 25 | 29 | pecho |
| 48 | 44 | ombligo |
| 72 | 72 | rodilla |
| 95 | 96 | tobillo |

Los puntos se posicionan con `position: absolute` + `top`/`left` en % sobre un contenedor `aspect-[2/3]`, con `-ml-2.5 -mt-2.5` para centrar el punto (de 20px) en la coordenada.

### Si vas a usar TUS propias imágenes

1. Ponlas en 2:3, PNG transparente, misma orientación (frente/espalda).
2. Abre la app y compara: si tus figuras tienen las mismas proporciones que las de U90X, quizá no toques nada.
3. Si no coinciden, **recalibra**:
   - Ajusta el factor `0.72` en `mx()` (más chico = puntos más juntos al centro).
   - Mide sobre tu figura dónde caen coronilla/garganta/pecho/ombligo/rodilla/tobillo (en % de altura) y actualiza `ANCHORS`.
   - Si tu arte ya está calibrado a coordenadas directas, puedes **eliminar `mx`/`my`** y usar `point.x`/`point.y` tal cual.

---

## 7. Uso

```tsx
import { useState } from 'react';
import { U90XBodyMap } from '@/components/U90XBodyMap';

export default function Demo() {
  const [open, setOpen] = useState(false);
  return (
    <>
      <button onClick={() => setOpen(true)}>Abrir mapa corporal</button>
      {open && (
        <U90XBodyMap
          lang="es"          // 'en' | 'es'
          theme="dark"       // 'dark' | 'light'
          onClose={() => setOpen(false)}
        />
      )}
    </>
  );
}
```

Props:

| Prop | Tipo | Descripción |
|---|---|---|
| `lang` | `'en' \| 'es'` | Idioma de todos los textos |
| `theme` | `'dark' \| 'light'` | Paleta clara u oscura |
| `onClose` | `() => void` | Se llama al pulsar el botón "volver" del header |

El componente es un **overlay `fixed inset-0 z-[60]`** de pantalla completa; contrólalo con un estado booleano en el padre (montar/desmontar), como en el ejemplo.

---

## 8. Personalizar el contenido (parches y puntos)

Todo vive en `PATCHES_DB`. Para cada parche:

- `id`, `name`, `subtitle{en,es}`, `desc{en,es}`, `icon` (componente de lucide-react).
- Colores (strings **literales** de Tailwind): `colorClass`, `bgActive`, `bgIcon`, `textAccent`, `dotColor`.
- `points[]`: cada punto con `id`, `label`, `desc`, `view` (`front`/`back`), y coordenadas:
  - Punto normal: `x`, `y`.
  - Punto **dual** (dos parches simétricos): `dual: true`, `xRight`, `xLeft`, `y` (sin `x`). Se pintan dos círculos (blanco a la derecha, ámbar/bronceado a la izquierda). El bloque explicativo "Blanco=Derecha / Bronceado=Izquierda" solo aparece para `id: 'energy'` (busca `selectedPatch.id === 'energy'` si quieres cambiar esa condición).

---

## 9. Checklist de replicación

1. `npm install react lucide-react` (y tener Tailwind 3 configurado).
2. Copiar `U90XBodyMap.tsx` a tu proyecto; ajustar las 2 rutas de import de imágenes.
3. Copiar `body-front.png` y `body-back.png` (2:3, transparentes) a tu carpeta de assets.
4. Agregar las utilidades CSS `safe-area-top` y `scrollbar-hide` (sección 4).
5. Confirmar que `tailwind.config` incluye el archivo en `content`.
6. Montar `<U90XBodyMap lang theme onClose />` desde un estado booleano.
7. (Si cambiaste las imágenes) recalibrar `mx`/`my` (sección 6).

---

Fuente original: `src/components/getfit/U90XBodyMap.tsx` del proyecto U90X_GFNGP (545 líneas). Este documento reproduce el componente verbatim más el contexto necesario para portarlo.

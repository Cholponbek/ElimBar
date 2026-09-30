<script setup>
import { computed } from 'vue';
import { lerpColor } from './colorLerp.js';

// Тема «Подарите тепло»: дом, который на 0% — заснеженный и холодный
// (голубая крыша, сосульки, снег на карнизе), а по мере сбора теплеет —
// крыша/трубы краснеют, окно светится ярче, снег и сосульки тают.
// "Игрушечный 3D"-стиль (градиенты + скругления + тень под домом) —
// не растровый рендер (тот не умеет плавно менять цвет по проценту
// сбора), но насыщеннее и объёмнее прежней плоской версии.
const props = defineProps({
    progress: { type: Number, required: true },
});

const t = computed(() => Math.max(0, Math.min(100, props.progress)) / 100);

const roofLight = computed(() => lerpColor('#A9CFE8', '#F2693D', t.value));
const roofDark = computed(() => lerpColor('#6E9AC0', '#B93C24', t.value));
const chimneyLight = computed(() => lerpColor('#CBDFEE', '#E08A4F', t.value));
const chimneyDark = computed(() => lerpColor('#96B4CC', '#A85A2E', t.value));
const wallLight = computed(() => lerpColor('#FFFFFF', '#FFF3E0', t.value));
const wallDark = computed(() => lerpColor('#DCEAF5', '#FBDCB8', t.value));
const glowOpacity = computed(() => 0.1 + t.value * 0.55);
const snowOpacity = computed(() => 1 - t.value);
const windowGlowOpacity = computed(() => 0.2 + t.value * 0.75);
// Дымок виден только когда труба уже начала теплеть.
const smokeOpacity = computed(() => Math.max(0, (t.value - 0.1) * 0.9));
</script>

<template>
    <div class="relative h-20 w-20 flex-shrink-0">
        <svg viewBox="0 0 96 96" class="h-20 w-20 house-icon">
            <defs>
                <linearGradient id="house-roof-grad" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" :stop-color="roofLight" />
                    <stop offset="100%" :stop-color="roofDark" />
                </linearGradient>
                <linearGradient id="house-chimney-grad" x1="0" y1="0" x2="1" y2="0">
                    <stop offset="0%" :stop-color="chimneyLight" />
                    <stop offset="100%" :stop-color="chimneyDark" />
                </linearGradient>
                <linearGradient id="house-wall-grad" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" :stop-color="wallLight" />
                    <stop offset="100%" :stop-color="wallDark" />
                </linearGradient>
            </defs>

            <ellipse cx="48" cy="87" rx="27" ry="4" fill="#0201A3" opacity="0.12" />
            <circle cx="48" cy="54" r="40" fill="#F5A65B" :opacity="glowOpacity" class="glow-pulse" />

            <!-- трубы -->
            <rect x="23" y="10" width="10" height="24" rx="2.5" fill="url(#house-chimney-grad)" />
            <rect x="61" y="10" width="10" height="24" rx="2.5" fill="url(#house-chimney-grad)" />
            <rect x="22" y="8" width="12" height="4" rx="2" fill="url(#house-chimney-grad)" />
            <rect x="60" y="8" width="12" height="4" rx="2" fill="url(#house-chimney-grad)" />

            <g :opacity="smokeOpacity" fill="none" stroke="#EAF1F7" stroke-width="2.5" stroke-linecap="round">
                <path class="smoke-puff smoke-puff-1" d="M66 8 q-3 -4 0 -8 q3 -4 0 -8" />
                <path class="smoke-puff smoke-puff-2" d="M66 8 q-3 -4 0 -8 q3 -4 0 -8" />
            </g>

            <!-- крыша, выпуклая (не плоский треугольник) -->
            <path d="M48,15 Q19,27 13,49 L83,49 Q77,27 48,15 Z" fill="url(#house-roof-grad)" />
            <path d="M48,18 Q26,29 19,47" fill="none" stroke="#FFFFFF" stroke-width="2.5" stroke-linecap="round" opacity="0.3" />

            <!-- стены со скруглением и цоколем -->
            <rect x="19" y="49" width="58" height="35" rx="11" fill="url(#house-wall-grad)" stroke="#C7D6E2" stroke-width="1" />
            <rect x="19" y="75" width="58" height="9" rx="4" fill="url(#house-wall-grad)" opacity="0.55" />

            <!-- окно -->
            <rect x="37" y="55" width="22" height="19" rx="5" fill="#FFE9A8" :opacity="windowGlowOpacity" />
            <rect x="37" y="55" width="22" height="19" rx="5" fill="none" stroke="#8FA0AC" stroke-width="1.5" />
            <path d="M48 55 v19 M37 64.5 h22" stroke="#8FA0AC" stroke-width="1.2" opacity="0.6" />

            <!-- дверь -->
            <path d="M42,84 V71 a6,6 0 0 1 12,0 V84 Z" fill="#6B4A34" />
            <circle cx="51" cy="77.5" r="1.3" fill="#F2D98A" />

            <!-- кустики у крыльца -->
            <ellipse cx="24" cy="82" rx="7" ry="6" fill="#6FAE5C" />
            <ellipse cx="72" cy="82" rx="7" ry="6" fill="#6FAE5C" />

            <!-- снег на карнизе и сосульки, тают с прогрессом -->
            <path
                d="M15 49 Q28 44 40 49 Q52 44 64 49 Q73 45 81 49"
                fill="none" stroke="white" stroke-width="4.5" stroke-linecap="round"
                :opacity="snowOpacity"
            />
            <g :opacity="snowOpacity" fill="#E3F1FB">
                <path d="M24 49 l2.5 9 l2.5 -9 z" />
                <path d="M44 49 l2.5 11 l2.5 -11 z" />
                <path d="M62 49 l2.5 8 l2.5 -8 z" />
            </g>
        </svg>
        <span class="font-heading absolute -bottom-1 -right-1 rounded-full bg-white px-1.5 py-0.5 text-[11px] font-extrabold text-brand-navy shadow">
            {{ Math.round(progress) }}%
        </span>
    </div>
</template>

<style scoped>
.house-icon {
    filter: drop-shadow(0 3px 3px rgba(2, 1, 163, 0.25));
}

.glow-pulse {
    transform-box: fill-box;
    transform-origin: center;
    animation: glow-pulse 2.6s ease-in-out infinite;
}

.smoke-puff {
    transform-box: fill-box;
    transform-origin: bottom center;
    opacity: 0;
    animation: smoke-rise 3s ease-out infinite;
}

.smoke-puff-2 {
    animation-delay: 1.5s;
}

@keyframes glow-pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.08); }
}

@keyframes smoke-rise {
    0% { transform: translateY(0) scale(0.6); opacity: 0; }
    15% { opacity: 0.8; }
    100% { transform: translateY(-14px) scale(1.15); opacity: 0; }
}

@media (prefers-reduced-motion: reduce) {
    .glow-pulse,
    .smoke-puff {
        animation: none;
    }
}
</style>

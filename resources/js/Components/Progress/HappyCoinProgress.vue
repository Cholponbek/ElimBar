<script setup>
import { computed } from 'vue';
import { lerpColor } from './colorLerp.js';

// Тема «Добрая монета»: на 0% — грустная серебристая монета, по мере
// сбора цвет теплеет до золотого и рот выгибается из грустной дуги в
// улыбку; при высоком прогрессе появляются искорки радости по бокам.
// Плюс лёгкая непрерывная анимация (CSS) — покачивание, моргание и
// бегущий блик (имитация объёма/3D), чтобы монета не была статичной.
const props = defineProps({
    progress: { type: Number, required: true },
});

const t = computed(() => Math.max(0, Math.min(100, props.progress)) / 100);

const bodyLight = computed(() => lerpColor('#E1E4E8', '#FFE58A', t.value));
const bodyDark = computed(() => lerpColor('#A7ACB4', '#D89B1F', t.value));
const ringColor = computed(() => lerpColor('#9CA3AF', '#C8901A', t.value));

// Рот — квадратичная кривая с концами в (38,58)/(58,58). Control point
// ВЫШЕ концов (y < 58) тянет середину вверх — дуга-арка ∩, то есть
// грустный рот; control point НИЖЕ концов (y > 58) тянет середину вниз —
// дуга-чаша ⌣, то есть улыбка. Поэтому control Y растёт с прогрессом.
const mouthControlY = computed(() => 50 + 16 * t.value);
const mouthPath = computed(() => `M38 58 Q48 ${mouthControlY.value} 58 58`);

const sparkleOpacity = computed(() => Math.max(0, (t.value - 0.7) / 0.3));
</script>

<template>
    <div class="relative h-20 w-20 flex-shrink-0">
        <svg viewBox="0 0 96 96" class="h-20 w-20 coin-icon">
            <defs>
                <clipPath id="coin-clip">
                    <circle cx="48" cy="48" r="40" />
                </clipPath>
                <linearGradient id="coin-body-grad" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" :stop-color="bodyLight" />
                    <stop offset="100%" :stop-color="bodyDark" />
                </linearGradient>
            </defs>

            <ellipse cx="48" cy="88" rx="24" ry="4" fill="#0201A3" opacity="0.12" />

            <g class="coin-sway">
                <circle cx="48" cy="48" r="40" fill="url(#coin-body-grad)" />
                <circle cx="48" cy="48" r="33" fill="none" :stroke="ringColor" stroke-width="2.5" stroke-dasharray="4 3" />

                <!-- бегущий блик — имитация объёма/3D на плоской монете -->
                <ellipse cx="30" cy="30" rx="10" ry="22" fill="white" opacity="0.35" class="coin-shine" clip-path="url(#coin-clip)" />

                <g class="coin-blink">
                    <circle cx="38" cy="44" r="3" fill="#3D4655" />
                    <circle cx="58" cy="44" r="3" fill="#3D4655" />
                </g>

                <path :d="mouthPath" fill="none" stroke="#3D4655" stroke-width="3" stroke-linecap="round" />
            </g>

            <g :opacity="sparkleOpacity" stroke="#F5C542" stroke-width="2" stroke-linecap="round" class="coin-sparkle">
                <path d="M16 30 l3 3 M16 36 l3 -3" />
                <path d="M80 30 l-3 3 M80 36 l-3 -3" />
            </g>
        </svg>
        <span class="font-heading absolute -bottom-1 -right-1 rounded-full bg-white px-1.5 py-0.5 text-[11px] font-extrabold text-brand-navy shadow">
            {{ Math.round(progress) }}%
        </span>
    </div>
</template>

<style scoped>
.coin-icon {
    filter: drop-shadow(0 3px 3px rgba(2, 1, 163, 0.25));
}

.coin-sway {
    transform-box: fill-box;
    transform-origin: center;
    animation: coin-sway 3.4s ease-in-out infinite;
}

.coin-shine {
    animation: coin-shine 3.4s ease-in-out infinite;
}

.coin-blink {
    transform-box: fill-box;
    transform-origin: center;
    animation: coin-blink 4.5s ease-in-out infinite;
}

.coin-sparkle path {
    animation: sparkle-twinkle 1.4s ease-in-out infinite;
}

.coin-sparkle path:last-child {
    animation-delay: 0.5s;
}

@keyframes coin-sway {
    0%, 100% { transform: rotate(-3deg); }
    50% { transform: rotate(3deg); }
}

@keyframes coin-shine {
    0% { transform: translateX(-6px); opacity: 0; }
    45% { opacity: 0.35; }
    55% { opacity: 0.35; }
    100% { transform: translateX(64px); opacity: 0; }
}

@keyframes coin-blink {
    0%, 92%, 100% { transform: scaleY(1); }
    96% { transform: scaleY(0.1); }
}

@keyframes sparkle-twinkle {
    0%, 100% { opacity: 0.3; }
    50% { opacity: 1; }
}

@media (prefers-reduced-motion: reduce) {
    .coin-sway,
    .coin-shine,
    .coin-blink,
    .coin-sparkle path {
        animation: none;
    }
}
</style>

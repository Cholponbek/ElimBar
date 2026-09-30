<script setup>
import { computed } from 'vue';
import { lerpColor } from './colorLerp.js';

// Тема «Добрая монета»: на 0% — грустная серебристая монета, по мере
// сбора цвет теплеет до золотого и рот выгибается из грустной дуги в
// улыбку; при высоком прогрессе появляются искорки радости по бокам.
const props = defineProps({
    progress: { type: Number, required: true },
});

const t = computed(() => Math.max(0, Math.min(100, props.progress)) / 100);

const bodyColor = computed(() => lerpColor('#C9CDD3', '#F5C542', t.value));
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
        <svg viewBox="0 0 96 96" class="h-20 w-20">
            <circle cx="48" cy="48" r="40" :fill="bodyColor" />
            <circle cx="48" cy="48" r="33" fill="none" :stroke="ringColor" stroke-width="2.5" stroke-dasharray="4 3" />

            <circle cx="38" cy="44" r="3" fill="#3D4655" />
            <circle cx="58" cy="44" r="3" fill="#3D4655" />

            <path :d="mouthPath" fill="none" stroke="#3D4655" stroke-width="3" stroke-linecap="round" />

            <g :opacity="sparkleOpacity" stroke="#F5C542" stroke-width="2" stroke-linecap="round">
                <path d="M16 30 l3 3 M16 36 l3 -3" />
                <path d="M80 30 l-3 3 M80 36 l-3 -3" />
            </g>
        </svg>
        <span class="font-heading absolute -bottom-1 -right-1 rounded-full bg-white px-1.5 py-0.5 text-[11px] font-extrabold text-brand-navy shadow">
            {{ Math.round(progress) }}%
        </span>
    </div>
</template>

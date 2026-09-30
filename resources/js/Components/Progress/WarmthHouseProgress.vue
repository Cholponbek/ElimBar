<script setup>
import { computed } from 'vue';
import { lerpColor } from './colorLerp.js';

// Тема «Подарите тепло»: дом, который на 0% — заснеженный и холодный
// (голубая крыша, сосульки, снег на карнизе), а по мере сбора теплеет —
// крыша/труба краснеют, окно светится ярче, снег и сосульки тают.
const props = defineProps({
    progress: { type: Number, required: true },
});

const t = computed(() => Math.max(0, Math.min(100, props.progress)) / 100);

const roofColor = computed(() => lerpColor('#8FB8D6', '#D9472B', t.value));
const chimneyColor = computed(() => lerpColor('#B8CFDE', '#B93C24', t.value));
const wallColor = computed(() => lerpColor('#EAF3FA', '#FCEEE3', t.value));
const glowOpacity = computed(() => 0.12 + t.value * 0.5);
const snowOpacity = computed(() => 1 - t.value);
const windowGlowOpacity = computed(() => 0.15 + t.value * 0.8);
</script>

<template>
    <div class="relative h-20 w-20 flex-shrink-0">
        <svg viewBox="0 0 96 96" class="h-20 w-20">
            <circle cx="48" cy="52" r="38" fill="#F5A65B" :opacity="glowOpacity" />

            <polygon points="48,20 78,44 18,44" :fill="roofColor" />
            <rect x="24" y="44" width="48" height="34" :fill="wallColor" stroke="#B8C4CF" stroke-width="1" />
            <rect x="60" y="22" width="8" height="18" :fill="chimneyColor" />

            <rect x="42" y="60" width="12" height="18" rx="1" fill="#5B4636" />
            <rect x="30" y="52" width="12" height="12" rx="1" fill="#FFE9A8" :opacity="windowGlowOpacity" />
            <rect x="30" y="52" width="12" height="12" rx="1" fill="none" stroke="#8FA0AC" stroke-width="1" />

            <path
                d="M20 44 Q30 40 40 44 Q50 40 60 44 Q68 41 76 44"
                fill="none" stroke="white" stroke-width="4" stroke-linecap="round"
                :opacity="snowOpacity"
            />
            <g :opacity="snowOpacity" fill="#D7ECFB">
                <path d="M28 44 l2 8 l2 -8 z" />
                <path d="M44 44 l2 10 l2 -10 z" />
                <path d="M60 44 l2 7 l2 -7 z" />
            </g>
        </svg>
        <span class="font-heading absolute -bottom-1 -right-1 rounded-full bg-white px-1.5 py-0.5 text-[11px] font-extrabold text-brand-navy shadow">
            {{ Math.round(progress) }}%
        </span>
    </div>
</template>

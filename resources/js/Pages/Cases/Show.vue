<script setup>
import { computed, ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import PublicLayout from '../../Layouts/PublicLayout.vue';
import { formatSom, pickLocale } from '../../money.js';

const props = defineProps({
    case: { type: Object, required: true },
    recentDonations: { type: Array, default: () => [] },
});

const page = usePage();
const locale = () => page.props.locale;
const flashSuccess = () => page.props.flash?.success;

// Ссылка на конкретный кейс — то, чем реально делятся.
const shareUrl = computed(() => (typeof window !== 'undefined' ? window.location.href : ''));
const shareTitle = computed(() => pickLocale(props.case.title, locale()));
const canNativeShare = typeof navigator !== 'undefined' && typeof navigator.share === 'function';

const categoryLabel = (category) => ({
    medical: 'Лечение',
    winter_food: 'Зимняя продуктовая помощь',
}[category] ?? category);

const progressPercent = () =>
    props.case.budget_minor > 0
        ? Math.min(100, Math.round((props.case.allocated_minor / props.case.budget_minor) * 100))
        : 0;

// Инстаграм не даёт сайту напрямую опубликовать сторис через URL. Хуже того:
// его расширение в системном меню "Поделиться" откликается в основном на
// изображения/видео — обычный текст+ссылка часто вообще не показывает пункт
// "Добавить в историю". Поэтому рисуем карточку самим (Canvas) и передаём её
// как файл: тогда Instagram принимает её как фон сторис. Если браузер не
// умеет шарить файлы (Web Share Level 2, часто отсутствует на десктопе) —
// просто скачиваем картинку, чтобы можно было загрузить её вручную.
function loadImage(src) {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => resolve(img);
        img.onerror = reject;
        img.src = src;
    });
}

function drawImageCover(ctx, img, x, y, w, h) {
    const imgRatio = img.width / img.height;
    const boxRatio = w / h;
    let sx, sy, sw, sh;
    if (imgRatio > boxRatio) {
        sh = img.height;
        sw = sh * boxRatio;
        sx = (img.width - sw) / 2;
        sy = 0;
    } else {
        sw = img.width;
        sh = sw / boxRatio;
        sx = 0;
        sy = (img.height - sh) / 2;
    }
    ctx.drawImage(img, sx, sy, sw, sh, x, y, w, h);
}

function wrapText(ctx, text, maxWidth, maxLines) {
    const words = (text || '').split(' ');
    const lines = [];
    let current = '';
    for (const word of words) {
        const test = current ? `${current} ${word}` : word;
        if (current && ctx.measureText(test).width > maxWidth) {
            lines.push(current);
            current = word;
            if (lines.length === maxLines) break;
        } else {
            current = test;
        }
    }
    if (current && lines.length < maxLines) {
        lines.push(current);
    }
    return lines.slice(0, maxLines);
}

function roundRect(ctx, x, y, w, h, r) {
    ctx.beginPath();
    ctx.moveTo(x + r, y);
    ctx.arcTo(x + w, y, x + w, y + h, r);
    ctx.arcTo(x + w, y + h, x, y + h, r);
    ctx.arcTo(x, y + h, x, y, r);
    ctx.arcTo(x, y, x + w, y, r);
    ctx.closePath();
}

async function buildStoryImage() {
    const width = 1080;
    const height = 1920;
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d');

    if (props.case.photoUrl) {
        const img = await loadImage(props.case.photoUrl);
        drawImageCover(ctx, img, 0, 0, width, height);
        const overlay = ctx.createLinearGradient(0, height * 0.35, 0, height);
        overlay.addColorStop(0, 'rgba(12, 10, 9, 0)');
        overlay.addColorStop(1, 'rgba(12, 10, 9, 0.92)');
        ctx.fillStyle = overlay;
        ctx.fillRect(0, 0, width, height);
    } else {
        const bg = ctx.createLinearGradient(0, 0, width, height);
        bg.addColorStop(0, '#f59e0b');
        bg.addColorStop(1, '#b45309');
        ctx.fillStyle = bg;
        ctx.fillRect(0, 0, width, height);
    }

    ctx.fillStyle = '#ffffff';
    ctx.font = '600 40px system-ui, sans-serif';
    ctx.fillText('ElimBar', 64, 96);
    ctx.font = '400 28px system-ui, sans-serif';
    ctx.fillStyle = 'rgba(255, 255, 255, 0.85)';
    ctx.fillText('Элим, барсыңбы?!', 64, 136);

    ctx.font = '700 32px system-ui, sans-serif';
    ctx.fillStyle = '#fbbf24';
    ctx.fillText(categoryLabel(props.case.category).toUpperCase(), 64, height - 620);

    ctx.fillStyle = '#ffffff';
    ctx.font = '700 64px system-ui, sans-serif';
    const titleLines = wrapText(ctx, pickLocale(props.case.title, locale()), width - 128, 3);
    let ty = height - 540;
    titleLines.forEach((line) => {
        ctx.fillText(line, 64, ty);
        ty += 74;
    });

    const barY = height - 300;
    const barW = width - 128;
    ctx.fillStyle = 'rgba(255, 255, 255, 0.25)';
    roundRect(ctx, 64, barY, barW, 16, 8);
    ctx.fill();
    ctx.fillStyle = '#fbbf24';
    roundRect(ctx, 64, barY, barW * (progressPercent() / 100), 16, 8);
    ctx.fill();

    ctx.font = '500 34px system-ui, sans-serif';
    ctx.fillStyle = '#ffffff';
    ctx.fillText(`${formatSom(props.case.allocated_minor)} из ${formatSom(props.case.budget_minor)}`, 64, barY + 60);

    ctx.font = '600 36px system-ui, sans-serif';
    ctx.fillStyle = '#fbbf24';
    ctx.fillText(`Поддержать → ${shareUrl.value.replace(/^https?:\/\//, '')}`, 64, height - 80);

    return new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));
}

const sharingImage = ref(false);
async function nativeShare() {
    sharingImage.value = true;
    try {
        const blob = await buildStoryImage().catch(() => null);
        if (blob) {
            const file = new File([blob], 'elimbar-case.png', { type: 'image/png' });
            if (navigator.canShare && navigator.canShare({ files: [file] })) {
                await navigator.share({ files: [file], title: shareTitle.value, text: shareUrl.value });
                return;
            }
        }
        // Файловый шеринг недоступен — обычная ссылка (сторис у Instagram
        // в этом случае может не появиться, но остальные приложения ей рады).
        await navigator.share({ title: shareTitle.value, url: shareUrl.value });
    } catch {
        // Пользователь закрыл системное меню — не ошибка.
    } finally {
        sharingImage.value = false;
    }
}

const downloadingImage = ref(false);
async function downloadStoryCard() {
    downloadingImage.value = true;
    try {
        const blob = await buildStoryImage();
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'elimbar-case.png';
        a.click();
        URL.revokeObjectURL(url);
    } finally {
        downloadingImage.value = false;
    }
}

const telegramShareUrl = computed(
    () => `https://t.me/share/url?url=${encodeURIComponent(shareUrl.value)}&text=${encodeURIComponent(shareTitle.value)}`,
);
const whatsappShareUrl = computed(
    () => `https://wa.me/?text=${encodeURIComponent(shareTitle.value + ' ' + shareUrl.value)}`,
);

const linkCopied = ref(false);
async function copyLink() {
    await navigator.clipboard.writeText(shareUrl.value);
    linkCopied.value = true;
    setTimeout(() => (linkCopied.value = false), 2000);
}

const formatDate = (isoString) =>
    new Date(isoString).toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit' });

const form = useForm({
    phone: '',
    amount: 500,
});

const presetAmounts = [200, 500, 1000, 5000];

function submit() {
    form.post(`/cases/${props.case.id}/donate`, {
        preserveScroll: true,
        onSuccess: () => form.reset('amount'),
    });
}
</script>

<template>
    <PublicLayout>
        <Link href="/" class="text-sm text-stone-500 hover:text-stone-700">&larr; Все кейсы</Link>

        <div class="mt-4 text-xs font-medium uppercase tracking-wide text-amber-600">
            {{ categoryLabel(props.case.category) }}
        </div>
        <h1 class="mt-2 text-xl font-semibold leading-snug sm:text-2xl">
            {{ pickLocale(props.case.title, locale()) }}
        </h1>

        <div class="mt-3 flex flex-wrap items-center gap-2">
            <button
                v-if="canNativeShare"
                type="button"
                :disabled="sharingImage"
                class="rounded-lg bg-stone-900 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-stone-700 disabled:opacity-60"
                @click="nativeShare"
            >
                {{ sharingImage ? 'Готовим карточку…' : 'Поделиться' }}
            </button>
            <button
                type="button"
                :disabled="downloadingImage"
                class="rounded-lg border border-stone-200 px-3 py-1.5 text-sm text-stone-600 transition hover:border-stone-300 disabled:opacity-60"
                @click="downloadStoryCard"
            >
                {{ downloadingImage ? 'Готовим…' : 'Скачать карточку для Stories' }}
            </button>
            <a
                :href="telegramShareUrl"
                target="_blank"
                rel="noopener"
                class="rounded-lg border border-stone-200 px-3 py-1.5 text-sm text-stone-600 transition hover:border-stone-300"
            >
                Telegram
            </a>
            <a
                :href="whatsappShareUrl"
                target="_blank"
                rel="noopener"
                class="rounded-lg border border-stone-200 px-3 py-1.5 text-sm text-stone-600 transition hover:border-stone-300"
            >
                WhatsApp
            </a>
            <button
                type="button"
                class="rounded-lg border border-stone-200 px-3 py-1.5 text-sm text-stone-600 transition hover:border-stone-300"
                @click="copyLink"
            >
                {{ linkCopied ? 'Ссылка скопирована' : 'Скопировать ссылку' }}
            </button>
        </div>

        <img
            v-if="props.case.photoUrl"
            :src="props.case.photoUrl"
            :alt="pickLocale(props.case.title, locale())"
            class="mt-6 aspect-video w-full rounded-xl object-cover"
        />

        <div class="mt-8 grid gap-6 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <p v-if="pickLocale(props.case.story, locale())" class="whitespace-pre-line leading-relaxed text-stone-700">
                    {{ pickLocale(props.case.story, locale()) }}
                </p>
                <p v-else class="text-stone-400">Подробностей пока нет.</p>
            </div>

            <div class="sm:col-span-1">
                <div class="rounded-xl border border-stone-200 bg-white p-4 sm:p-5">
                    <div class="h-2 overflow-hidden rounded-full bg-stone-100">
                        <div class="h-full bg-amber-500" :style="{ width: progressPercent() + '%' }" />
                    </div>
                    <div class="mt-2 flex flex-col gap-1 text-sm text-stone-500 sm:flex-row sm:justify-between">
                        <span>{{ formatSom(props.case.allocated_minor) }} собрано</span>
                        <span>из {{ formatSom(props.case.budget_minor) }}</span>
                    </div>

                    <div class="mt-3 text-sm text-stone-500">
                        Выплачено по кейсу: {{ formatSom(props.case.disbursed_minor) }}
                    </div>

                    <div v-if="flashSuccess()" class="mt-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ flashSuccess() }}
                    </div>

                    <form class="mt-6 space-y-4" @submit.prevent="submit">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-stone-700">Сумма, сом</label>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="preset in presetAmounts"
                                    :key="preset"
                                    type="button"
                                    class="rounded-lg border px-3 py-1.5 text-sm transition"
                                    :class="form.amount === preset
                                        ? 'border-amber-500 bg-amber-50 text-amber-700'
                                        : 'border-stone-200 text-stone-600 hover:border-stone-300'"
                                    @click="form.amount = preset"
                                >
                                    {{ preset.toLocaleString('ru-RU') }}
                                </button>
                            </div>
                            <input
                                v-model.number="form.amount"
                                type="number"
                                min="1"
                                step="1"
                                class="mt-2 w-full rounded-lg border border-stone-300 px-3 py-2.5 text-base focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
                                placeholder="Или своя сумма"
                            />
                            <p v-if="form.errors.amount" class="mt-1 text-sm text-red-600">{{ form.errors.amount }}</p>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-stone-700">Телефон</label>
                            <input
                                v-model="form.phone"
                                type="tel"
                                placeholder="+996 700 000 000"
                                class="w-full rounded-lg border border-stone-300 px-3 py-2.5 text-base focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
                            />
                            <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</p>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full rounded-lg bg-amber-500 px-4 py-3 font-medium text-white transition hover:bg-amber-600 disabled:opacity-60"
                        >
                            {{ form.processing ? 'Отправляем…' : `Поддержать — ${form.amount || 0} сом` }}
                        </button>
                        <p class="text-center text-xs text-stone-400">
                            Демо-режим: реальный платёжный провайдер ещё не подключён, деньги не списываются.
                        </p>
                    </form>
                </div>

                <div v-if="recentDonations.length > 0" class="mt-4 rounded-xl border border-stone-200 bg-white p-4 sm:p-5">
                    <h2 class="text-sm font-medium text-stone-700">Последние донаты</h2>
                    <ul class="mt-3 space-y-2">
                        <li
                            v-for="(donation, index) in recentDonations"
                            :key="index"
                            class="flex items-center justify-between text-sm text-stone-600"
                        >
                            <span class="flex flex-col">
                                <span class="text-stone-400">{{ formatDate(donation.created_at) }}</span>
                                <span v-if="donation.donorPhoneMasked" class="text-xs text-stone-400">{{ donation.donorPhoneMasked }}</span>
                            </span>
                            <span class="font-medium">{{ formatSom(donation.amount_minor) }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

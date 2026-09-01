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
    fund_project: 'Проект фонда',
}[category] ?? category);

const progressPercent = () =>
    props.case.budget_minor > 0
        ? Math.min(100, Math.round((props.case.allocated_minor / props.case.budget_minor) * 100))
        : 0;

// Кольцо прогресса (GoFundMe-паттерн) — окружность длиной 2πr, показываем
// нужную долю через stroke-dasharray/offset, остальное решает CSS-transition.
const RING_RADIUS = 42;
const RING_CIRCUMFERENCE = 2 * Math.PI * RING_RADIUS;
const ringOffset = () => RING_CIRCUMFERENCE * (1 - progressPercent() / 100);

// Для донатов с именем — инициал (аватар-кружок); для замаскированного
// телефона (начинается на "+" или "****") показываем общую иконку донора,
// потому что маскированный номер — не то, что стоит выносить в аватар.
const isMaskedPhone = (display) => !display || display.startsWith('+') || display.startsWith('*');
const avatarInitial = (display) => (isMaskedPhone(display) ? null : display.trim().charAt(0).toUpperCase());

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

// Плашка-"таблетка" (как у GoFundMe: сумма/CTA на цветном фоне, а не голым
// текстом) — рисует её и возвращает { width, height }, чтобы вызывающий код
// мог сдвинуть курсор вниз на её реальный размер.
function drawPill(ctx, text, x, y, { font, textColor, bgColor, paddingX, paddingY }) {
    ctx.font = font;
    const w = ctx.measureText(text).width + paddingX * 2;
    const size = parseInt(font.match(/(\d+)px/)[1], 10);
    const h = size + paddingY * 2;

    ctx.fillStyle = bgColor;
    roundRect(ctx, x, y, w, h, h / 2);
    ctx.fill();

    ctx.fillStyle = textColor;
    ctx.textBaseline = 'middle';
    ctx.fillText(text, x + paddingX, y + h / 2 + 2);
    ctx.textBaseline = 'alphabetic';

    return { width: w, height: h };
}

function truncateToWidth(ctx, text, maxWidth) {
    if (ctx.measureText(text).width <= maxWidth) {
        return text;
    }
    let cut = text;
    while (cut.length > 1 && ctx.measureText(`${cut}…`).width > maxWidth) {
        cut = cut.slice(0, -1);
    }
    return `${cut}…`;
}

// ui-rounded — генерик-семейство CSS Fonts Level 4: в мобильном
// Safari (а именно там чаще всего и открывают "Поделиться" в Instagram)
// это SF Rounded — тот самый дружелюбный закруглённый жирный шрифт, каким
// пользуются GoFundMe/Instagram-сторис. На платформах, где браузер его не
// знает, спецификация требует просто пропустить неизвестное имя и уйти
// дальше по списку — падать некуда, а не подключать веб-шрифт ради Canvas.
const STORY_FONT = 'ui-rounded, -apple-system, "Helvetica Neue", Arial, sans-serif';
// Плашки — фирменный ярко-голубой (Pantone 801 C), тёмно-синий текст:
// та же логика, что была у янтарного варианта (яркий цвет — тёмный текст,
// чтобы читалось поверх тёмного градиента на фото), просто в рамках трёх
// фирменных синих без стороннего акцента — сайт теперь строго на них.
const STORY_INK = '#0201a3';
const STORY_ACCENT = '#00ade6';

// Размеры блоков в одном месте: используются и чтобы заранее посчитать
// суммарную высоту контента (для вертикального центрирования — заголовок
// на 1–3 строки, высота блока каждый раз разная), и чтобы реально
// нарисовать те же элементы тем же шрифтом/паддингами. Дублировать эти
// числа во втором проходе — верный способ рассинхронизировать расчёт
// с отрисовкой.
const STORY_BRAND_BLOCK = 96;
const STORY_CAT = { fontSize: 32, padX: 28, padY: 16, gapAfter: 44 };
const STORY_TITLE = { fontSize: 84, lineHeight: 94, gapAfter: 56 };
const STORY_STAT = { fontSize: 48, padX: 32, padY: 22, gapAfter: 44 };
const STORY_BAR = { height: 26, gapAfter: 50 };
const STORY_GOAL_BLOCK = 82;
const STORY_CTA = { fontSize: 46, padX: 40, padY: 24, gapAfter: 36 };
const STORY_URL_BLOCK = 40;
const STORY_TOP_SAFE = 220; // не залезать под иконки редактора Instagram сверху
const STORY_BOTTOM_SAFE = 260; // и под панель подписи/стикеров снизу

async function buildStoryImage() {
    const width = 1080;
    const height = 1920;
    const pad = 72;
    const contentWidth = width - pad * 2;
    const canvas = document.createElement('canvas');
    canvas.width = width;
    canvas.height = height;
    const ctx = canvas.getContext('2d');

    if (props.case.photoUrl) {
        const img = await loadImage(props.case.photoUrl);
        drawImageCover(ctx, img, 0, 0, width, height);
        // Ровный тёмный тон по всей фотографии (иначе верхний брендинг
        // на светлом небе/фоне нечитаем) плюс более тёмный градиент снизу,
        // где сидит самый важный текст — сумма, прогресс, кнопка.
        ctx.fillStyle = 'rgba(10, 9, 8, 0.32)';
        ctx.fillRect(0, 0, width, height);
        const overlay = ctx.createLinearGradient(0, height * 0.32, 0, height);
        overlay.addColorStop(0, 'rgba(10, 9, 8, 0)');
        overlay.addColorStop(1, 'rgba(10, 9, 8, 0.88)');
        ctx.fillStyle = overlay;
        ctx.fillRect(0, 0, width, height);
    } else {
        const bg = ctx.createLinearGradient(0, 0, width, height);
        bg.addColorStop(0, '#0087D2');
        bg.addColorStop(1, '#0201A3');
        ctx.fillStyle = bg;
        ctx.fillRect(0, 0, width, height);
    }

    // Заголовок переносится по словам в зависимости от реальной ширины
    // текста (кириллица непредсказуема по числу символов), поэтому число
    // строк — а значит и общая высота блока — известно только после
    // wrapText. Считаем её один раз и центрируем блок по вертикали:
    // иначе при коротком заголовке контент повисает у самого верха с
    // пустым низом, а при длинном рискует не влезть в безопасную зону.
    ctx.font = `900 ${STORY_TITLE.fontSize}px ${STORY_FONT}`;
    const titleLines = wrapText(ctx, pickLocale(props.case.title, locale()), contentWidth, 3);

    const catPillHeight = STORY_CAT.fontSize + STORY_CAT.padY * 2;
    const statPillHeight = STORY_STAT.fontSize + STORY_STAT.padY * 2;
    const ctaPillHeight = STORY_CTA.fontSize + STORY_CTA.padY * 2;
    const totalHeight = STORY_BRAND_BLOCK
        + catPillHeight + STORY_CAT.gapAfter
        + titleLines.length * STORY_TITLE.lineHeight + STORY_TITLE.gapAfter
        + statPillHeight + STORY_STAT.gapAfter
        + STORY_BAR.height + STORY_BAR.gapAfter
        + STORY_GOAL_BLOCK
        + ctaPillHeight + STORY_CTA.gapAfter
        + STORY_URL_BLOCK;

    const idealStart = (height - totalHeight) / 2;
    let cursorY = Math.min(
        Math.max(idealStart, STORY_TOP_SAFE),
        height - STORY_BOTTOM_SAFE - totalHeight,
    );

    ctx.fillStyle = '#ffffff';
    ctx.font = `800 46px ${STORY_FONT}`;
    ctx.fillText('ElimBar', pad, cursorY);
    cursorY += STORY_BRAND_BLOCK;

    const catPill = drawPill(ctx, categoryLabel(props.case.category).toUpperCase(), pad, cursorY, {
        font: `800 ${STORY_CAT.fontSize}px ${STORY_FONT}`,
        textColor: STORY_INK,
        bgColor: STORY_ACCENT,
        paddingX: STORY_CAT.padX,
        paddingY: STORY_CAT.padY,
    });
    cursorY += catPill.height + STORY_CAT.gapAfter;

    ctx.fillStyle = '#ffffff';
    ctx.font = `900 ${STORY_TITLE.fontSize}px ${STORY_FONT}`;
    titleLines.forEach((line) => {
        cursorY += STORY_TITLE.lineHeight;
        ctx.fillText(line, pad, cursorY);
    });
    cursorY += STORY_TITLE.gapAfter;

    const statPill = drawPill(ctx, `${formatSom(props.case.allocated_minor)} собрано`, pad, cursorY, {
        font: `800 ${STORY_STAT.fontSize}px ${STORY_FONT}`,
        textColor: STORY_INK,
        bgColor: STORY_ACCENT,
        paddingX: STORY_STAT.padX,
        paddingY: STORY_STAT.padY,
    });
    cursorY += statPill.height + STORY_STAT.gapAfter;

    const barH = STORY_BAR.height;
    ctx.font = `800 52px ${STORY_FONT}`;
    const percentText = `${progressPercent()}%`;
    const percentWidth = ctx.measureText(percentText).width;
    const barW = contentWidth - percentWidth - 28;
    ctx.fillStyle = 'rgba(255, 255, 255, 0.3)';
    roundRect(ctx, pad, cursorY, barW, barH, barH / 2);
    ctx.fill();
    ctx.fillStyle = STORY_ACCENT;
    roundRect(ctx, pad, cursorY, barW * (progressPercent() / 100), barH, barH / 2);
    ctx.fill();
    ctx.fillStyle = '#ffffff';
    ctx.textBaseline = 'middle';
    ctx.fillText(percentText, pad + barW + 28, cursorY + barH / 2 + 2);
    ctx.textBaseline = 'alphabetic';
    cursorY += barH + STORY_BAR.gapAfter;

    ctx.font = `600 34px ${STORY_FONT}`;
    ctx.fillStyle = 'rgba(255, 255, 255, 0.85)';
    ctx.fillText(`Цель — ${formatSom(props.case.budget_minor)}`, pad, cursorY);
    cursorY += STORY_GOAL_BLOCK;

    const ctaPill = drawPill(ctx, 'Поддержать →', pad, cursorY, {
        font: `800 ${STORY_CTA.fontSize}px ${STORY_FONT}`,
        textColor: STORY_INK,
        bgColor: STORY_ACCENT,
        paddingX: STORY_CTA.padX,
        paddingY: STORY_CTA.padY,
    });
    cursorY += ctaPill.height + STORY_CTA.gapAfter;

    ctx.font = `500 28px ${STORY_FONT}`;
    ctx.fillStyle = 'rgba(255, 255, 255, 0.7)';
    const urlText = shareUrl.value.replace(/^https?:\/\//, '');
    ctx.fillText(truncateToWidth(ctx, urlText, contentWidth), pad, cursorY);

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
    name: '',
    show_name_publicly: false,
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
        <Link href="/" class="text-sm text-[#5B6472] hover:text-brand-navy">&larr; Все кейсы</Link>

        <div class="font-heading mt-4 text-xs font-bold uppercase tracking-wider text-brand-cyan">
            {{ categoryLabel(props.case.category) }}
        </div>
        <h1 class="font-heading mt-2 text-xl font-bold leading-snug text-brand-navy sm:text-2xl">
            {{ pickLocale(props.case.title, locale()) }}
        </h1>

        <div class="mt-3 flex flex-wrap items-center gap-2">
            <button
                v-if="canNativeShare"
                type="button"
                :disabled="sharingImage"
                class="rounded-lg bg-brand-navy px-3 py-1.5 text-sm font-medium text-white transition hover:bg-brand-navy/90 disabled:opacity-60"
                @click="nativeShare"
            >
                {{ sharingImage ? 'Готовим карточку…' : 'Поделиться' }}
            </button>
            <button
                type="button"
                :disabled="downloadingImage"
                class="rounded-lg border border-[#DCE6F0] px-3 py-1.5 text-sm text-[#5B6472] transition hover:border-brand-cyan disabled:opacity-60"
                @click="downloadStoryCard"
            >
                {{ downloadingImage ? 'Готовим…' : 'Скачать карточку для Stories' }}
            </button>
            <a
                :href="telegramShareUrl"
                target="_blank"
                rel="noopener"
                class="rounded-lg border border-[#DCE6F0] px-3 py-1.5 text-sm text-[#5B6472] transition hover:border-brand-cyan"
            >
                Telegram
            </a>
            <a
                :href="whatsappShareUrl"
                target="_blank"
                rel="noopener"
                class="rounded-lg border border-[#DCE6F0] px-3 py-1.5 text-sm text-[#5B6472] transition hover:border-brand-cyan"
            >
                WhatsApp
            </a>
            <button
                type="button"
                class="rounded-lg border border-[#DCE6F0] px-3 py-1.5 text-sm text-[#5B6472] transition hover:border-brand-cyan"
                @click="copyLink"
            >
                {{ linkCopied ? 'Ссылка скопирована' : 'Скопировать ссылку' }}
            </button>
        </div>

        <img
            v-if="props.case.photoUrl"
            :src="props.case.photoUrl"
            :alt="pickLocale(props.case.title, locale())"
            class="mt-6 aspect-video w-full rounded-[10px] object-cover"
        />

        <div class="mt-8 grid gap-6 lg:grid-cols-3 lg:items-start">
            <div class="lg:col-span-2">
                <p v-if="pickLocale(props.case.story, locale())" class="whitespace-pre-line leading-relaxed text-[#3D4655]">
                    {{ pickLocale(props.case.story, locale()) }}
                </p>
                <p v-else class="text-[#8B94A3]">Подробностей пока нет.</p>
            </div>

            <div class="lg:sticky lg:top-6 lg:col-span-1">
                <div class="rounded-[10px] border border-[#DCE6F0] bg-white p-4 sm:p-5">
                    <div class="flex items-center gap-4">
                        <div class="relative h-20 w-20 flex-shrink-0">
                            <svg viewBox="0 0 96 96" class="h-20 w-20 -rotate-90">
                                <circle cx="48" cy="48" r="42" fill="none" stroke="#E4ECF5" stroke-width="8" />
                                <circle
                                    cx="48" cy="48" r="42" fill="none" stroke="#0201a3" stroke-width="8"
                                    stroke-linecap="round"
                                    :stroke-dasharray="2 * Math.PI * 42"
                                    :stroke-dashoffset="ringOffset()"
                                    class="transition-[stroke-dashoffset] duration-500"
                                />
                            </svg>
                            <span class="font-heading absolute inset-0 flex items-center justify-center text-base font-extrabold text-brand-navy">
                                {{ progressPercent() }}%
                            </span>
                        </div>
                        <div class="flex flex-1 flex-col gap-1 text-sm text-[#5B6472]">
                            <span>Собрано <b class="block text-base text-[#101318]">{{ formatSom(props.case.allocated_minor) }}</b></span>
                            <span class="text-xs">Цель {{ formatSom(props.case.budget_minor) }}</span>
                        </div>
                    </div>

                    <div class="mt-3 text-sm text-[#8B94A3]">
                        Выплачено по кейсу: {{ formatSom(props.case.disbursed_minor) }}
                    </div>

                    <div v-if="flashSuccess()" class="mt-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ flashSuccess() }}
                    </div>

                    <form class="mt-6 space-y-4" @submit.prevent="submit">
                        <div>
                            <label class="font-heading mb-1.5 block text-sm font-bold text-[#101318]">Сумма, сом</label>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="preset in presetAmounts"
                                    :key="preset"
                                    type="button"
                                    class="font-heading rounded-lg border px-3 py-1.5 text-sm font-bold transition"
                                    :class="form.amount === preset
                                        ? 'border-brand-navy bg-[#EEF0FB] text-brand-navy'
                                        : 'border-[#DCE6F0] text-[#5B6472] hover:border-brand-cyan'"
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
                                class="mt-2 w-full rounded-lg border border-[#DCE6F0] px-3 py-2.5 text-base focus:border-brand-cyan focus:outline-none focus:ring-1 focus:ring-brand-cyan"
                                placeholder="Или своя сумма"
                            />
                            <p v-if="form.errors.amount" class="mt-1 text-sm text-red-600">{{ form.errors.amount }}</p>
                        </div>

                        <div>
                            <label class="font-heading mb-1.5 block text-sm font-bold text-[#101318]">Телефон</label>
                            <input
                                v-model="form.phone"
                                type="tel"
                                placeholder="+996 700 000 000"
                                class="w-full rounded-lg border border-[#DCE6F0] px-3 py-2.5 text-base focus:border-brand-cyan focus:outline-none focus:ring-1 focus:ring-brand-cyan"
                            />
                            <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</p>
                        </div>

                        <div>
                            <label class="font-heading mb-1.5 block text-sm font-bold text-[#101318]">Имя (необязательно)</label>
                            <input
                                v-model="form.name"
                                type="text"
                                placeholder="Как к вам обращаться"
                                class="w-full rounded-lg border border-[#DCE6F0] px-3 py-2.5 text-base focus:border-brand-cyan focus:outline-none focus:ring-1 focus:ring-brand-cyan"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                            <label class="mt-2 flex items-center gap-2 text-sm text-[#5B6472]">
                                <input v-model="form.show_name_publicly" type="checkbox" class="rounded border-[#DCE6F0] text-brand-navy focus:ring-brand-cyan" />
                                Показывать моё имя в списке донатов вместо номера телефона
                            </label>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="font-heading w-full rounded-lg bg-brand-navy px-4 py-3 font-bold text-white transition hover:bg-brand-navy/90 disabled:opacity-60"
                        >
                            {{ form.processing ? 'Отправляем…' : `Поддержать — ${form.amount || 0} сом` }}
                        </button>
                        <p class="text-center text-xs text-[#8B94A3]">
                            Демо-режим: реальный платёжный провайдер ещё не подключён, деньги не списываются.
                        </p>
                    </form>
                </div>

                <div v-if="recentDonations.length > 0" class="mt-4 rounded-[10px] border border-[#DCE6F0] bg-white p-4 sm:p-5">
                    <h2 class="font-heading text-sm font-bold text-[#101318]">Последние донаты</h2>
                    <ul class="mt-3 space-y-3">
                        <li
                            v-for="(donation, index) in recentDonations"
                            :key="index"
                            class="flex items-center gap-3 text-sm text-[#5B6472]"
                        >
                            <span
                                v-if="avatarInitial(donation.donorDisplay)"
                                class="font-heading flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-brand-navy text-xs font-bold text-white"
                            >
                                {{ avatarInitial(donation.donorDisplay) }}
                            </span>
                            <span v-else class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-[#E4ECF5] text-[#8B94A3]">
                                <svg viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4">
                                    <path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0 2c-4.4 0-8 2.2-8 5v1a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-1c0-2.8-3.6-5-8-5Z" />
                                </svg>
                            </span>
                            <span class="flex flex-1 flex-col">
                                <span class="text-[#8B94A3]">{{ formatDate(donation.created_at) }}</span>
                                <span v-if="donation.donorDisplay" class="text-xs text-[#8B94A3]">{{ donation.donorDisplay }}</span>
                            </span>
                            <span class="font-heading font-bold text-[#101318]">{{ formatSom(donation.amount_minor) }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

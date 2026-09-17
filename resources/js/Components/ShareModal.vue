<script setup>
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { formatSom, pickLocale } from '../money.js';
import { categoryLabel as categoryLabelFor, t } from '../i18n.js';

const props = defineProps({
    case: { type: Object, required: true },
});

const page = usePage();
const locale = () => page.props.locale;
const categoryLabel = (category) => categoryLabelFor(category, locale());

const isOpen = ref(false);
function open() {
    isOpen.value = true;
}
function close() {
    isOpen.value = false;
}
defineExpose({ open });

// Ссылка на конкретный кейс — то, чем реально делятся.
const shareUrl = computed(() => (typeof window !== 'undefined' ? window.location.href : ''));
const shareTitle = computed(() => pickLocale(props.case.title, locale()));
const canNativeShare = typeof navigator !== 'undefined' && typeof navigator.share === 'function';

const progressPercent = () =>
    props.case.budget_minor > 0
        ? Math.min(100, Math.round((props.case.allocated_minor / props.case.budget_minor) * 100))
        : 0;

const linkCopied = ref(false);
async function copyLink() {
    await navigator.clipboard.writeText(shareUrl.value);
    linkCopied.value = true;
    setTimeout(() => (linkCopied.value = false), 2000);
}

const telegramShareUrl = computed(
    () => `https://t.me/share/url?url=${encodeURIComponent(shareUrl.value)}&text=${encodeURIComponent(shareTitle.value)}`,
);
const whatsappShareUrl = computed(
    () => `https://wa.me/?text=${encodeURIComponent(shareTitle.value + ' ' + shareUrl.value)}`,
);
const facebookShareUrl = computed(
    () => `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareUrl.value)}`,
);
const emailShareUrl = computed(
    () => `mailto:?subject=${encodeURIComponent(shareTitle.value)}&body=${encodeURIComponent(shareUrl.value)}`,
);

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

    const statPill = drawPill(ctx, `${formatSom(props.case.allocated_minor)} ${t('story_collected_suffix', locale())}`, pad, cursorY, {
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
    ctx.fillText(`${t('story_goal_prefix', locale())} ${formatSom(props.case.budget_minor)}`, pad, cursorY);
    cursorY += STORY_GOAL_BLOCK;

    const ctaPill = drawPill(ctx, `${t('support', locale())} →`, pad, cursorY, {
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

// Одна кнопка вместо двух: на устройстве, где есть файловый Web Share,
// сразу предлагаем поделиться карточкой (в т.ч. в Instagram Stories);
// там, где его нет (почти весь десктоп), просто скачиваем картинку.
async function shareStoryCard() {
    if (canNativeShare) {
        await nativeShare();
    } else {
        await downloadStoryCard();
    }
}

async function shareOther() {
    try {
        await navigator.share({ title: shareTitle.value, url: shareUrl.value });
    } catch {
        // Пользователь закрыл системное меню — не ошибка.
    }
}
</script>

<template>
    <Teleport to="body">
        <div
            v-if="isOpen"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4"
            @click.self="close"
            @keydown.esc="close"
        >
            <div class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-[#DCE6F0] p-4 sm:p-5">
                    <h2 class="font-heading text-lg font-bold text-[#101318]">{{ t('share_modal_title', locale()) }}</h2>
                    <button
                        type="button"
                        class="flex h-8 w-8 items-center justify-center rounded-full text-[#8B94A3] transition hover:bg-[#F5F8FC] hover:text-[#101318]"
                        :aria-label="t('close', locale())"
                        @click="close"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="h-5 w-5">
                            <path d="M6 6l12 12M18 6L6 18" />
                        </svg>
                    </button>
                </div>

                <div class="p-4 sm:p-5">
                    <label class="mb-1.5 block text-xs font-medium text-[#8B94A3]">{{ t('share_your_link', locale()) }}</label>
                    <div class="flex items-center gap-2 rounded-lg border border-[#DCE6F0] p-1.5 pl-3">
                        <span class="flex-1 truncate text-sm text-[#5B6472]">{{ shareUrl }}</span>
                        <button
                            type="button"
                            class="font-heading flex-shrink-0 rounded-md bg-brand-navy px-3 py-2 text-xs font-bold text-white transition hover:bg-brand-navy/90"
                            @click="copyLink"
                        >
                            {{ linkCopied ? t('link_copied', locale()) : t('copy_link', locale()) }}
                        </button>
                    </div>

                    <p class="mt-4 text-sm text-[#5B6472]">{{ t('share_description', locale()) }}</p>

                    <div class="mt-4 grid grid-cols-2 gap-3">
                        <a
                            :href="telegramShareUrl"
                            target="_blank"
                            rel="noopener"
                            class="flex items-center gap-3 rounded-lg border border-[#DCE6F0] p-3 transition hover:border-brand-cyan"
                        >
                            <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-[#26A5E4] text-white">
                                <svg viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                                    <path d="M21.9 4.3 18.8 19c-.2 1-.9 1.3-1.7.8l-4.8-3.5-2.3 2.2c-.3.3-.5.5-1 .5l.4-5 9-8.1c.4-.3-.1-.5-.6-.2L6.4 12.7 1.7 11.3c-1-.3-1-1 .2-1.5L20.6 3c.9-.3 1.6.2 1.3 1.3Z" />
                                </svg>
                            </span>
                            <span class="text-sm font-medium text-[#101318]">Telegram</span>
                        </a>

                        <a
                            :href="whatsappShareUrl"
                            target="_blank"
                            rel="noopener"
                            class="flex items-center gap-3 rounded-lg border border-[#DCE6F0] p-3 transition hover:border-brand-cyan"
                        >
                            <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-[#25D366] text-white">
                                <svg viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                                    <path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5.1-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.1l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1-.2.2-.7.8-.8 1-.2.2-.3.2-.5.1-.2-.1-1-.4-1.9-1.2-.7-.6-1.2-1.4-1.3-1.6-.1-.2 0-.4.1-.5.1-.1.3-.3.4-.5.1-.1.2-.3.3-.4.1-.2 0-.4 0-.5 0-.1-.6-1.5-.9-2-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.5.1-.7.3-.2.2-1 .9-1 2.3s1 2.6 1.1 2.8c.1.2 2 3 4.8 4.2.7.3 1.2.5 1.6.6.7.2 1.3.2 1.8.1.5-.1 1.5-.6 1.8-1.2.2-.6.2-1.1.2-1.2-.1-.1-.3-.2-.5-.3Z" />
                                </svg>
                            </span>
                            <span class="text-sm font-medium text-[#101318]">WhatsApp</span>
                        </a>

                        <a
                            :href="facebookShareUrl"
                            target="_blank"
                            rel="noopener"
                            class="flex items-center gap-3 rounded-lg border border-[#DCE6F0] p-3 transition hover:border-brand-cyan"
                        >
                            <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-[#1877F2] text-white">
                                <svg viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5">
                                    <path d="M14 13.5h2.5l.5-3H14V8.8c0-.9.2-1.5 1.5-1.5H17V4.6c-.3 0-1.1-.1-2.2-.1-2.2 0-3.8 1.3-3.8 3.8V10.5H8.5v3H11V21h3v-7.5Z" />
                                </svg>
                            </span>
                            <span class="text-sm font-medium text-[#101318]">Facebook</span>
                        </a>

                        <a
                            :href="emailShareUrl"
                            class="flex items-center gap-3 rounded-lg border border-[#DCE6F0] p-3 transition hover:border-brand-cyan"
                        >
                            <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-[#5B6472] text-white">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                    <path d="M3 6h18v12H3z" />
                                    <path d="m3 7 9 6 9-6" />
                                </svg>
                            </span>
                            <span class="text-sm font-medium text-[#101318]">{{ t('share_email', locale()) }}</span>
                        </a>

                        <button
                            type="button"
                            :disabled="sharingImage || downloadingImage"
                            class="flex items-center gap-3 rounded-lg border border-[#DCE6F0] p-3 text-left transition hover:border-brand-cyan disabled:opacity-60"
                            @click="shareStoryCard"
                        >
                            <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#8a3ab9] via-[#e95950] to-[#fccc63] text-white">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="h-5 w-5">
                                    <rect x="3" y="3" width="18" height="18" rx="5" />
                                    <circle cx="12" cy="12" r="4" />
                                    <circle cx="17.5" cy="6.5" r="0.75" fill="currentColor" stroke="none" />
                                </svg>
                            </span>
                            <span class="text-sm font-medium text-[#101318]">
                                {{ sharingImage || downloadingImage ? t('preparing', locale()) : t('share_instagram_stories', locale()) }}
                            </span>
                        </button>

                        <button
                            v-if="canNativeShare"
                            type="button"
                            class="flex items-center gap-3 rounded-lg border border-[#DCE6F0] p-3 text-left transition hover:border-brand-cyan"
                            @click="shareOther"
                        >
                            <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-[#E4ECF5] text-[#5B6472]">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                    <circle cx="18" cy="5" r="2.5" />
                                    <circle cx="6" cy="12" r="2.5" />
                                    <circle cx="18" cy="19" r="2.5" />
                                    <path d="m8.2 10.8 7.6-4.1M8.2 13.2l7.6 4.1" />
                                </svg>
                            </span>
                            <span class="text-sm font-medium text-[#101318]">{{ t('share_other_ways', locale()) }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

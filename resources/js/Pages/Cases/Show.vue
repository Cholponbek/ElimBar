<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import PublicLayout from '../../Layouts/PublicLayout.vue';
import ShareModal from '../../Components/ShareModal.vue';
import { formatSom, pickLocale } from '../../money.js';
import { categoryLabel as categoryLabelFor, t } from '../../i18n.js';

const props = defineProps({
    case: { type: Object, required: true },
    recentDonations: { type: Array, default: () => [] },
});

const page = usePage();
const locale = () => page.props.locale;
const flashSuccess = () => page.props.flash?.success;
const flashError = () => page.props.flash?.error;

const shareModalRef = ref(null);

const categoryLabel = (category) => categoryLabelFor(category, locale());

// Карусель фото — case.photoUrls (новые кейсы, порядок как в админке) с
// откатом на одиночный photoUrl для кейсов, заведённых до карусели.
const photos = computed(() =>
    props.case.photoUrls?.length ? props.case.photoUrls : (props.case.photoUrl ? [props.case.photoUrl] : []),
);
const activePhoto = ref(0);
function prevPhoto() {
    activePhoto.value = (activePhoto.value - 1 + photos.value.length) % photos.value.length;
}
function nextPhoto() {
    activePhoto.value = (activePhoto.value + 1) % photos.value.length;
}

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

const donateFormOpen = ref(false);

// Плавающая нижняя панель на мобильном: показываем её только когда сама
// карточка доната (donateCardEl) прокручена мимо экрана — иначе на
// коротких экранах будет одновременно видно и карточку, и дублирующую
// панель снизу.
const donateCardEl = ref(null);
const donateCardVisible = ref(true);
let donateCardObserver = null;

onMounted(() => {
    if (typeof IntersectionObserver === 'undefined' || !donateCardEl.value) return;
    donateCardObserver = new IntersectionObserver(([entry]) => {
        donateCardVisible.value = entry.isIntersecting;
    });
    donateCardObserver.observe(donateCardEl.value);
});

onUnmounted(() => {
    donateCardObserver?.disconnect();
});

function focusDonateCard() {
    donateFormOpen.value = true;
    donateCardEl.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
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
        <Link href="/" class="text-sm text-[#5B6472] hover:text-brand-navy">&larr; {{ t('back_to_cases', locale()) }}</Link>

        <div class="font-heading mt-4 text-xs font-bold uppercase tracking-wider text-brand-cyan">
            {{ categoryLabel(props.case.category) }}
        </div>
        <h1 class="font-heading mt-2 text-xl font-bold leading-snug text-brand-navy sm:text-2xl">
            {{ pickLocale(props.case.title, locale()) }}
        </h1>

        <div class="mt-6 grid gap-6 pb-20 lg:grid-cols-3 lg:items-start lg:pb-0">
            <div class="lg:col-start-1 lg:col-span-2 lg:row-start-1">
                <!-- Карусель: одна фотография видна за раз, стрелки и точки
                     только когда их реально из чего выбирать. -->
                <div class="relative aspect-video w-full overflow-hidden rounded-[10px] bg-gradient-to-br from-brand-blue to-brand-navy">
                    <Transition name="photo-fade">
                        <img
                            v-if="photos.length"
                            :key="activePhoto"
                            :src="photos[activePhoto]"
                            :alt="pickLocale(props.case.title, locale())"
                            class="absolute inset-0 h-full w-full object-cover"
                        />
                    </Transition>

                    <template v-if="photos.length > 1">
                        <button
                            type="button"
                            class="absolute left-3 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/85 text-brand-navy shadow transition hover:bg-white"
                            :aria-label="t('previous_photo', locale())"
                            @click="prevPhoto"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                <path d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <button
                            type="button"
                            class="absolute right-3 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-white/85 text-brand-navy shadow transition hover:bg-white"
                            :aria-label="t('next_photo', locale())"
                            @click="nextPhoto"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                                <path d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                        <div class="absolute bottom-3 left-1/2 flex -translate-x-1/2 gap-1.5">
                            <button
                                v-for="(photo, index) in photos"
                                :key="index"
                                type="button"
                                class="h-1.5 rounded-full transition-all"
                                :class="index === activePhoto ? 'w-5 bg-white' : 'w-1.5 bg-white/50'"
                                :aria-label="`${t('photo', locale())} ${index + 1}`"
                                @click="activePhoto = index"
                            />
                        </div>
                    </template>
                </div>
            </div>

            <!-- Карточка доната: на мобильном идёт сразу под фото (до
                 описания — не нужно листать вниз, чтобы её найти), на lg —
                 отдельная колонка справа, растянутая на обе строки грида. -->
            <div class="lg:sticky lg:top-6 lg:col-start-3 lg:col-span-1 lg:row-start-1 lg:row-span-2 lg:max-h-[calc(100vh-3rem)] lg:overflow-y-auto">
                <!-- donateCardEl — якорь IntersectionObserver именно на
                     этой карточке (кнопка/форма), НЕ на всей колонке —
                     иначе, пока снизу ещё видна плашка "Последние донаты",
                     плавающая панель не появляется, хотя сама кнопка
                     "Поддержать" уже давно прокручена мимо экрана. -->
                <div ref="donateCardEl" class="rounded-[10px] border border-[#DCE6F0] bg-white p-4 sm:p-5">
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
                            <span>{{ t('collected', locale()) }} <b class="block text-base text-[#101318]">{{ formatSom(props.case.allocated_minor) }}</b></span>
                            <span class="text-xs">{{ t('goal', locale()) }} {{ formatSom(props.case.budget_minor) }}</span>
                        </div>
                    </div>

                    <div class="mt-3 text-sm text-[#8B94A3]">
                        {{ t('disbursed_for_case', locale()) }} {{ formatSom(props.case.disbursed_minor) }}
                    </div>

                    <div v-if="flashSuccess()" class="mt-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                        {{ flashSuccess() }}
                    </div>
                    <div v-if="flashError()" class="mt-6 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800">
                        {{ flashError() }}
                    </div>

                    <div class="mt-6 flex gap-2">
                        <button
                            v-if="!donateFormOpen"
                            type="button"
                            class="font-heading flex-1 rounded-lg bg-brand-navy px-4 py-3 font-bold text-white transition hover:bg-brand-navy/90"
                            @click="donateFormOpen = true"
                        >
                            {{ t('support', locale()) }}
                        </button>
                        <button
                            type="button"
                            class="font-heading flex-1 rounded-lg border border-[#DCE6F0] px-4 py-3 font-bold text-[#101318] transition hover:border-brand-cyan"
                            @click="shareModalRef.open()"
                        >
                            {{ t('share', locale()) }}
                        </button>
                    </div>

                    <form v-if="donateFormOpen" class="mt-4 space-y-4" @submit.prevent="submit">
                        <div>
                            <label class="font-heading mb-1.5 block text-sm font-bold text-[#101318]">{{ t('amount_som', locale()) }}</label>
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
                                :placeholder="t('custom_amount_placeholder', locale())"
                            />
                            <p v-if="form.errors.amount" class="mt-1 text-sm text-red-600">{{ form.errors.amount }}</p>
                        </div>

                        <div>
                            <label class="font-heading mb-1.5 block text-sm font-bold text-[#101318]">{{ t('phone', locale()) }}</label>
                            <input
                                v-model="form.phone"
                                type="tel"
                                :placeholder="t('phone_placeholder', locale())"
                                class="w-full rounded-lg border border-[#DCE6F0] px-3 py-2.5 text-base focus:border-brand-cyan focus:outline-none focus:ring-1 focus:ring-brand-cyan"
                            />
                            <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</p>
                        </div>

                        <div>
                            <label class="font-heading mb-1.5 block text-sm font-bold text-[#101318]">{{ t('name_optional', locale()) }}</label>
                            <input
                                v-model="form.name"
                                type="text"
                                :placeholder="t('name_placeholder', locale())"
                                class="w-full rounded-lg border border-[#DCE6F0] px-3 py-2.5 text-base focus:border-brand-cyan focus:outline-none focus:ring-1 focus:ring-brand-cyan"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                            <label class="mt-2 flex items-center gap-2 text-sm text-[#5B6472]">
                                <input v-model="form.show_name_publicly" type="checkbox" class="rounded border-[#DCE6F0] text-brand-navy focus:ring-brand-cyan" />
                                {{ t('show_name_publicly', locale()) }}
                            </label>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="font-heading w-full rounded-lg bg-brand-navy px-4 py-3 font-bold text-white transition hover:bg-brand-navy/90 disabled:opacity-60"
                        >
                            {{ form.processing ? t('sending', locale()) : `${t('support', locale())} — ${form.amount || 0} сом` }}
                        </button>
                    </form>
                </div>

                <div v-if="recentDonations.length > 0" class="mt-4 rounded-[10px] border border-[#DCE6F0] bg-white p-4 sm:p-5">
                    <h2 class="font-heading text-sm font-bold text-[#101318]">{{ t('recent_donations', locale()) }}</h2>
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

            <!-- "Поделиться" здесь не нужен отдельной кнопкой — она уже
                 есть в карточке доната и в плавающей нижней панели,
                 доступ к модалке шаринга и так под рукой везде. -->
            <div class="lg:col-start-1 lg:col-span-2 lg:row-start-2">
                <p v-if="pickLocale(props.case.story, locale())" class="whitespace-pre-line text-sm leading-relaxed text-[#374151] sm:text-base">
                    {{ pickLocale(props.case.story, locale()) }}
                </p>
                <p v-else class="text-sm text-[#8B94A3]">{{ t('no_details_yet', locale()) }}</p>
            </div>
        </div>

        <!-- Плавающая нижняя панель — только на мобильном (на lg карточка
             доната и так sticky-видна сбоку). Появляется, когда сама
             карточка (donateCardEl) уходит за пределы экрана при
             прокрутке описания, и остаётся на виду, пока пользователь не
             вернётся к ней — не перекрывает текст описания, потому что
             это просто тонкая полоса снизу, а не сама форма (снизу у
             сетки есть pb-20 именно под неё). -->
        <div
            v-if="!donateCardVisible"
            class="fixed inset-x-0 bottom-0 z-40 border-t border-[#DCE6F0] bg-white p-3 shadow-[0_-4px_16px_rgba(2,1,163,0.12)] lg:hidden"
        >
            <div class="flex items-center gap-2">
                <div class="flex flex-1 flex-col text-sm text-[#5B6472]">
                    <span class="font-heading font-bold text-[#101318]">{{ formatSom(props.case.allocated_minor) }}</span>
                    <span class="text-xs">{{ progressPercent() }}% · {{ t('goal', locale()) }} {{ formatSom(props.case.budget_minor) }}</span>
                </div>
                <button
                    type="button"
                    class="font-heading rounded-lg border border-[#DCE6F0] px-4 py-2.5 font-bold text-[#101318] transition hover:border-brand-cyan"
                    @click="shareModalRef.open()"
                >
                    {{ t('share', locale()) }}
                </button>
                <button
                    type="button"
                    class="font-heading rounded-lg bg-brand-navy px-5 py-2.5 font-bold text-white transition hover:bg-brand-navy/90"
                    @click="focusDonateCard"
                >
                    {{ t('support', locale()) }}
                </button>
            </div>
        </div>

        <ShareModal ref="shareModalRef" :case="props.case" />
    </PublicLayout>
</template>

<style scoped>
/* Кроссфейд между фото карусели: оба кадра — position:absolute inset-0
   внутри одного relative-контейнера, так что пока новое проявляется,
   старое ещё видно под ним на том же месте — плавный переход, а не
   резкая смена. */
.photo-fade-enter-active,
.photo-fade-leave-active {
    transition: opacity 0.35s ease;
}

.photo-fade-enter-from,
.photo-fade-leave-to {
    opacity: 0;
}
</style>

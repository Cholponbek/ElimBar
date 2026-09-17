<script setup>
import { ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { formatSom } from '../money.js';
import { t } from '../i18n.js';

const props = defineProps({
    caseId: { type: Number, required: true },
    donationsCount: { type: Number, default: 0 },
});

const page = usePage();
const locale = () => page.props.locale;

const isOpen = ref(false);
const activeTab = ref('recent');
const donations = ref([]);
const loading = ref(false);

async function fetchDonations() {
    loading.value = true;
    try {
        const response = await fetch(`/cases/${props.caseId}/donations?sort=${activeTab.value === 'top' ? 'top' : 'recent'}`);
        const body = await response.json();
        donations.value = body.data;
    } finally {
        loading.value = false;
    }
}

// open('top') — вызывается кнопкой "Смотреть топ" под инлайн-списком,
// сразу открывает модалку на нужной вкладке, а не всегда на "Все".
function open(tab = 'recent') {
    activeTab.value = tab;
    isOpen.value = true;
    fetchDonations();
}
function close() {
    isOpen.value = false;
}
defineExpose({ open });

watch(activeTab, () => {
    if (isOpen.value) fetchDonations();
});

const isMaskedPhone = (display) => !display || display.startsWith('+') || display.startsWith('*');
const avatarInitial = (display) => (isMaskedPhone(display) ? null : display.trim().charAt(0).toUpperCase());

const formatDate = (isoString) =>
    new Date(isoString).toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit' });
</script>

<template>
    <Teleport to="body">
        <div
            v-if="isOpen"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4"
            @click.self="close"
            @keydown.esc="close"
        >
            <div class="flex max-h-[85vh] w-full max-w-md flex-col overflow-hidden rounded-2xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-[#DCE6F0] p-4 sm:p-5">
                    <h2 class="font-heading flex items-center gap-2 text-lg font-bold text-[#101318]">
                        {{ t('donations_modal_title', locale()) }}
                        <span class="rounded-full bg-[#F5F8FC] px-2 py-0.5 text-xs font-bold text-[#5B6472]">{{ donationsCount }}</span>
                    </h2>
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

                <div class="flex gap-2 border-b border-[#DCE6F0] p-3">
                    <button
                        type="button"
                        class="font-heading rounded-full px-4 py-1.5 text-sm font-bold transition"
                        :class="activeTab === 'recent' ? 'bg-brand-navy text-white' : 'text-[#5B6472] hover:bg-[#F5F8FC]'"
                        @click="activeTab = 'recent'"
                    >
                        {{ t('donations_tab_all', locale()) }}
                    </button>
                    <button
                        type="button"
                        class="font-heading rounded-full px-4 py-1.5 text-sm font-bold transition"
                        :class="activeTab === 'top' ? 'bg-brand-navy text-white' : 'text-[#5B6472] hover:bg-[#F5F8FC]'"
                        @click="activeTab = 'top'"
                    >
                        {{ t('donations_tab_top', locale()) }}
                    </button>
                </div>

                <div class="overflow-y-auto p-4 sm:p-5">
                    <p v-if="loading" class="py-6 text-center text-sm text-[#8B94A3]">{{ t('loading', locale()) }}</p>
                    <p v-else-if="donations.length === 0" class="py-6 text-center text-sm text-[#8B94A3]">{{ t('no_donations_yet', locale()) }}</p>
                    <ul v-else class="space-y-3">
                        <li
                            v-for="(donation, index) in donations"
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
    </Teleport>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import PublicLayout from '../../Layouts/PublicLayout.vue';
import { formatSom, pickLocale } from '../../money.js';

defineProps({
    cases: { type: Array, required: true },
});

const page = usePage();
const locale = () => page.props.locale;

const categoryLabel = (category) => ({
    medical: 'Лечение',
    winter_food: 'Зимняя продуктовая помощь',
    fund_project: 'Проект фонда',
}[category] ?? category);

const progress = (c) => (c.budget_minor > 0 ? Math.min(100, Math.round((c.allocated_minor / c.budget_minor) * 100)) : 0);
</script>

<template>
    <PublicLayout>
        <h1 class="font-heading text-2xl font-bold text-brand-navy">Кейсы, которым нужна помощь</h1>
        <p class="mt-1 text-[#5B6472]">Каждый сом привязан к конкретному кейсу — публичный отчёт собирается автоматически.</p>

        <div v-if="cases.length === 0" class="mt-10 rounded-lg border border-dashed border-[#DCE6F0] p-10 text-center text-[#8B94A3]">
            Пока нет активных кейсов.
        </div>

        <div v-else class="mt-8 grid gap-5 sm:grid-cols-2">
            <Link
                v-for="c in cases"
                :key="c.id"
                :href="`/cases/${c.id}`"
                class="block overflow-hidden rounded-[10px] border border-[#DCE6F0] bg-white transition hover:border-brand-cyan"
            >
                <div class="h-1 bg-brand-cyan" />
                <img
                    v-if="c.photoUrl"
                    :src="c.photoUrl"
                    :alt="pickLocale(c.title, locale())"
                    class="aspect-video w-full object-cover"
                />
                <div class="flex flex-col gap-2.5 p-4">
                    <div class="font-heading text-[11px] font-bold uppercase tracking-wider text-brand-cyan">
                        {{ categoryLabel(c.category) }}
                    </div>
                    <h2 class="font-heading text-[15.5px] font-bold leading-snug text-[#101318]">
                        {{ pickLocale(c.title, locale()) }}
                    </h2>

                    <div class="flex items-center gap-2.5">
                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-[#E4ECF5]">
                            <div class="h-full bg-brand-navy" :style="{ width: progress(c) + '%' }" />
                        </div>
                        <span class="font-heading text-[13px] font-bold text-brand-navy">{{ progress(c) }}%</span>
                    </div>
                    <div class="flex justify-between border-t border-[#EEF3F8] pt-2 text-[12.5px] text-[#5B6472]">
                        <span>Собрано <b class="text-[#101318]">{{ formatSom(c.allocated_minor) }}</b></span>
                        <span>Цель <b class="text-[#101318]">{{ formatSom(c.budget_minor) }}</b></span>
                    </div>
                </div>
            </Link>
        </div>
    </PublicLayout>
</template>

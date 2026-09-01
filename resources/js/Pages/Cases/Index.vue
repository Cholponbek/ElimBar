<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import PublicLayout from '../../Layouts/PublicLayout.vue';
import { formatSom, pickLocale } from '../../money.js';

const props = defineProps({
    cases: { type: Array, required: true },
    stats: { type: Object, required: true },
});

const page = usePage();
const locale = () => page.props.locale;

const categoryLabel = (category) => ({
    medical: 'Лечение',
    winter_food: 'Зимняя продуктовая помощь',
    fund_project: 'Проект фонда',
}[category] ?? category);

const progress = (c) => (c.budget_minor > 0 ? Math.min(100, Math.round((c.allocated_minor / c.budget_minor) * 100)) : 0);

const donationsLabel = (n) => {
    const mod10 = n % 10;
    const mod100 = n % 100;
    if (mod10 === 1 && mod100 !== 11) return 'донат';
    if ([2, 3, 4].includes(mod10) && ![12, 13, 14].includes(mod100)) return 'доната';
    return 'донатов';
};
</script>

<template>
    <PublicLayout>
        <template #hero>
            <section class="border-b border-brand-cyan/30 bg-brand-navy">
                <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8 lg:py-20">
                    <span class="font-heading inline-block rounded-full bg-white/10 px-3.5 py-1.5 text-xs font-bold uppercase tracking-wider text-brand-cyan">
                        Общественный благотворительный фонд
                    </span>
                    <h1 class="font-heading mt-5 max-w-2xl text-3xl font-extrabold leading-tight text-white sm:text-4xl lg:text-5xl">
                        Элим, барсыңбы?!
                    </h1>
                    <p class="mt-4 max-w-xl text-base text-white/75 sm:text-lg">
                        Каждый сом привязан к конкретному кейсу — публичный отчёт собирается автоматически.
                    </p>
                    <div class="mt-7 flex flex-wrap items-center gap-3">
                        <a
                            href="#cases"
                            class="font-heading rounded-lg bg-brand-cyan px-5 py-3 text-sm font-bold text-brand-navy transition hover:bg-white"
                        >
                            Смотреть кейсы
                        </a>
                        <Link
                            href="/help"
                            class="font-heading rounded-lg border border-white/25 px-5 py-3 text-sm font-bold text-white transition hover:border-brand-cyan hover:text-brand-cyan"
                        >
                            Нужна помощь?
                        </Link>
                    </div>

                    <dl class="mt-10 grid max-w-xl grid-cols-3 gap-4 border-t border-white/15 pt-6 sm:mt-12 sm:pt-7">
                        <div>
                            <dt class="text-xs text-white/60">Активных кейсов</dt>
                            <dd class="font-heading mt-1 text-xl font-extrabold text-white sm:text-2xl">{{ stats.activeCases }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-white/60">Собрано публично</dt>
                            <dd class="font-heading mt-1 text-xl font-extrabold text-white sm:text-2xl">{{ formatSom(stats.raisedMinor) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-white/60">Донатов</dt>
                            <dd class="font-heading mt-1 text-xl font-extrabold text-white sm:text-2xl">{{ stats.donationsCount }}</dd>
                        </div>
                    </dl>
                </div>
            </section>
        </template>

        <h2 id="cases" class="font-heading scroll-mt-6 text-xl font-bold text-brand-navy sm:text-2xl">Кейсы, которым нужна помощь</h2>

        <div v-if="cases.length === 0" class="mt-6 rounded-lg border border-dashed border-[#DCE6F0] p-10 text-center text-[#8B94A3]">
            Пока нет активных кейсов.
        </div>

        <div v-else class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <Link
                v-for="c in cases"
                :key="c.id"
                :href="`/cases/${c.id}`"
                class="block overflow-hidden rounded-[10px] border border-[#DCE6F0] bg-white transition hover:border-brand-cyan hover:shadow-[0_4px_16px_rgba(2,1,163,0.08)]"
            >
                <div class="relative">
                    <div class="h-1 bg-brand-cyan" />
                    <img
                        v-if="c.photoUrl"
                        :src="c.photoUrl"
                        :alt="pickLocale(c.title, locale())"
                        class="aspect-video w-full object-cover"
                    />
                    <div v-else class="aspect-video w-full bg-gradient-to-br from-brand-blue to-brand-navy" />
                    <span
                        v-if="c.donationsCount > 0"
                        class="font-heading absolute bottom-2 right-2 rounded-full bg-brand-navy/85 px-2.5 py-1 text-[11px] font-bold text-white backdrop-blur-sm"
                    >
                        {{ c.donationsCount }} {{ donationsLabel(c.donationsCount) }}
                    </span>
                </div>
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

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
        <h1 class="text-2xl font-semibold">Кейсы, которым нужна помощь</h1>
        <p class="mt-1 text-stone-500">Каждый сом привязан к конкретному кейсу.</p>

        <div v-if="cases.length === 0" class="mt-10 rounded-lg border border-dashed border-stone-300 p-10 text-center text-stone-400">
            Пока нет активных кейсов.
        </div>

        <div v-else class="mt-8 grid gap-6 sm:grid-cols-2">
            <Link
                v-for="c in cases"
                :key="c.id"
                :href="`/cases/${c.id}`"
                class="block overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm transition hover:shadow-md"
            >
                <img
                    v-if="c.photoUrl"
                    :src="c.photoUrl"
                    :alt="pickLocale(c.title, locale())"
                    class="aspect-video w-full object-cover"
                />
                <div class="p-5">
                    <div class="text-xs font-medium uppercase tracking-wide text-amber-600">
                        {{ categoryLabel(c.category) }}
                    </div>
                    <h2 class="mt-2 text-lg font-semibold leading-snug">
                        {{ pickLocale(c.title, locale()) }}
                    </h2>

                    <div class="mt-4">
                        <div class="h-2 overflow-hidden rounded-full bg-stone-100">
                            <div class="h-full bg-amber-500" :style="{ width: progress(c) + '%' }" />
                        </div>
                        <div class="mt-2 flex justify-between text-sm text-stone-500">
                            <span>{{ formatSom(c.allocated_minor) }} собрано</span>
                            <span>из {{ formatSom(c.budget_minor) }}</span>
                        </div>
                    </div>
                </div>
            </Link>
        </div>
    </PublicLayout>
</template>

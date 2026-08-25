<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import PublicLayout from '../../Layouts/PublicLayout.vue';
import { formatSom, pickLocale } from '../../money.js';

const props = defineProps({
    case: { type: Object, required: true },
});

const page = usePage();
const locale = () => page.props.locale;

const categoryLabel = (category) => ({
    medical: 'Лечение',
    winter_food: 'Зимняя продуктовая помощь',
}[category] ?? category);

const progressPercent = () =>
    props.case.budget_minor > 0
        ? Math.min(100, Math.round((props.case.allocated_minor / props.case.budget_minor) * 100))
        : 0;
</script>

<template>
    <PublicLayout>
        <Link href="/" class="text-sm text-stone-500 hover:text-stone-700">&larr; Все кейсы</Link>

        <div class="mt-4 text-xs font-medium uppercase tracking-wide text-amber-600">
            {{ categoryLabel(props.case.category) }}
        </div>
        <h1 class="mt-2 text-2xl font-semibold leading-snug">
            {{ pickLocale(props.case.title, locale()) }}
        </h1>

        <p v-if="pickLocale(props.case.story, locale())" class="mt-4 whitespace-pre-line leading-relaxed text-stone-700">
            {{ pickLocale(props.case.story, locale()) }}
        </p>

        <div class="mt-8 rounded-xl border border-stone-200 bg-white p-5">
            <div class="h-2 overflow-hidden rounded-full bg-stone-100">
                <div class="h-full bg-amber-500" :style="{ width: progressPercent() + '%' }" />
            </div>
            <div class="mt-2 flex justify-between text-sm text-stone-500">
                <span>{{ formatSom(props.case.allocated_minor) }} собрано</span>
                <span>из {{ formatSom(props.case.budget_minor) }}</span>
            </div>

            <div class="mt-3 text-sm text-stone-500">
                Выплачено по кейсу: {{ formatSom(props.case.disbursed_minor) }}
            </div>

            <button
                type="button"
                disabled
                title="Оплата подключается на следующем этапе"
                class="mt-6 w-full cursor-not-allowed rounded-lg bg-amber-300 px-4 py-3 font-medium text-white"
            >
                Поддержать (скоро)
            </button>
        </div>
    </PublicLayout>
</template>

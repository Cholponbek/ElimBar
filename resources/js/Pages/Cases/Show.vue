<script setup>
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

const categoryLabel = (category) => ({
    medical: 'Лечение',
    winter_food: 'Зимняя продуктовая помощь',
}[category] ?? category);

const progressPercent = () =>
    props.case.budget_minor > 0
        ? Math.min(100, Math.round((props.case.allocated_minor / props.case.budget_minor) * 100))
        : 0;

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
                            class="flex justify-between text-sm text-stone-600"
                        >
                            <span class="text-stone-400">{{ formatDate(donation.created_at) }}</span>
                            <span class="font-medium">{{ formatSom(donation.amount_minor) }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

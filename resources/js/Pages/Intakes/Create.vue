<script setup>
import { useForm, usePage } from '@inertiajs/vue3';
import PublicLayout from '../../Layouts/PublicLayout.vue';

const page = usePage();
const flashSuccess = () => page.props.flash?.success;

const form = useForm({
    full_name: '',
    phone: '',
    category: 'medical',
    description: '',
    requested_amount: null,
});

function submit() {
    form.post('/help', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <PublicLayout>
        <h1 class="text-2xl font-semibold">Нужна помощь?</h1>
        <p class="mt-1 text-stone-500">
            Расскажите о ситуации — сотрудник фонда свяжется с вами и, если всё
            подтвердится, кейс появится на сайте.
        </p>

        <div v-if="flashSuccess()" class="mt-6 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ flashSuccess() }}
        </div>

        <form class="mt-8 max-w-lg space-y-4" @submit.prevent="submit">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-stone-700">Ваше ФИО</label>
                <input
                    v-model="form.full_name"
                    type="text"
                    class="w-full rounded-lg border border-stone-300 px-3 py-2.5 text-base focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
                />
                <p v-if="form.errors.full_name" class="mt-1 text-sm text-red-600">{{ form.errors.full_name }}</p>
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

            <div>
                <label class="mb-1.5 block text-sm font-medium text-stone-700">Категория</label>
                <select
                    v-model="form.category"
                    class="w-full rounded-lg border border-stone-300 px-3 py-2.5 text-base focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
                >
                    <option value="medical">Лечение</option>
                    <option value="winter_food">Зимняя продуктовая помощь</option>
                </select>
                <p v-if="form.errors.category" class="mt-1 text-sm text-red-600">{{ form.errors.category }}</p>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-stone-700">Опишите ситуацию</label>
                <textarea
                    v-model="form.description"
                    rows="5"
                    class="w-full rounded-lg border border-stone-300 px-3 py-2.5 text-base focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
                />
                <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-stone-700">Нужная сумма, сом (если известна)</label>
                <input
                    v-model.number="form.requested_amount"
                    type="number"
                    min="1"
                    step="1"
                    class="w-full rounded-lg border border-stone-300 px-3 py-2.5 text-base focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500"
                />
                <p v-if="form.errors.requested_amount" class="mt-1 text-sm text-red-600">{{ form.errors.requested_amount }}</p>
            </div>

            <button
                type="submit"
                :disabled="form.processing"
                class="w-full rounded-lg bg-amber-500 px-4 py-3 font-medium text-white transition hover:bg-amber-600 disabled:opacity-60"
            >
                {{ form.processing ? 'Отправляем…' : 'Отправить заявку' }}
            </button>
        </form>
    </PublicLayout>
</template>

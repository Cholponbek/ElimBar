<script setup>
import { ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { t } from '../i18n.js';
import { pickLocale } from '../money.js';

const page = usePage();
const locale = () => page.props.locale;

// preserveScroll/preserveState — переключение языка не должно прыгать
// наверх страницы или сбрасывать введённые в форму доната данные.
function switchLocale(newLocale) {
    if (newLocale === locale()) return;
    router.post('/locale', { locale: newLocale }, { preserveScroll: true, preserveState: true });
}

// navCases — общий проп из HandleInertiaRequests (не только на витрине,
// хедер один на все страницы, включая саму карточку кейса).
const supportMenuOpen = ref(false);
</script>

<template>
    <div class="min-h-screen bg-[#F5F8FC] text-stone-900">
        <header class="border-b-[3px] border-brand-cyan bg-brand-navy">
            <!-- На мобильном — 3 равные колонки (лого по центру, независимо
                 от разной ширины "Нужна помощь?" слева и переключателя
                 языка справа), на sm: и выше — обычный flex с лого слева
                 и всем остальным одной группой справа, как было. -->
            <div class="mx-auto grid grid-cols-[1fr_auto_1fr] items-center gap-2 px-4 py-3.5 sm:flex sm:max-w-6xl sm:justify-between sm:gap-4 sm:px-6 lg:px-8">
                <Link href="/help" class="text-sm font-medium text-white hover:text-brand-cyan sm:hidden">
                    {{ t('nav_help', locale()) }}
                </Link>

                <Link href="/" class="justify-self-center sm:order-first sm:justify-self-auto">
                    <img src="/images/elimbar-logo-white.png" alt="Элим, барсыңбы?!" class="h-9 w-auto" />
                </Link>

                <div class="flex items-center justify-end gap-4 sm:justify-normal">
                    <!-- Только от sm: и выше — на мобильном кнопка вплотную
                         к лого смотрится тесно, а выпадающее меню под ней
                         на узком экране не даёт выигрыша (кейсы и так
                         первым делом на витрине). -->
                    <div class="relative hidden sm:block">
                        <button
                            type="button"
                            class="font-heading flex items-center gap-1 rounded-lg bg-brand-cyan px-3 py-1.5 text-sm font-bold text-brand-navy transition hover:bg-white"
                            :aria-expanded="supportMenuOpen"
                            @click="supportMenuOpen = !supportMenuOpen"
                        >
                            {{ t('support', locale()) }}
                            <svg
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
                                class="h-3.5 w-3.5 transition-transform"
                                :class="{ 'rotate-180': supportMenuOpen }"
                            >
                                <path d="m6 9 6 6 6-6" />
                            </svg>
                        </button>

                        <template v-if="supportMenuOpen">
                            <!-- Прозрачная подложка на весь экран — клик вне
                                 панели закрывает меню, без document-level
                                 слушателей. -->
                            <div class="fixed inset-0 z-40" @click="supportMenuOpen = false" />
                            <!-- На мобильном кнопка стоит близко к левому краю — absolute
                                 right-0/left-0 от неё в любую сторону выталкивает панель
                                 за пределы экрана. fixed + inset-x с отступами от краёв
                                 viewport решает это независимо от того, где кнопка; на sm:
                                 и выше места достаточно, чтобы просто привязаться к кнопке. -->
                            <div class="fixed inset-x-4 top-20 z-50 mx-auto max-w-sm overflow-hidden rounded-xl bg-white text-left shadow-2xl sm:absolute sm:inset-x-auto sm:left-0 sm:top-full sm:mx-0 sm:mt-2 sm:w-80 sm:max-w-none">
                                <div class="border-b border-[#DCE6F0] p-3">
                                    <p class="font-heading text-sm font-bold text-[#101318]">{{ t('support_menu_title', locale()) }}</p>
                                </div>
                                <ul class="max-h-80 overflow-y-auto p-2">
                                    <li v-for="c in page.props.navCases" :key="c.id">
                                        <Link
                                            :href="`/cases/${c.id}`"
                                            class="block rounded-lg px-3 py-2 text-sm text-[#101318] transition hover:bg-[#F5F8FC]"
                                            @click="supportMenuOpen = false"
                                        >
                                            {{ pickLocale(c.title, locale()) }}
                                        </Link>
                                    </li>
                                    <li v-if="!page.props.navCases?.length" class="px-3 py-4 text-center text-sm text-[#8B94A3]">
                                        {{ t('no_active_cases', locale()) }}
                                    </li>
                                </ul>
                                <div class="border-t border-[#DCE6F0] p-2">
                                    <Link
                                        href="/"
                                        class="font-heading block rounded-lg px-3 py-2 text-center text-sm font-bold text-brand-navy transition hover:bg-[#F5F8FC]"
                                        @click="supportMenuOpen = false"
                                    >
                                        {{ t('view_all_cases', locale()) }}
                                    </Link>
                                </div>
                            </div>
                        </template>
                    </div>
                    <Link href="/help" class="hidden text-sm font-medium text-white hover:text-brand-cyan sm:inline">
                        {{ t('nav_help', locale()) }}
                    </Link>
                    <span class="hidden text-sm text-white/70 sm:inline">{{ t('org_line', locale()) }}</span>
                    <div class="flex items-center gap-1 rounded-full bg-white/10 p-0.5 text-xs font-bold">
                        <button
                            type="button"
                            class="rounded-full px-2 py-1 transition"
                            :class="locale() === 'ky' ? 'bg-brand-cyan text-brand-navy' : 'text-white/70 hover:text-white'"
                            @click="switchLocale('ky')"
                        >
                            KY
                        </button>
                        <button
                            type="button"
                            class="rounded-full px-2 py-1 transition"
                            :class="locale() === 'ru' ? 'bg-brand-cyan text-brand-navy' : 'text-white/70 hover:text-white'"
                            @click="switchLocale('ru')"
                        >
                            RU
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <slot name="hero" />

        <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
            <slot />
        </main>

        <footer class="mt-16 border-t border-[#DCE6F0] py-8 text-center text-sm text-[#8B94A3]">
            {{ t('footer_tagline', locale()) }}
        </footer>
    </div>
</template>

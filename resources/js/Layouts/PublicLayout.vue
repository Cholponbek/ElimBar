<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { t } from '../i18n.js';

const page = usePage();
const locale = () => page.props.locale;

// preserveScroll/preserveState — переключение языка не должно прыгать
// наверх страницы или сбрасывать введённые в форму доната данные.
function switchLocale(newLocale) {
    if (newLocale === locale()) return;
    router.post('/locale', { locale: newLocale }, { preserveScroll: true, preserveState: true });
}
</script>

<template>
    <div class="min-h-screen bg-[#F5F8FC] text-stone-900">
        <header class="border-b-[3px] border-brand-cyan bg-brand-navy">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3.5 sm:px-6 lg:px-8">
                <Link href="/">
                    <img src="/images/elimbar-logo-white.png" alt="Элим, барсыңбы?!" class="h-9 w-auto" />
                </Link>
                <div class="flex items-center gap-4">
                    <Link href="/help" class="text-sm font-medium text-white hover:text-brand-cyan">
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

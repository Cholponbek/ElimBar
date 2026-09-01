<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-[#F5F8FC] text-stone-900">
        <header class="border-b-[3px] border-brand-cyan bg-brand-navy">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3.5 sm:px-6 lg:px-8">
                <Link href="/">
                    <img src="/images/elimbar-logo-white.png" alt="Элим, барсыңбы?!" class="h-7 w-auto" />
                </Link>
                <div class="flex items-center gap-4">
                    <Link href="/help" class="text-sm font-medium text-white hover:text-brand-cyan">
                        Нужна помощь?
                    </Link>
                    <span class="hidden text-sm text-white/70 sm:inline">ОБФ · Бишкек, КР</span>
                </div>
            </div>
        </header>

        <slot name="hero" />

        <main class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
            <slot />
        </main>

        <footer class="mt-16 border-t border-[#DCE6F0] py-8 text-center text-sm text-[#8B94A3]">
            Каждый сом привязан к конкретному кейсу — публичный отчёт собирается автоматически.
        </footer>
    </div>
</template>

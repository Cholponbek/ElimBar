<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="min-h-screen bg-stone-50 text-stone-900">
        <header class="border-b border-stone-200 bg-white">
            <div class="mx-auto flex max-w-4xl items-center justify-between px-4 py-4">
                <Link href="/" class="text-lg font-semibold tracking-tight">
                    Элим, барсыңбы?!
                </Link>
                <div class="flex items-center gap-4">
                    <Link href="/help" class="text-sm font-medium text-amber-600 hover:text-amber-700">
                        Нужна помощь?
                    </Link>
                    <span class="text-sm text-stone-500">ОБФ · Бишкек, КР</span>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-4xl px-4 py-8">
            <slot />
        </main>

        <footer class="mt-16 border-t border-stone-200 py-8 text-center text-sm text-stone-400">
            Каждый сом привязан к конкретному кейсу — публичный отчёт собирается автоматически.
        </footer>
    </div>
</template>

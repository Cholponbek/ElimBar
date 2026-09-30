<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import PublicLayout from '../../Layouts/PublicLayout.vue';
import { formatSom, pickLocale } from '../../money.js';
import { categoryLabel as categoryLabelFor, t } from '../../i18n.js';

const props = defineProps({
    case: { type: Object, required: true },
});

const page = usePage();
const locale = () => page.props.locale;
const categoryLabel = (category) => categoryLabelFor(category, locale());

const formatDate = (isoString) =>
    isoString ? new Date(isoString).toLocaleDateString('ru-RU', { day: '2-digit', month: 'long', year: 'numeric' }) : null;
</script>

<template>
    <PublicLayout>
        <Link href="/" class="text-sm text-[#5B6472] hover:text-brand-navy">&larr; {{ t('back_to_cases', locale()) }}</Link>

        <div class="font-heading mt-4 text-xs font-bold uppercase tracking-wider text-brand-cyan">
            {{ categoryLabel(props.case.category) }}
        </div>
        <div class="mt-2 flex flex-wrap items-center gap-3">
            <h1 class="font-heading text-xl font-bold leading-snug text-brand-navy sm:text-2xl">
                {{ pickLocale(props.case.title, locale()) }}
            </h1>
            <span class="font-heading rounded-full bg-[#F5F8FC] px-3 py-1 text-xs font-bold text-[#5B6472]">
                {{ t('status_closed', locale()) }}
            </span>
        </div>

        <div
            v-if="props.case.photoUrl"
            class="mt-6 aspect-video w-full overflow-hidden rounded-[10px] bg-gradient-to-br from-brand-blue to-brand-navy"
        >
            <img :src="props.case.photoUrl" :alt="pickLocale(props.case.title, locale())" class="h-full w-full object-cover" />
        </div>

        <div class="mt-8 grid gap-6 sm:grid-cols-2">
            <section class="rounded-[10px] border border-[#DCE6F0] bg-white p-5">
                <h2 class="font-heading text-sm font-bold text-[#101318]">{{ t('report_case_heading', locale()) }}</h2>
                <dl class="mt-4 space-y-3 text-sm text-[#3D4655]">
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-[#8B94A3]">{{ t('report_short_description', locale()) }}</dt>
                        <dd class="mt-0.5">{{ pickLocale(props.case.story, locale()) || '—' }}</dd>
                    </div>
                    <div class="flex justify-between border-t border-[#EEF3F8] pt-3">
                        <span>{{ t('report_needed', locale()) }}</span>
                        <b class="text-[#101318]">{{ formatSom(props.case.budget_minor, locale()) }}</b>
                    </div>
                    <div class="flex justify-between">
                        <span>{{ t('collected', locale()) }}</span>
                        <b class="text-[#101318]">{{ formatSom(props.case.allocated_minor, locale()) }}</b>
                    </div>
                </dl>
            </section>

            <section class="rounded-[10px] border border-[#DCE6F0] bg-white p-5">
                <h2 class="font-heading text-sm font-bold text-[#101318]">{{ t('report_period_heading', locale()) }}</h2>
                <dl class="mt-4 space-y-3 text-sm text-[#3D4655]">
                    <div class="flex justify-between">
                        <span>{{ t('report_start_date', locale()) }}</span>
                        <b class="text-[#101318]">{{ formatDate(props.case.startDate) }}</b>
                    </div>
                    <div class="flex justify-between">
                        <span>{{ t('report_end_date', locale()) }}</span>
                        <b class="text-[#101318]">{{ formatDate(props.case.endDate) || t('report_open_ended', locale()) }}</b>
                    </div>
                    <div class="flex justify-between border-t border-[#EEF3F8] pt-3">
                        <span>{{ t('report_status', locale()) }}</span>
                        <span class="font-heading rounded-full bg-[#F5F8FC] px-2.5 py-1 text-xs font-bold text-[#5B6472]">
                            {{ t('status_closed', locale()) }}
                        </span>
                    </div>
                </dl>
            </section>
        </div>

        <section class="mt-6 rounded-[10px] border border-[#DCE6F0] bg-white p-5">
            <h2 class="font-heading text-sm font-bold text-[#101318]">{{ t('report_activities_heading', locale()) }}</h2>
            <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-[#3D4655]">
                {{ pickLocale(props.case.report, locale()) }}
            </p>

            <div v-if="props.case.reportPhotoUrls?.length" class="mt-5">
                <h3 class="font-heading text-xs font-bold uppercase tracking-wider text-[#8B94A3]">{{ t('report_photos_heading', locale()) }}</h3>
                <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <img
                        v-for="(photo, index) in props.case.reportPhotoUrls"
                        :key="index"
                        :src="photo"
                        alt=""
                        class="aspect-video w-full rounded-lg object-cover"
                    />
                </div>
            </div>
        </section>
    </PublicLayout>
</template>

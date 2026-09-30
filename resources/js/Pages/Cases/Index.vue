<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import PublicLayout from '../../Layouts/PublicLayout.vue';
import { formatSom, pickLocale } from '../../money.js';
import { categoryLabel as categoryLabelFor, donationsWord, t } from '../../i18n.js';

const props = defineProps({
    cases: { type: Array, required: true },
    closedCaseReports: { type: Array, default: () => [] },
    stats: { type: Object, required: true },
    siteSettings: { type: Object, default: null },
});

const page = usePage();
const locale = () => page.props.locale;

const categoryLabel = (category) => categoryLabelFor(category, locale());
const donationsLabel = (n) => donationsWord(n, locale());

const progress = (c) => (c.budget_minor > 0 ? Math.min(100, Math.round((c.allocated_minor / c.budget_minor) * 100)) : 0);
</script>

<template>
    <PublicLayout>
        <template #hero>
            <section class="relative overflow-hidden border-b border-brand-cyan/30 bg-brand-navy">
                <img
                    src="/images/pexels-huysuzkadraj-19142792.jpg"
                    alt=""
                    class="absolute inset-0 h-full w-full object-cover"
                />
                <div class="absolute inset-0 bg-gradient-to-r from-brand-navy/75 via-brand-navy/55 to-brand-navy/20" />

                <div class="relative mx-auto max-w-6xl px-4 py-12 text-center sm:px-6 sm:py-16 sm:text-left lg:px-8 lg:py-20">
                    <span class="font-heading inline-block rounded-full bg-white/10 px-3.5 py-1.5 text-xs font-bold uppercase tracking-wider text-brand-cyan">
                        {{ t('hero_badge', locale()) }}
                    </span>
                    <h1 class="font-heading mx-auto mt-5 max-w-2xl text-3xl font-extrabold leading-tight text-white sm:mx-0 sm:text-4xl lg:text-5xl">
                        Элим, барсыңбы?!
                    </h1>
                    <p class="mx-auto mt-4 max-w-xl text-base text-white/75 sm:mx-0 sm:text-lg">
                        {{ t('hero_subtitle', locale()) }}
                    </p>
                    <div class="mt-7 flex flex-wrap items-center justify-center gap-3 sm:justify-start">
                        <a
                            href="#cases"
                            class="font-heading rounded-lg bg-brand-cyan px-5 py-3 text-sm font-bold text-brand-navy transition hover:bg-white"
                        >
                            {{ t('hero_cta_view_cases', locale()) }}
                        </a>
                        <Link
                            href="/help"
                            class="font-heading rounded-lg border border-white/25 px-5 py-3 text-sm font-bold text-white transition hover:border-brand-cyan hover:text-brand-cyan"
                        >
                            {{ t('nav_help', locale()) }}
                        </Link>
                    </div>

                    <dl class="mx-auto mt-10 grid max-w-xl grid-cols-3 gap-4 border-t border-white/15 pt-6 sm:mx-0 sm:mt-12 sm:pt-7">
                        <div>
                            <dt class="text-xs text-white/60">{{ t('stat_active_cases', locale()) }}</dt>
                            <dd class="font-heading mt-1 text-xl font-extrabold text-white sm:text-2xl">{{ stats.activeCases }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-white/60">{{ t('stat_raised', locale()) }}</dt>
                            <dd class="font-heading mt-1 text-xl font-extrabold text-white sm:text-2xl">{{ formatSom(stats.raisedMinor, locale()) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-white/60">{{ t('stat_donations', locale()) }}</dt>
                            <dd class="font-heading mt-1 text-xl font-extrabold text-white sm:text-2xl">{{ stats.donationsCount }}</dd>
                        </div>
                    </dl>
                </div>
            </section>
        </template>

        <h2 id="cases" class="font-heading scroll-mt-6 text-center text-xl font-bold uppercase tracking-tight text-brand-navy sm:text-left sm:text-2xl">
            {{ t('cases_heading', locale()) }}
            <span class="mx-auto mt-2 block h-1 w-14 bg-brand-cyan sm:mx-0" />
        </h2>

        <div v-if="cases.length === 0" class="mt-6 rounded-lg border border-dashed border-[#DCE6F0] p-10 text-center text-[#8B94A3]">
            {{ t('no_active_cases', locale()) }}
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

                    <template v-if="c.budget_minor !== null">
                        <div class="flex items-center gap-2.5">
                            <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-[#E4ECF5]">
                                <div class="h-full bg-brand-navy" :style="{ width: progress(c) + '%' }" />
                            </div>
                            <span class="font-heading text-[13px] font-bold text-brand-navy">{{ progress(c) }}%</span>
                        </div>
                        <div class="flex justify-between border-t border-[#EEF3F8] pt-2 text-[12.5px] text-[#5B6472]">
                            <span>{{ t('collected', locale()) }} <b class="text-[#101318]">{{ formatSom(c.allocated_minor, locale()) }}</b></span>
                            <span>{{ t('goal', locale()) }} <b class="text-[#101318]">{{ formatSom(c.budget_minor, locale()) }}</b></span>
                        </div>
                    </template>
                    <div v-else class="flex justify-between border-t border-[#EEF3F8] pt-2 text-[12.5px] text-[#5B6472]">
                        <span>{{ t('collected', locale()) }} <b class="text-[#101318]">{{ formatSom(c.allocated_minor, locale()) }}</b></span>
                        <span class="text-[#8B94A3]">{{ t('budget_unlimited', locale()) }}</span>
                    </div>
                </div>
            </Link>
        </div>

        <section v-if="closedCaseReports.length > 0" class="mt-16 border-t border-[#DCE6F0] pt-10 text-center sm:text-left">
            <h2 class="font-heading text-xl font-bold uppercase tracking-tight text-brand-navy sm:text-2xl">
                {{ t('reports_heading', locale()) }}
                <span class="mx-auto mt-2 block h-1 w-14 bg-brand-cyan sm:mx-0" />
            </h2>

            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <Link
                    v-for="c in closedCaseReports"
                    :key="c.id"
                    :href="`/cases/${c.id}/report`"
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
                        <span class="font-heading absolute bottom-2 right-2 rounded-full bg-brand-navy/85 px-2.5 py-1 text-[11px] font-bold text-white backdrop-blur-sm">
                            {{ t('status_closed', locale()) }}
                        </span>
                    </div>
                    <div class="flex flex-col gap-2.5 p-4">
                        <div class="font-heading text-[11px] font-bold uppercase tracking-wider text-brand-cyan">
                            {{ categoryLabel(c.category) }}
                        </div>
                        <h3 class="font-heading text-[15.5px] font-bold leading-snug text-[#101318]">
                            {{ pickLocale(c.title, locale()) }}
                        </h3>

                        <template v-if="c.budget_minor !== null">
                            <div class="flex items-center gap-2.5">
                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-[#E4ECF5]">
                                    <div class="h-full bg-brand-navy" :style="{ width: progress(c) + '%' }" />
                                </div>
                                <span class="font-heading text-[13px] font-bold text-brand-navy">{{ progress(c) }}%</span>
                            </div>
                            <div class="flex justify-between border-t border-[#EEF3F8] pt-2 text-[12.5px] text-[#5B6472]">
                                <span>{{ t('collected', locale()) }} <b class="text-[#101318]">{{ formatSom(c.allocated_minor, locale()) }}</b></span>
                                <span>{{ t('goal', locale()) }} <b class="text-[#101318]">{{ formatSom(c.budget_minor, locale()) }}</b></span>
                            </div>
                        </template>
                        <div v-else class="flex justify-between border-t border-[#EEF3F8] pt-2 text-[12.5px] text-[#5B6472]">
                            <span>{{ t('collected', locale()) }} <b class="text-[#101318]">{{ formatSom(c.allocated_minor, locale()) }}</b></span>
                            <span class="text-[#8B94A3]">{{ t('budget_unlimited', locale()) }}</span>
                        </div>
                    </div>
                </Link>
            </div>
        </section>

        <section v-if="siteSettings && pickLocale(siteSettings.aboutBody, locale())" class="mt-16 border-t border-[#DCE6F0] pt-10 text-center sm:text-left">
            <h2 class="font-heading text-xl font-bold uppercase tracking-tight text-brand-navy sm:text-2xl">
                {{ pickLocale(siteSettings.aboutTitle, locale()) || t('about_heading', locale()) }}
                <span class="mx-auto mt-2 block h-1 w-14 bg-brand-cyan sm:mx-0" />
            </h2>
            <p class="mx-auto mt-5 max-w-3xl whitespace-pre-line text-left leading-relaxed text-[#3D4655] sm:mx-0">
                {{ pickLocale(siteSettings.aboutBody, locale()) }}
            </p>

            <div class="mt-8 grid gap-5 text-left sm:grid-cols-3">
                <div v-if="pickLocale(siteSettings.aboutVolunteersBody, locale())" class="overflow-hidden rounded-[10px] border border-[#DCE6F0] bg-white">
                    <div class="h-1 bg-brand-cyan" />
                    <div class="p-5">
                        <h3 class="font-heading text-sm font-bold text-[#101318]">{{ t('about_volunteers_heading', locale()) }}</h3>
                        <p class="mt-2.5 whitespace-pre-line text-sm leading-relaxed text-[#5B6472]">{{ pickLocale(siteSettings.aboutVolunteersBody, locale()) }}</p>
                    </div>
                </div>
                <div v-if="pickLocale(siteSettings.aboutBoxesBody, locale())" class="overflow-hidden rounded-[10px] border border-[#DCE6F0] bg-white">
                    <div class="h-1 bg-brand-cyan" />
                    <div class="p-5">
                        <h3 class="font-heading text-sm font-bold text-[#101318]">{{ t('about_boxes_heading', locale()) }}</h3>
                        <p class="mt-2.5 whitespace-pre-line text-sm leading-relaxed text-[#5B6472]">{{ pickLocale(siteSettings.aboutBoxesBody, locale()) }}</p>
                    </div>
                </div>
                <div v-if="pickLocale(siteSettings.aboutShopBody, locale())" class="overflow-hidden rounded-[10px] border border-[#DCE6F0] bg-white">
                    <div class="h-1 bg-brand-cyan" />
                    <div class="p-5">
                        <h3 class="font-heading text-sm font-bold text-[#101318]">{{ t('about_shop_heading', locale()) }}</h3>
                        <p class="mt-2.5 whitespace-pre-line text-sm leading-relaxed text-[#5B6472]">{{ pickLocale(siteSettings.aboutShopBody, locale()) }}</p>
                    </div>
                </div>
            </div>
        </section>

        <section v-if="siteSettings" class="mt-16 border-t border-[#DCE6F0] pt-10 text-center sm:text-left">
            <h2 class="font-heading text-xl font-bold uppercase tracking-tight text-brand-navy sm:text-2xl">
                {{ t('contacts_heading', locale()) }}
                <span class="mx-auto mt-2 block h-1 w-14 bg-brand-cyan sm:mx-0" />
            </h2>

            <dl class="mx-auto mt-6 grid max-w-3xl gap-x-10 gap-y-5 text-left text-sm text-[#3D4655] sm:mx-0 sm:grid-cols-2">
                <div v-if="pickLocale(siteSettings.contactAddress, locale())" class="flex items-start gap-3">
                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#F5F8FC] text-brand-cyan">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-4.5 w-4.5">
                            <path d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                        </svg>
                    </span>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-[#8B94A3]">{{ t('address', locale()) }}</dt>
                        <dd class="mt-0.5">{{ pickLocale(siteSettings.contactAddress, locale()) }}</dd>
                    </div>
                </div>
                <div v-if="siteSettings.contactReceptionPhone" class="flex items-start gap-3">
                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#F5F8FC] text-brand-cyan">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-4.5 w-4.5">
                            <path d="M2.25 6.75c0 8.284 6.716 15 15 15h1.5a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106a1.125 1.125 0 00-1.173.417l-.97 1.293c-2.884-1.353-5.207-3.676-6.56-6.56l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                        </svg>
                    </span>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-[#8B94A3]">{{ t('reception_phone', locale()) }}</dt>
                        <dd class="mt-0.5">
                            <a :href="`tel:${siteSettings.contactReceptionPhone}`" class="hover:text-brand-cyan">{{ siteSettings.contactReceptionPhone }}</a>
                        </dd>
                    </div>
                </div>
                <div v-if="siteSettings.contactPartnershipPhone" class="flex items-start gap-3">
                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#F5F8FC] text-brand-cyan">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-4.5 w-4.5">
                            <path d="M2.25 6.75c0 8.284 6.716 15 15 15h1.5a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106a1.125 1.125 0 00-1.173.417l-.97 1.293c-2.884-1.353-5.207-3.676-6.56-6.56l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                        </svg>
                    </span>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-[#8B94A3]">{{ t('partnership_phone', locale()) }}</dt>
                        <dd class="mt-0.5">
                            <a :href="`tel:${siteSettings.contactPartnershipPhone}`" class="hover:text-brand-cyan">{{ siteSettings.contactPartnershipPhone }}</a>
                        </dd>
                    </div>
                </div>
                <div v-if="siteSettings.contactEmail" class="flex items-start gap-3">
                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#F5F8FC] text-brand-cyan">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-4.5 w-4.5">
                            <path d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                        </svg>
                    </span>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-[#8B94A3]">{{ t('email', locale()) }}</dt>
                        <dd class="mt-0.5">
                            <a :href="`mailto:${siteSettings.contactEmail}`" class="hover:text-brand-cyan">{{ siteSettings.contactEmail }}</a>
                        </dd>
                    </div>
                </div>
                <div v-if="pickLocale(siteSettings.contactWorkingHours, locale())" class="flex items-start gap-3">
                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#F5F8FC] text-brand-cyan">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-4.5 w-4.5">
                            <path d="M12 6v6l4 2" />
                            <path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-[#8B94A3]">{{ t('working_hours', locale()) }}</dt>
                        <dd class="mt-0.5">{{ pickLocale(siteSettings.contactWorkingHours, locale()) }}</dd>
                    </div>
                </div>
                <div v-if="siteSettings.contactWebsite" class="flex items-start gap-3">
                    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#F5F8FC] text-brand-cyan">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-4.5 w-4.5">
                            <path d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            <path d="M3.6 9h16.8M3.6 15h16.8" />
                            <path d="M12 3c2.5 3 2.5 15 0 18M12 3c-2.5 3-2.5 15 0 18" />
                        </svg>
                    </span>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-[#8B94A3]">{{ t('website', locale()) }}</dt>
                        <dd class="mt-0.5">
                            <a :href="siteSettings.contactWebsite" target="_blank" rel="noopener" class="hover:text-brand-cyan">{{ siteSettings.contactWebsite }}</a>
                        </dd>
                    </div>
                </div>
                <div v-if="siteSettings.contactInstagram" class="flex items-center">
                    <a
                        :href="siteSettings.contactInstagram"
                        target="_blank"
                        rel="noopener"
                        aria-label="Instagram"
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#F5F8FC] text-brand-cyan transition hover:bg-brand-cyan hover:text-white"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="h-4.5 w-4.5">
                            <rect x="3" y="3" width="18" height="18" rx="5" />
                            <circle cx="12" cy="12" r="4" />
                            <circle cx="17.25" cy="6.75" r="0.5" fill="currentColor" stroke="none" />
                        </svg>
                    </a>
                </div>
            </dl>
        </section>
    </PublicLayout>
</template>

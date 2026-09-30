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

            <dl class="mx-auto mt-6 grid max-w-3xl gap-x-10 gap-y-4 text-left text-sm text-[#3D4655] sm:mx-0 sm:grid-cols-2">
                <div v-if="pickLocale(siteSettings.contactAddress, locale())">
                    <dt class="text-xs uppercase tracking-wider text-[#8B94A3]">{{ t('address', locale()) }}</dt>
                    <dd class="mt-0.5">{{ pickLocale(siteSettings.contactAddress, locale()) }}</dd>
                </div>
                <div v-if="siteSettings.contactReceptionPhone">
                    <dt class="text-xs uppercase tracking-wider text-[#8B94A3]">{{ t('reception_phone', locale()) }}</dt>
                    <dd class="mt-0.5">
                        <a :href="`tel:${siteSettings.contactReceptionPhone}`" class="hover:text-brand-cyan">{{ siteSettings.contactReceptionPhone }}</a>
                    </dd>
                </div>
                <div v-if="siteSettings.contactPartnershipPhone">
                    <dt class="text-xs uppercase tracking-wider text-[#8B94A3]">{{ t('partnership_phone', locale()) }}</dt>
                    <dd class="mt-0.5">
                        <a :href="`tel:${siteSettings.contactPartnershipPhone}`" class="hover:text-brand-cyan">{{ siteSettings.contactPartnershipPhone }}</a>
                    </dd>
                </div>
                <div v-if="siteSettings.contactEmail">
                    <dt class="text-xs uppercase tracking-wider text-[#8B94A3]">{{ t('email', locale()) }}</dt>
                    <dd class="mt-0.5">
                        <a :href="`mailto:${siteSettings.contactEmail}`" class="hover:text-brand-cyan">{{ siteSettings.contactEmail }}</a>
                    </dd>
                </div>
                <div v-if="siteSettings.contactEmailSecondary">
                    <dt class="text-xs uppercase tracking-wider text-[#8B94A3]">{{ t('email', locale()) }}</dt>
                    <dd class="mt-0.5">
                        <a :href="`mailto:${siteSettings.contactEmailSecondary}`" class="hover:text-brand-cyan">{{ siteSettings.contactEmailSecondary }}</a>
                    </dd>
                </div>
                <div v-if="siteSettings.contactWebsite">
                    <dt class="text-xs uppercase tracking-wider text-[#8B94A3]">🌐 {{ t('website', locale()) }}</dt>
                    <dd class="mt-0.5">
                        <a :href="siteSettings.contactWebsite" target="_blank" rel="noopener" class="hover:text-brand-cyan">{{ siteSettings.contactWebsite }}</a>
                    </dd>
                </div>
                <div v-if="siteSettings.contactInstagram">
                    <dt class="text-xs uppercase tracking-wider text-[#8B94A3]">📱 Instagram</dt>
                    <dd class="mt-0.5">
                        <a :href="siteSettings.contactInstagram" target="_blank" rel="noopener" class="hover:text-brand-cyan">{{ siteSettings.contactInstagram }}</a>
                    </dd>
                </div>
            </dl>
        </section>
    </PublicLayout>
</template>

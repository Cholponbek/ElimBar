// amount_minor — всегда bigint в минорных единицах (тыйын). Делим на 100
// только на границе показа человеку, никогда не храним и не считаем в сомах.
export function formatSom(amountMinor, locale = 'ru') {
    const amount = Math.round(amountMinor / 100);

    if (locale === 'en') {
        return `${amount.toLocaleString('en-US')} som`;
    }

    return `${amount.toLocaleString('ru-RU')} сом`;
}

// jsonb {"ky": "...", "ru": "..."} — с запасным языком, если перевод не заполнен.
export function pickLocale(value, locale, fallback = 'ru') {
    if (!value) return '';
    return value[locale] || value[fallback] || Object.values(value)[0] || '';
}

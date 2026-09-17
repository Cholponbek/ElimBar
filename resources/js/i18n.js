// Статичные UI-тексты (кнопки, лейблы, заголовки) — не путать с
// pickLocale() в money.js, который выбирает язык у переводимого контента
// из БД (заголовки/истории кейсов, "О нас"/"Контакты"). Переключатель языка
// в хедере (PublicLayout.vue) меняет то, что смотрит сюда.
//
// Ключи плоские (без вложенности) — страниц немного, вложенность добавила
// бы косвенности без реальной пользы при таком объёме текста.
const translations = {
    ru: {
        nav_help: 'Нужна помощь?',
        org_line: 'ОБФ · Бишкек, КР',
        footer_tagline: 'Каждый сом привязан к конкретному кейсу — публичный отчёт собирается автоматически.',

        hero_badge: 'Общественный благотворительный фонд',
        hero_subtitle: 'Каждый сом привязан к конкретному кейсу — публичный отчёт собирается автоматически.',
        hero_cta_view_cases: 'Смотреть кейсы',
        stat_active_cases: 'Активных кейсов',
        stat_raised: 'Собрано публично',
        stat_donations: 'Донатов',

        cases_heading: 'Кейсы, которым нужна помощь',
        no_active_cases: 'Пока нет активных кейсов.',
        collected: 'Собрано',
        goal: 'Цель',

        category_medical: 'Лечение',
        category_winter_food: 'Зимняя продуктовая помощь',
        category_fund_project: 'Проект фонда',

        back_to_cases: 'Все кейсы',
        share: 'Поделиться',
        preparing_card: 'Готовим карточку…',
        download_story_card: 'Скачать карточку для Stories',
        preparing: 'Готовим…',
        copy_link: 'Скопировать ссылку',
        link_copied: 'Ссылка скопирована',
        close: 'Закрыть',
        share_modal_title: 'Поделиться',
        share_your_link: 'Ваша ссылка',
        share_description: 'Отправьте ссылку друзьям — каждый донат сразу виден в публичном отчёте.',
        share_email: 'Email',
        share_instagram_stories: 'Instagram Stories',
        share_other_ways: 'Другие способы',
        previous_photo: 'Предыдущее фото',
        next_photo: 'Следующее фото',
        photo: 'Фото',
        no_details_yet: 'Подробностей пока нет.',
        disbursed_for_case: 'Выплачено по кейсу:',
        amount_som: 'Сумма, сом',
        custom_amount_placeholder: 'Или своя сумма',
        phone: 'Телефон',
        phone_placeholder: '+996 700 000 000',
        name_optional: 'Имя (необязательно)',
        name_placeholder: 'Как к вам обращаться',
        show_name_publicly: 'Показывать моё имя в списке донатов вместо номера телефона',
        support: 'Поддержать',
        sending: 'Отправляем…',
        recent_donations: 'Последние донаты',

        help_title: 'Нужна помощь?',
        help_subtitle: 'Расскажите о ситуации — сотрудник фонда свяжется с вами и, если всё подтвердится, кейс появится на сайте.',
        full_name: 'Ваше ФИО',
        category: 'Категория',
        describe_situation: 'Опишите ситуацию',
        requested_amount: 'Нужная сумма, сом (если известна)',
        submit_request: 'Отправить заявку',

        about_heading: 'О нас',
        contacts_heading: 'Контакты',
        address: 'Адрес',
        email: 'Email',
        social_networks: 'Соцсети',

        story_collected_suffix: 'собрано',
        story_goal_prefix: 'Цель —',
    },
    ky: {
        nav_help: 'Жардам керекпи?',
        org_line: 'ОБФ · Бишкек, КР',
        footer_tagline: 'Ар бир сом конкреттүү кейске бекитилген — коомдук отчёт автоматтык түрдө түзүлөт.',

        hero_badge: 'Коомдук кайрымдуулук фонду',
        hero_subtitle: 'Ар бир сом конкреттүү кейске бекитилген — коомдук отчёт автоматтык түрдө түзүлөт.',
        hero_cta_view_cases: 'Кейстерди көрүү',
        stat_active_cases: 'Активдүү кейстер',
        stat_raised: 'Коомдук жыйналган',
        stat_donations: 'Донаттар',

        cases_heading: 'Жардам керек болгон кейстер',
        no_active_cases: 'Азырынча активдүү кейстер жок.',
        collected: 'Жыйналды',
        goal: 'Максат',

        category_medical: 'Дарылоо',
        category_winter_food: 'Кышкы азык-түлүк жардамы',
        category_fund_project: 'Фонддун долбоору',

        back_to_cases: 'Бардык кейстер',
        share: 'Бөлүшүү',
        preparing_card: 'Карточка даярдалууда…',
        download_story_card: 'Stories үчүн карточканы жүктөп алуу',
        preparing: 'Даярдалууда…',
        copy_link: 'Шилтемени көчүрүү',
        link_copied: 'Шилтеме көчүрүлдү',
        close: 'Жабуу',
        share_modal_title: 'Бөлүшүү',
        share_your_link: 'Сиздин шилтемеңиз',
        share_description: 'Шилтемени досторуңузга жөнөтүңүз — ар бир донат дароо публикалык отчётто көрүнөт.',
        share_email: 'Email',
        share_instagram_stories: 'Instagram Stories',
        share_other_ways: 'Башка жолдор',
        previous_photo: 'Мурунку сүрөт',
        next_photo: 'Кийинки сүрөт',
        photo: 'Сүрөт',
        no_details_yet: 'Азырынча кеңири маалымат жок.',
        disbursed_for_case: 'Кейс боюнча төлөндү:',
        amount_som: 'Сумма, сом',
        custom_amount_placeholder: 'Же өз суммаңыз',
        phone: 'Телефон',
        phone_placeholder: '+996 700 000 000',
        name_optional: 'Атыңыз (милдеттүү эмес)',
        name_placeholder: 'Сизге кантип кайрылса болот',
        show_name_publicly: 'Телефон номеринин ордуна донаттар тизмесинде атымды көрсөтүү',
        support: 'Колдоо көрсөтүү',
        sending: 'Жөнөтүлүүдө…',
        recent_donations: 'Акыркы донаттар',

        help_title: 'Жардам керекпи?',
        help_subtitle: 'Кырдаал жөнүндө айтып бериңиз — фонддун кызматкери сиз менен байланышат, эгер бардыгы ырасталса, кейс сайтта пайда болот.',
        full_name: 'Аты-жөнүңүз',
        category: 'Категория',
        describe_situation: 'Кырдаалды сүрөттөп бериңиз',
        requested_amount: 'Керектүү сумма, сом (белгилүү болсо)',
        submit_request: 'Арызды жөнөтүү',

        about_heading: 'Биз жөнүндө',
        contacts_heading: 'Байланыш',
        address: 'Дарек',
        email: 'Email',
        social_networks: 'Социалдык тармактар',

        story_collected_suffix: 'жыйналды',
        story_goal_prefix: 'Максат —',
    },
};

const FALLBACK_LOCALE = 'ru';

export function t(key, locale) {
    return translations[locale]?.[key] ?? translations[FALLBACK_LOCALE][key] ?? key;
}

const CATEGORY_KEYS = {
    medical: 'category_medical',
    winter_food: 'category_winter_food',
    fund_project: 'category_fund_project',
};

export function categoryLabel(category, locale) {
    const key = CATEGORY_KEYS[category];
    return key ? t(key, locale) : category;
}

// Кыргызский, как и другие тюркские языки, не склоняет существительное
// после числительного ("3 донат", не "3 доната") — русское склонение
// (донат/доната/донатов) нужно только для ru.
export function donationsWord(n, locale) {
    if (locale !== 'ru') {
        return 'донат';
    }

    const mod10 = n % 10;
    const mod100 = n % 100;
    if (mod10 === 1 && mod100 !== 11) return 'донат';
    if ([2, 3, 4].includes(mod10) && ![12, 13, 14].includes(mod100)) return 'доната';
    return 'донатов';
}

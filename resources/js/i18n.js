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
        support_menu_title: 'Выберите кейс, чтобы помочь',
        view_all_cases: 'Смотреть все кейсы',
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
        budget_unlimited: 'Без ограничения бюджета',

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
        see_all_donations: 'Смотреть все',
        see_top_donations: 'Смотреть топ',
        donations_modal_title: 'Донаты',
        donations_tab_all: 'Все',
        donations_tab_top: 'Топ',
        loading: 'Загрузка…',
        no_donations_yet: 'Пока нет донатов.',

        help_title: 'Нужна помощь?',
        help_subtitle: 'Расскажите о ситуации — сотрудник фонда свяжется с вами и, если всё подтвердится, кейс появится на сайте.',
        full_name: 'Ваше ФИО',
        category: 'Категория',
        describe_situation: 'Опишите ситуацию',
        requested_amount: 'Нужная сумма, сом (если известна)',
        submit_request: 'Отправить заявку',

        about_heading: 'О фонде',
        about_volunteers_heading: 'Наши волонтёры',
        about_boxes_heading: 'Благотворительные ящики нашего фонда',
        about_shop_heading: 'Социально-благотворительный магазин',
        contacts_heading: 'Контакты фонда',
        address: 'Адрес',
        email: 'Электронная почта',
        reception_phone: 'Приёмное отделение',
        partnership_phone: 'По вопросам сотрудничества и спонсорства',
        website: 'Официальный сайт',
        social_networks: 'Соцсети',

        reports_heading: 'Мероприятия и отчёты',
        report_closed_on: 'Закрыт',
        report_case_heading: 'Кейс',
        report_short_description: 'Краткое описание',
        report_needed: 'Нужно было собрать',
        report_period_heading: 'Сроки кейса',
        report_start_date: 'Дата начала',
        report_end_date: 'Дата окончания',
        report_open_ended: 'Открыт',
        report_status: 'Статус',
        status_closed: 'Закрыт',
        report_activities_heading: 'Отчёт по кейсу',
        report_photos_heading: 'Фото отчёта',

        story_collected_suffix: 'собрано',
        story_goal_prefix: 'Цель —',
    },
    ky: {
        nav_help: 'Жардам керекпи?',
        org_line: 'ОБФ · Бишкек, КР',
        support_menu_title: 'Жардам берүү үчүн кейс тандаңыз',
        view_all_cases: 'Бардык кейстерди көрүү',
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
        budget_unlimited: 'Бюджет чектелген эмес',

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
        see_all_donations: 'Баарын көрүү',
        see_top_donations: 'Топту көрүү',
        donations_modal_title: 'Донаттар',
        donations_tab_all: 'Баары',
        donations_tab_top: 'Топ',
        loading: 'Жүктөлүүдө…',
        no_donations_yet: 'Азырынча донат жок.',

        help_title: 'Жардам керекпи?',
        help_subtitle: 'Кырдаал жөнүндө айтып бериңиз — фонддун кызматкери сиз менен байланышат, эгер бардыгы ырасталса, кейс сайтта пайда болот.',
        full_name: 'Аты-жөнүңүз',
        category: 'Категория',
        describe_situation: 'Кырдаалды сүрөттөп бериңиз',
        requested_amount: 'Керектүү сумма, сом (белгилүү болсо)',
        submit_request: 'Арызды жөнөтүү',

        about_heading: 'Фонд жөнүндө',
        about_volunteers_heading: 'Биздин волонтёрлор',
        about_boxes_heading: 'Фонддун кайрымдуулук жашиктери',
        about_shop_heading: 'Социалдык-кайрымдуулук дүкөнү',
        contacts_heading: 'Фонддун байланыштары',
        address: 'Дарек',
        email: 'Электрондук почта',
        reception_phone: 'Кабыл алуу бөлүмү',
        partnership_phone: 'Кызматташтык жана демөөрчүлүк маселелери боюнча',
        website: 'Расмий сайт',
        social_networks: 'Социалдык тармактар',

        reports_heading: 'Иш-чаралар жана отчёттор',
        report_closed_on: 'Жабылды',
        report_case_heading: 'Кейс',
        report_short_description: 'Кыскача маалымат',
        report_needed: 'Канча сумма керек болгон',
        report_period_heading: 'Кейстин мөөнөтү',
        report_start_date: 'Башталган күнү',
        report_end_date: 'Аяктаган күнү',
        report_open_ended: 'Ачык',
        report_status: 'Статус',
        status_closed: 'Жабылды',
        report_activities_heading: 'Кейс боюнча отчёт',
        report_photos_heading: 'Отчёттун сүрөттөрү',

        story_collected_suffix: 'жыйналды',
        story_goal_prefix: 'Максат —',
    },
    en: {
        nav_help: 'Need help?',
        org_line: 'Public Charity Fund · Bishkek, KG',
        support_menu_title: 'Choose a case to support',
        view_all_cases: 'View all cases',
        footer_tagline: 'Every som is tied to a specific case — the public report is generated automatically.',

        hero_badge: 'Public Charity Fund',
        hero_subtitle: 'Every som is tied to a specific case — the public report is generated automatically.',
        hero_cta_view_cases: 'View cases',
        stat_active_cases: 'Active cases',
        stat_raised: 'Raised publicly',
        stat_donations: 'Donations',

        cases_heading: 'Cases that need help',
        no_active_cases: 'No active cases yet.',
        collected: 'Raised',
        goal: 'Goal',
        budget_unlimited: 'No budget limit',

        category_medical: 'Medical treatment',
        category_winter_food: 'Winter food aid',
        category_fund_project: 'Fund project',

        back_to_cases: 'All cases',
        share: 'Share',
        preparing_card: 'Preparing card…',
        download_story_card: 'Download Stories card',
        preparing: 'Preparing…',
        copy_link: 'Copy link',
        link_copied: 'Link copied',
        close: 'Close',
        share_modal_title: 'Share',
        share_your_link: 'Your link',
        share_description: 'Send the link to friends — every donation is instantly visible in the public report.',
        share_email: 'Email',
        share_instagram_stories: 'Instagram Stories',
        share_other_ways: 'Other ways',
        previous_photo: 'Previous photo',
        next_photo: 'Next photo',
        photo: 'Photo',
        no_details_yet: 'No details yet.',
        disbursed_for_case: 'Disbursed for this case:',
        amount_som: 'Amount, KGS',
        custom_amount_placeholder: 'Or your own amount',
        phone: 'Phone',
        phone_placeholder: '+996 700 000 000',
        name_optional: 'Name (optional)',
        name_placeholder: 'How should we address you',
        show_name_publicly: 'Show my name in the donations list instead of my phone number',
        support: 'Support',
        sending: 'Sending…',
        recent_donations: 'Recent donations',
        see_all_donations: 'See all',
        see_top_donations: 'See top',
        donations_modal_title: 'Donations',
        donations_tab_all: 'All',
        donations_tab_top: 'Top',
        loading: 'Loading…',
        no_donations_yet: 'No donations yet.',

        help_title: 'Need help?',
        help_subtitle: 'Tell us about the situation — a fund staff member will contact you, and if everything is confirmed, the case will appear on the site.',
        full_name: 'Your full name',
        category: 'Category',
        describe_situation: 'Describe the situation',
        requested_amount: 'Amount needed, KGS (if known)',
        submit_request: 'Submit request',

        about_heading: 'About the fund',
        about_volunteers_heading: 'Our volunteers',
        about_boxes_heading: 'Our fund\'s charity boxes',
        about_shop_heading: 'Social charity shop',
        contacts_heading: 'Fund contacts',
        address: 'Address',
        email: 'Email',
        reception_phone: 'Reception desk',
        partnership_phone: 'Partnerships and sponsorship',
        website: 'Official website',
        social_networks: 'Social media',

        reports_heading: 'Activities and reports',
        report_closed_on: 'Closed',
        report_case_heading: 'Case',
        report_short_description: 'Short description',
        report_needed: 'Amount needed',
        report_period_heading: 'Case timeline',
        report_start_date: 'Start date',
        report_end_date: 'End date',
        report_open_ended: 'Open-ended',
        report_status: 'Status',
        status_closed: 'Closed',
        report_activities_heading: 'Case report',
        report_photos_heading: 'Report photos',

        story_collected_suffix: 'raised',
        story_goal_prefix: 'Goal —',
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
// (донат/доната/донатов) нужно только для ru. Английский — обычное
// singular/plural (1 donation / 2 donations).
export function donationsWord(n, locale) {
    if (locale === 'en') {
        return n === 1 ? 'donation' : 'donations';
    }

    if (locale !== 'ru') {
        return 'донат';
    }

    const mod10 = n % 10;
    const mod100 = n % 100;
    if (mod10 === 1 && mod100 !== 11) return 'донат';
    if ([2, 3, 4].includes(mod10) && ![12, 13, 14].includes(mod100)) return 'доната';
    return 'донатов';
}

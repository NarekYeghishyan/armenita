(function () {
  "use strict";

  const STORAGE_KEY = "dilijan-villas-rest-relax-lang";

  /** Filled by `initNavDropdowns`; used after `setLang` to refresh video/image + preview copy. */
  const navDropdownPreviewRefreshers = [];

  const STRINGS = {
    hy: {
      lang_select_aria: "Լեզու",
      nav_reviews: "Կարծիքներ",
      nav_book_stay: "Забронировать проживание",
      nav_dropdown_full_page: "Բացել ամբողջ էջը",
      stay_heading: "Վիլլաներ",
      stay_villas_heading: "Մասնավոր վիլլա",
      stay_unit_kind_cottage: "Քոթեջ",
      stay_unit_kind_villa: "Առանձնատուն",
      home_view_details: "Տեսնել մանրամասները",
      home_kind_tour: "Տուր",
      home_kind_diving: "Սուզում",
      stay_section_whats_inside: "Ինչ կա ներսում",
      stay_section_amenities: "Հարմարություններ",
      stay_section_room_features: "Սենյակի առանձնահատկություններ",
      stay_section_room_types: "Սենյակների տեսակները",
      booking_label_name: "Անուն",
      booking_label_phone: "Հեռախոս",
      booking_label_email: "Էլ. փոստ",
      booking_msg_available: "Հասանելի է ընտրված ամսաթվերի համար։",
      booking_msg_unavailable: "Ընտրված ամսաթվերը հասանելի չեն։",
      booking_msg_checking: "Հասանելիության ստուգում...",
      booking_msg_check_error: "Հասանելիությունը չհաջողվեց ստուգել։ Փորձեք կրկին։",
      booking_msg_open_no_price: "Այս ամսաթվերը բաց են, բայց գին դեռ չի սահմանվել։ Խնդրում ենք կապ հաստատել մեզ հետ։",
      stay_label_1: "Cottage 1",
      stay_label_2: "Cottage 2",
      stay_label_3: "Private villa 1",
      stay_label_4: "Private villa 2",
      stay_desc_1: "Ընտանեկան քոթեջ՝ բնության գրկում հանգիստ անցկացնելու համար։",
      stay_desc_2: "Անտառի մեջ գտնվող քոթեջ՝ խաղաղ ու էկո հանգստի համար։",
      stay_desc_3: "Առանձնատուն՝ մեծ ընտանիքի կամ խմբի հարմարավետ հանգստի համար։",
      stay_desc_4: "Լայնարձակ առանձնատուն միջոցառումների և երկարատև հանգստի համար։",
      stay_title_1: "Քոթեջ 1",
      stay_intro_1:
        "Ընտանեկան քոթեջ՝ մոտ այգիներին և զբոսաշրջային ուղիներին։ Հարմար է փոքր ընտանիքների, խաղաղ առավոտների և Դիլիջանի կանաչ միջավայրում քայլելու համար։",
      stay_title_2: "Քոթեջ 2",
      stay_intro_2:
        "Անտառային միջավայրում՝ ավելի հանգիստ և էկո հանգստի տրամադրությամբ։ Հարմար է, երբ ուզում եք անտառային տեսարաններ և ավելի դանդաղ կյանքի ռիթմ։",
      stay_title_3: "Առանձնատուն 1",
      stay_intro_3:
        "Լայն պլանավորում՝ մեծ ընտանիքների կամ ընկերների խմբերի համար։ Բազմաթիվ ննջասենյակներ և ընդհանուր հանգստի տարածք՝ միասին անցկացնելու համար։",
      stay_title_4: "Առանձնատուն 2",
      stay_intro_4:
        "Ամենամեծ տարբերակը տոնակատարությունների և բազմօրյա ծրագրերի համար։ Նախատեսված է երկարատև հանգստի և փակ միջոցառումների համար։",
      nav_tours: "Ռեստորան",
      tour_label_1: "Տուր 1",
      tour_label_2: "Տուր 2",
      tour_desc_1: "Բնության և հանգստի տուր Դիլիջանի կանաչ միջավայրում։",
      tour_desc_2: "Հարմարավետության տուր՝ ջակուզի, շոգեբաղնիք և բուխարի։",
      tour_title_1: "Բնություն և հանգիստի տուր",
      tour_intro_1:
        "Դանդաղ տեմպով օրեր՝ անտառային ուղիներով, տեսարաններով և հանգստի կանգառներով. հարմար է զույգերի և փոքր խմբերի համար, ովքեր ուզում են մաքուր օդ առանց կոշտ ծրագրի։",
      tour_title_2: "Հարմարավետության տուր",
      tour_intro_2:
        "Կենտրոնացված է տարածքում հանգստի վրա՝ ջակուզի, շոգեբաղնիք և բուխարի. իդեալական է քայլարշավից հետո կամ որպես ամբողջական հանգստի օր։",
      diving_heading: "Ակտիվ հանգիստ և ժամանց",
      diving_label_1: "Diving 1",
      diving_label_2: "Diving 2",
      diving_desc_1: "Սուզման փորձառություն 1՝ սկսնակների և արկածասերների համար։",
      diving_desc_2: "Սուզման փորձառություն 2՝ ավելի խոր և ակտիվ ծրագրով։",
      diving_title_1: "Սուզում՝ սկսնակների համար",
      diving_intro_1:
        "Ներածական սեսիաներ՝ լրացուցիչ ուղղորդմամբ նրանց համար, ովքեր առաջին անգամ են փորձում և ուզում են անվտանգ, աջակցված փորձ։",
      diving_title_2: "Սուզում՝ ակտիվ երթուղի",
      diving_intro_2:
        "Ավելի երկար պրոֆիլներ և ակտիվ ծրագիր նրանց համար, ովքեր արդեն ունեն հիմք և ուզում են խորություն ու բազմազանություն։",
      nav_stay: "Առավելություններ",
      events_page_intro:
        "Տուրեր և սուզման փորձառություններ Դիլիջանի բնության մեջ։ Մանրամասների համար գրեք WhatsApp-ով։",
      nav_gallery: "Պատկերասրահ",
      nav_videos: "Քարտեզ",
      nav_contact: "Կապ",
      hero_eyebrow: "Villas · Dilijan",
      hero_title: "Armenita Family Resort",
      hero_subtitle: "",
      hero_cta: "Կապվել",
      hero_secondary: "Պատկերասրահ",
      about_title: "Մեր մասին",
      about_lead: "Armenita Family Resort-ը առաջարկում է ընտանեկան ու կորպորատիվ հանգիստ՝ հարմարավետ քոթեջներում և առանձնատներում։",
      about_li1: "Ընտանեկան քոթեջներ 2-10 անձի համար",
      about_li2: "Էկո քոթեջներ ջակուզիով, շոգեբաղնիքով եւ ճապոնական չանայով",
      about_li3: "Առանձնատուն 15-40 անձի համար, փակ միջոցառումներ մինչև 60 անձի",
      about_li4: "Armenia, Dilijan, Armenia, 3901",
      about_book_now: "Book now",
      reviews_page_kicker: "Հյուրեր",
      reviews_page_title: "Ինչ են ասում մեր հյուրերը",
      reviews_page_lead: "Վարկանիշներ և կարծիքներ Tripadvisor-ից և ճանապարհորդների փորձից։",
      reviews_ratings_eyebrow: "Ճանապարհորդների գնահատական",
      reviews_ratings_title: "Ինչ են ասում մեր մասին",
      reviews_based_on: "Հիմնված ճանապարհորդների կարծիքների վրա",
      reviews_tripadvisor_btn: "Կարդալ Tripadvisor-ում",
      reviews_guest_label: "Հյուրի կարծիք",
      booking_popup_title: "Պատվիրել հանգիստը",
      booking_popup_lead:
        "Ընտրեք ամսաթվերը և հյուրերի թիվը։ Գնահատված գումարը AMD-ով ցուցադրվում է «Վճարել» կոճակից անմիջապես վերևում։",
      booking_label_checkin: "Մուտքի ամսաթիվ",
      booking_label_checkout: "Դուրս գալու ամսաթիվ",
      booking_label_stay_dates: "Ամրագրման ժամանակաշրջան",
      booking_label_stay_length: "Մնալու տևողություն",
      booking_label_guests: "Հյուրեր",
      booking_label_accommodation_type: "Բնակության տեսակ",
      booking_error_dates: "Դուրս գալու ամսաթիվը պետք է լինի մուտքի ամսաթվից ոչ վաղ։",
      booking_price_label: "Գնահատված ընդհանուր",
      booking_price_currency: "AMD",
      booking_submit_pay: "Վճարել",
      booking_msg_payment_failed:
        "Չհաջողվեց բացել վճարման էջը։ Ձեր հայտը պահպանվել է — խնդրում ենք կապվել մեզ հետ վճարումն ավարտելու համար։",
      booking_submit_check: "Ուղարկել հայտը",
      booking_label_children: "Երեխաներով ե՞ք գալիս",
      booking_label_children_count: "Երեխաների թիվը",
      booking_children_opt_no: "Ոչ",
      booking_children_opt_yes: "Այո",
      booking_msg_intro: "Բարև, ուզում եմ ուղարկել հայտ։",
      booking_msg_checkin: "Մուտք",
      booking_msg_checkout: "Ելք",
      booking_msg_stay_length: "Մնալու տևողություն",
      booking_msg_guests: "Հյուրեր",
      booking_msg_children: "Երեխաներ",
      booking_msg_accommodation: "Բնակության տեսակ",
      booking_msg_estimated_total: "Գնահատված ընդհանուր",
      booking_msg_night_one: "գիշեր",
      booking_msg_night_many: "գիշեր",
      stay_title: "Ինչու ընտրել մեզ",
      stay_card1_title: "Բնություն և հանգիստ",
      stay_card1_text: "Քոթեջային հանգիստ Դիլիջանի կանաչ բնության գրկում։",
      stay_card2_title: "Հարմարավետություն",
      stay_card2_text: "Ջակուզի, շոգեբաղնիք, բուխարի և փակ տաղավարներ։",
      stay_card3_title: "Հասցե և միջոցառումներ",
      stay_card3_text: "Դիլիջանի կենտրոնում, միջոցառումներ մինչև 60 անձի համար։",
      gallery_title: "Պատկերասրահ",
      gallery_empty_message: "Նկարները ավելացրեք էջի խմբագրիչում Gallery դաշտում։",
      videos_section_title: "Տեսանյութեր",
      videos_title: "Տեսանյութեր",
      videos_intro: "Տեսանյութերը կարող եք դիտել նաև Facebook-ում։",
      videos_cta: "Բացել Facebook",
      videos_fb_title: "Facebook",
      contact_title: "Կապ",
      contact_intro: "Զանգահարեք կամ գրեք Instagram-ում։",
      contact_address: "Armenia, Dilijan, Armenia, 3901",
      contact_phone: "094 605665",
      contact_instagram: "Instagram — @dilijan_villas",
      contact_facebook: "Facebook",
      contact_tagline: "Էկո հանգիստ Դիլիջանի կենտրոնում։",
      contact_map_aria: "Քարտեզ",
      contact_map_title: "Գտնվելու վայր",
      contact_map_link: "Բացել Google Maps-ում",
      footer_copy: "© Armenita Family Resort",
      footer_copy_full: "© 2024 Armenita Family Resort. Բոլոր իրավունքները պաշտպանված են։",
      footer_privacy: "Գաղտնիության քաղաքականություն",
      footer_terms: "Ծառայության պայմաններ",
      footer_site_by: "Կայքը պատրաստել է",
      footer_credit_prefix: "Կայքը պատրաստել է ",
      footer_tag: "Հայաստան",
      footer_top_aria: "Վերև",
    },
    ru: {
      lang_select_aria: "Язык",
      nav_reviews: "Отзывы",
      nav_book_stay: "Book your stay",
      nav_dropdown_full_page: "Открыть полную страницу",
      stay_heading: "Виллы",
      stay_villas_heading: "Частная вилла",
      stay_unit_kind_cottage: "Коттедж",
      stay_unit_kind_villa: "Частная вилла",
      home_view_details: "Подробнее",
      home_kind_tour: "Тур",
      home_kind_diving: "Дайвинг",
      stay_section_whats_inside: "Что внутри",
      stay_section_amenities: "Удобства",
      stay_section_room_features: "Особенности номера",
      stay_section_room_types: "Типы номеров",
      booking_label_name: "Имя",
      booking_label_phone: "Телефон",
      booking_label_email: "Эл. почта",
      booking_msg_available: "Доступно на выбранные даты.",
      booking_msg_unavailable: "Выбранные даты недоступны.",
      booking_msg_checking: "Проверяем доступность...",
      booking_msg_check_error: "Не удалось проверить доступность. Попробуйте ещё раз.",
      booking_msg_open_no_price: "Эти даты свободны, но цена пока не опубликована. Свяжитесь с нами для уточнения.",
      stay_label_1: "Коттедж 1",
      stay_label_2: "Коттедж 2",
      stay_label_3: "Частная вилла 1",
      stay_label_4: "Частная вилла 2",
      stay_desc_1: "Семейный коттедж для спокойного отдыха на природе.",
      stay_desc_2: "Лесной коттедж для тихого эко-отдыха.",
      stay_desc_3: "Вилла для комфортного отдыха большой семьи или группы.",
      stay_desc_4: "Просторная вилла для мероприятий и длительного отдыха.",
      stay_title_1: "Коттедж 1",
      stay_intro_1:
        "Семейный коттедж ближе к садовым дорожкам — удобен для небольших семей, тихих утренних кофе и прогулок по зелёным окрестностям Дилижана.",
      stay_title_2: "Коттедж 2",
      stay_intro_2:
        "Среди деревьев для более уединённого эко-отдыха — если хотите лесные виды и неспешный ритм.",
      stay_title_3: "Вилла 1",
      stay_intro_3:
        "Просторная планировка для большой семьи или компании друзей — несколько спален и место, чтобы отдыхать вместе.",
      stay_title_4: "Вилла 2",
      stay_intro_4:
        "Самый большой вариант для праздников и длительного отдыха — для многодневных встреч и частных мероприятий.",
      nav_tours: "Ресторан",
      tour_label_1: "Тур 1",
      tour_label_2: "Тур 2",
      tour_desc_1: "Тур природы и отдыха в зеленой среде Дилижана.",
      tour_desc_2: "Тур комфорта: джакузи, сауна и камин.",
      tour_title_1: "Тур: природа и отдых",
      tour_intro_1:
        "Неспешные дни с лесными тропами, смотровыми точками и паузами на отдых — для пар и небольших групп, которым важен свежий воздух без жёсткого расписания.",
      tour_title_2: "Тур: комфорт и релакс",
      tour_intro_2:
        "Акцент на отдыхе на территории: джакузи, сауна и время у камина — после прогулок или как отдельный день заботы о себе.",
      diving_heading: "Активный отдых и досуг",
      diving_label_1: "Diving 1",
      diving_label_2: "Diving 2",
      diving_desc_1: "Погружение 1 для начинающих и любителей приключений.",
      diving_desc_2: "Погружение 2 с более активной и глубокой программой.",
      diving_title_1: "Погружение для начинающих",
      diving_intro_1:
        "Вводные занятия с дополнительным сопровождением для тех, кто пробует впервые и хочет спокойную, поддерживающую сессию.",
      diving_title_2: "Погружение: активный маршрут",
      diving_intro_2:
        "Более длительные профили и активная программа для гостей с базой, которым нужны глубина и разнообразие.",
      nav_stay: "Преимущества",
      events_page_intro:
        "Туры и погружения на природе Дилижана. Подробности и запись — в WhatsApp.",
      nav_gallery: "Галерея",
      nav_videos: "Карта",
      nav_contact: "Контакты",
      hero_eyebrow: "Villas · Dilijan",
      hero_title: "Armenita Family Resort",
      hero_subtitle: "Семейные коттеджи, эко-отдых и закрытые мероприятия в центре Дилижана",
      hero_cta: "Связаться",
      hero_secondary: "Галерея",
      about_title: "О нас",
      about_lead: "Armenita Family Resort предлагает семейный и корпоративный отдых в комфортных коттеджах и виллах.",
      about_li1: "Семейные коттеджи для 2-10 гостей",
      about_li2: "Эко-коттеджи с джакузи, сауной и японской чаной",
      about_li3: "Вилла для 15-40 гостей, закрытые мероприятия до 60 гостей",
      about_li4: "Armenia, Dilijan, Armenia, 3901",
      about_book_now: "Book now",
      reviews_page_kicker: "Гости",
      reviews_page_title: "Отзывы гостей",
      reviews_page_lead: "Рейтинги и отзывы с Tripadvisor и от путешественников.",
      reviews_ratings_eyebrow: "Оценки путешественников",
      reviews_ratings_title: "Что говорят о нас гости",
      reviews_based_on: "На основе отзывов путешественников",
      reviews_tripadvisor_btn: "Читать на TripAdvisor",
      reviews_guest_label: "Отзыв гостя",
      booking_popup_title: "Забронировать проживание",
      booking_popup_lead:
        "Выберите даты и число гостей. Ориентировочная сумма в AMD отображается над кнопкой «Оплатить».",
      booking_label_checkin: "Дата заезда",
      booking_label_checkout: "Дата выезда",
      booking_label_stay_dates: "Период проживания",
      booking_label_stay_length: "Длительность проживания",
      booking_label_guests: "Гости",
      booking_label_accommodation_type: "Тип размещения",
      booking_error_dates: "Дата выезда не может быть раньше даты заезда.",
      booking_price_label: "Ориентировочная сумма",
      booking_price_currency: "AMD",
      booking_submit_pay: "Оплатить",
      booking_msg_payment_failed:
        "Не удалось открыть страницу оплаты. Заявка сохранена — свяжитесь с нами, чтобы завершить платёж.",
      booking_submit_check: "Отправить заявку",
      booking_label_children: "С детьми?",
      booking_label_children_count: "Сколько детей",
      booking_children_opt_no: "Нет",
      booking_children_opt_yes: "Да",
      booking_msg_intro: "Здравствуйте, хочу отправить заявку.",
      booking_msg_checkin: "Заезд",
      booking_msg_checkout: "Выезд",
      booking_msg_stay_length: "Длительность проживания",
      booking_msg_guests: "Гости",
      booking_msg_children: "Дети",
      booking_msg_accommodation: "Тип размещения",
      booking_msg_estimated_total: "Ориентировочная сумма",
      booking_msg_night_one: "ночь",
      booking_msg_night_many: "ночей",
      stay_title: "Почему мы",
      stay_card1_title: "Природа и отдых",
      stay_card1_text: "Отдых в коттеджах среди природы Дилижана.",
      stay_card2_title: "Комфорт",
      stay_card2_text: "Джакузи, сауна, камин и крытые беседки.",
      stay_card3_title: "Локация и мероприятия",
      stay_card3_text: "В центре Дилижана, мероприятия до 60 гостей.",
      gallery_title: "Галерея",
      gallery_empty_message: "Добавьте фото в поле Gallery в редакторе страницы.",
      videos_section_title: "Видео",
      videos_title: "Видео",
      videos_intro: "Видео также доступны на Facebook.",
      videos_cta: "Открыть Facebook",
      videos_fb_title: "Facebook",
      contact_title: "Контакты",
      contact_intro: "Позвоните нам или напишите в Instagram.",
      contact_address: "Armenia, Dilijan, Armenia, 3901",
      contact_phone: "094 605665",
      contact_instagram: "Instagram — @dilijan_villas",
      contact_facebook: "Facebook",
      contact_tagline: "Эко-отдых в центре Дилижана.",
      contact_map_aria: "Карта",
      contact_map_title: "Расположение",
      contact_map_link: "Открыть в Google Картах",
      footer_copy: "© Armenita Family Resort",
      footer_copy_full: "© 2024 Armenita Family Resort. Все права защищены.",
      footer_privacy: "Политика конфиденциальности",
      footer_terms: "Условия использования",
      footer_site_by: "Сайт создан",
      footer_credit_prefix: "Сайт создан ",
      footer_tag: "Армения",
      footer_top_aria: "Наверх",
    },
    en: {
      lang_select_aria: "Language",
      nav_reviews: "Reviews",
      nav_book_stay: "Book your stay",
      nav_dropdown_full_page: "Open full page",
      stay_heading: "Villas",
      stay_villas_heading: "Private Villa",
      stay_unit_kind_cottage: "Cottage",
      stay_unit_kind_villa: "Private villa",
      home_view_details: "View details",
      home_kind_tour: "Tour",
      home_kind_diving: "Diving",
      stay_section_whats_inside: "What's inside",
      stay_section_amenities: "Amenities",
      stay_section_room_features: "Room features",
      stay_section_room_types: "Room types",
      booking_label_name: "Name",
      booking_label_phone: "Phone",
      booking_label_email: "Email",
      booking_msg_available: "Available for selected dates.",
      booking_msg_unavailable: "Selected dates are not available.",
      booking_msg_checking: "Checking availability...",
      booking_msg_check_error: "Could not check availability. Please try again.",
      booking_msg_open_no_price: "These dates are open, but no price is published yet. Please contact us for a quote.",
      stay_label_1: "Cottage 1",
      stay_label_2: "Cottage 2",
      stay_label_3: "Private villa 1",
      stay_label_4: "Private villa 2",
      stay_desc_1: "Family cottage stay surrounded by Dilijan nature.",
      stay_desc_2: "Forest cottage stay for peaceful eco relaxation.",
      stay_desc_3: "Private villa stay for larger families or groups.",
      stay_desc_4: "Large private villa for events and long stays.",
      stay_title_1: "Cottage 1",
      stay_intro_1:
        "Our signature family cottage closest to the garden paths—ideal for small families who want quiet mornings and easy access to Dilijan's green surroundings.",
      stay_title_2: "Cottage 2",
      stay_intro_2:
        "Set deeper among the trees for a calmer, eco-minded stay—perfect when you want forest views and a slower pace.",
      stay_title_3: "Private villa 1",
      stay_intro_3:
        "Spacious layout for extended families or friend groups—multiple sleeping areas and room to spread out together.",
      stay_title_4: "Private villa 2",
      stay_intro_4:
        "Our largest option for celebrations and longer retreats—designed for multi-day gatherings and private events.",
      nav_tours: "Restaurant",
      tour_label_1: "Tour 1",
      tour_label_2: "Tour 2",
      tour_desc_1: "Nature and rest tour in Dilijan's green surroundings.",
      tour_desc_2: "Comfort tour with jacuzzi, sauna, and fireplace.",
      tour_title_1: "Nature & rest tour",
      tour_intro_1:
        "Easy-paced days around forests, viewpoints, and picnic-style stops—great for couples and small groups who want fresh air without a packed schedule.",
      tour_title_2: "Comfort & wellness tour",
      tour_intro_2:
        "Focused on time at the property: jacuzzi, sauna, and fireplace evenings—ideal after a hike or as a full pampering day.",
      diving_heading: "Adventure & Leisure",
      diving_label_1: "Diving 1",
      diving_label_2: "Diving 2",
      diving_desc_1: "Diving experience 1 for beginners and adventure seekers.",
      diving_desc_2: "Diving experience 2 with a deeper, more active route.",
      diving_title_1: "Diving — beginner friendly",
      diving_intro_1:
        "Intro-style sessions with extra coaching for first-timers and curious guests who want a calm, well-supported experience.",
      diving_title_2: "Diving — advanced route",
      diving_intro_2:
        "Longer profiles and a more active plan for guests who already have basics and want depth and variety.",
      nav_stay: "Events and activates",
      events_page_intro:
        "Tours and diving experiences in Dilijan's nature. Message us on WhatsApp for details and booking.",
      nav_gallery: "Gallery",
      nav_videos: "Map",
      nav_contact: "About us",
      hero_eyebrow: "Villas · Dilijan",
      hero_title: "Armenita Family Resort",
      hero_subtitle: "Family cottages, eco rest, and private events in central Dilijan",
      hero_cta: "Get in touch",
      hero_secondary: "Gallery",
      about_title: "About us",
      about_lead: "Armenita Family Resort offers family and corporate stays in comfortable cottages and private villas.",
      about_li1: "Family cottages for 2-10 guests",
      about_li2: "Eco cottages with jacuzzi, sauna, and Japanese tub",
      about_li3: "Private villa for 15-40 guests, closed events up to 60 guests",
      about_li4: "Armenia, Dilijan, Armenia, 3901",
      about_book_now: "Book now",
      reviews_page_kicker: "Guests",
      reviews_page_title: "Guest reviews",
      reviews_page_lead: "Ratings and feedback from Tripadvisor and travellers who stayed with us.",
      reviews_ratings_eyebrow: "Travellers' ratings",
      reviews_ratings_title: "What guests say about us",
      reviews_based_on: "Based on traveller reviews",
      reviews_tripadvisor_btn: "Read on TripAdvisor",
      reviews_guest_label: "Guest review",
      booking_popup_title: "Book your stay",
      booking_popup_lead:
        "Pick your dates and guest count. The estimated total in AMD appears above the Pay button when dates are set.",
      booking_label_checkin: "Check-in date",
      booking_label_checkout: "Check-out date",
      booking_label_stay_dates: "Stay period",
      booking_label_stay_length: "Stay length",
      booking_label_guests: "Guests",
      booking_label_accommodation_type: "Accommodation type",
      booking_error_dates: "Check-out must be on or after check-in.",
      booking_price_label: "Estimated total",
      booking_price_currency: "AMD",
      booking_submit_pay: "Pay",
      booking_msg_payment_failed:
        "We could not open the payment page. Your request was saved — please contact us to complete the payment.",
      booking_submit_check: "Check availability",
      booking_label_children: "Traveling with children?",
      booking_label_children_count: "How many children?",
      booking_children_opt_no: "No",
      booking_children_opt_yes: "Yes",
      booking_msg_intro: "Hello, I want to submit a booking request.",
      booking_msg_checkin: "Check-in",
      booking_msg_checkout: "Check-out",
      booking_msg_stay_length: "Stay length",
      booking_msg_guests: "Guests",
      booking_msg_children: "Children",
      booking_msg_accommodation: "Accommodation type",
      booking_msg_estimated_total: "Estimated total",
      booking_msg_night_one: "night",
      booking_msg_night_many: "nights",
      stay_title: "Why choose us",
      stay_card1_title: "Nature and rest",
      stay_card1_text: "Cottage stay in the green nature of Dilijan.",
      stay_card2_title: "Comfort",
      stay_card2_text: "Jacuzzi, sauna, fireplace, and covered pavilions.",
      stay_card3_title: "Location & events",
      stay_card3_text: "In central Dilijan, events for up to 60 guests.",
      gallery_title: "Gallery",
      gallery_empty_message: "Add photos in the Gallery field in the page editor.",
      videos_section_title: "Videos",
      videos_title: "Videos",
      videos_intro: "You can also watch our reels on Facebook.",
      videos_cta: "Open Facebook",
      videos_fb_title: "Facebook",
      contact_title: "Contact",
      contact_intro: "Call us or message us on Instagram.",
      contact_address: "Armenia, Dilijan, Armenia, 3901",
      contact_phone: "094 605665",
      contact_instagram: "Instagram — @dilijan_villas",
      contact_facebook: "Facebook",
      contact_tagline: "Eco rest in central Dilijan.",
      contact_map_aria: "Map",
      contact_map_title: "Location",
      contact_map_link: "Open in Google Maps",
      footer_copy: "© Armenita Family Resort",
      footer_copy_full: "© 2024 Armenita Family Resort. All rights reserved.",
      footer_privacy: "Privacy Policy",
      footer_terms: "Terms of Service",
      footer_site_by: "Site by",
      footer_credit_prefix: "Site by ",
      footer_tag: "Armenia",
      footer_top_aria: "Back to top",
    },
  };

  const prefersReducedMotion = () => window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  function getPageBaseHref() {
    const u = new URL(window.location.href);
    let path = u.pathname;
    if (!path.endsWith("/")) {
      path = path.replace(/\/[^/]+$/, "/");
    }
    u.pathname = path;
    u.hash = "";
    u.search = "";
    return u.href;
  }

  function getAssetResolveBase() {
    try {
      if (typeof document !== "undefined" && document.baseURI) {
        return document.baseURI;
      }
    } catch {}
    return getPageBaseHref();
  }

  function safeResolveUrl(rel, base) {
    const clean = String(rel).replace(/^\.\//, "");
    try {
      return new URL(clean, base).href;
    } catch {
      return null;
    }
  }

  function resolveAssetUrls() {
    const base = getAssetResolveBase();

    document.querySelectorAll("img[src], source[src]").forEach((el) => {
      const raw = el.getAttribute("src");
      if (!raw || /^https?:\/\//i.test(raw) || raw.startsWith("data:") || raw.startsWith("blob:")) return;
      const abs = safeResolveUrl(raw, base);
      if (abs) el.setAttribute("src", abs);
    });

    document.querySelectorAll("video[poster]").forEach((el) => {
      const raw = el.getAttribute("poster");
      if (!raw || /^https?:\/\//i.test(raw)) return;
      const abs = safeResolveUrl(raw, base);
      if (abs) el.setAttribute("poster", abs);
    });

    document.querySelectorAll("[style]").forEach((el) => {
      const st = el.getAttribute("style");
      if (!st || !st.includes("url(")) return;
      const next = st.replace(/url\(\s*['"]?([^'")]+)['"]?\s*\)/gi, (match, path) => {
        const p = String(path).trim();
        if (/^https?:\/\//i.test(p) || p.startsWith("data:")) return match;
        const abs = safeResolveUrl(p, base);
        return abs ? `url('${abs}')` : match;
      });
      if (next !== st) el.setAttribute("style", next);
    });
  }

  function initHeroBackdrop() {
    const staticEl = document.getElementById("heroStaticBg");
    const fallback = document.querySelector(".hero__video-fallback");
    const bannerRel = staticEl?.getAttribute("data-hero-banner");
    if (!bannerRel) return;

    let absBanner = bannerRel;
    try {
      absBanner = new URL(String(bannerRel).replace(/^\.\//, ""), getAssetResolveBase()).href;
    } catch {}

    const bg = `linear-gradient(160deg, rgba(10, 14, 20, 0.82) 0%, rgba(18, 26, 38, 0.68) 45%, rgba(6, 10, 14, 0.88) 100%), url("${absBanner}")`;
    if (staticEl) {
      staticEl.style.backgroundImage = bg;
      staticEl.style.backgroundSize = "cover";
      staticEl.style.backgroundPosition = "center";
    }
    if (fallback) {
      fallback.style.backgroundImage = bg;
      fallback.style.backgroundSize = "cover";
      fallback.style.backgroundPosition = "center";
    }
  }

  function getLang() {
    const htmlLang = (document.documentElement.getAttribute("lang") || "").toLowerCase();
    const normalizedHtmlLang = htmlLang.split("-")[0];
    if (normalizedHtmlLang && STRINGS[normalizedHtmlLang]) return normalizedHtmlLang;

    const saved = localStorage.getItem(STORAGE_KEY);
    return saved && STRINGS[saved] ? saved : "en";
  }

  function setLang(lang) {
    if (!STRINGS[lang]) return;
    localStorage.setItem(STORAGE_KEY, lang);
    const dict = STRINGS[lang];
    document.documentElement.lang = lang;

    document.querySelectorAll("[data-i18n]").forEach((el) => {
      const key = el.getAttribute("data-i18n");
      if (key && dict[key] != null) el.textContent = dict[key];
    });

    document.querySelectorAll("[data-i18n-aria]").forEach((el) => {
      const key = el.getAttribute("data-i18n-aria");
      if (key && dict[key] != null) el.setAttribute("aria-label", dict[key]);
    });

    // document.title намеренно не трогаем: <title> рендерит WordPress
    // (add_theme_support('title-tag') + ACF-поле "SEO Title"), и смена языка
    // всегда идёт с перезагрузкой страницы, так что заголовок уже корректный.
    document.querySelectorAll("[data-lang-select]").forEach((sel) => {
      if (STRINGS[lang]) sel.value = lang;
    });

    navDropdownPreviewRefreshers.forEach((fn) => fn());
  }

  function initLang() {
    setLang(getLang());
    document.querySelectorAll("[data-lang-select]").forEach((sel) => {
      sel.addEventListener("change", () => {
        const next = sel.value;
        if (!next || !STRINGS[next]) return;
        setLang(next);
        const header = document.getElementById("site-header");
        const mqMobile = window.matchMedia("(max-width: 920px)");
        if (header && mqMobile.matches) {
          header.classList.remove("header--nav-open");
          document.body.classList.remove("nav-open");
          const toggle = document.querySelector("[data-menu-toggle]");
          if (toggle) toggle.setAttribute("aria-expanded", "false");
          const navEl = document.getElementById("primary-nav");
          if (navEl) navEl.setAttribute("aria-hidden", "true");
        }
      });
    });
  }

  function initHeaderScrollState() {
    const header = document.getElementById("site-header");
    const wrap = document.querySelector("[data-header-hero-wrap]");
    if (!header) return;

    const setCompact = (on) => {
      header.classList.toggle("header--compact", on);
      document.documentElement.classList.toggle("header-is-compact", on);
    };

    if (!wrap) {
      setCompact(true);
      return;
    }

    /** Switch to fixed compact bar before the hero fully leaves (was rect.bottom <= 0). */
    const compactWhileHeroBottomPx = 650;

    const sync = () => {
      const rect = wrap.getBoundingClientRect();
      const pastHero = rect.bottom <= compactWhileHeroBottomPx;
      setCompact(pastHero);
    };

    window.addEventListener("scroll", sync, { passive: true });
    window.addEventListener("resize", sync);
    sync();
  }

  function initMobileNav() {
    const header = document.getElementById("site-header");
    const toggle = document.querySelector("[data-menu-toggle]");
    const backdrop = document.querySelector("[data-menu-close]");
    const nav = document.getElementById("primary-nav");
    if (!header || !toggle || !nav) return;

    const mq = window.matchMedia("(max-width: 920px)");
    let menuReturnFocus = null;

    const syncNavAria = () => {
      if (!mq.matches) {
        nav.removeAttribute("aria-hidden");
        return;
      }
      nav.setAttribute("aria-hidden", header.classList.contains("header--nav-open") ? "false" : "true");
    };

    const closeMenu = () => {
      if (!header.classList.contains("header--nav-open")) return;
      header.classList.remove("header--nav-open");
      document.body.classList.remove("nav-open");
      toggle.setAttribute("aria-expanded", "false");
      syncNavAria();
      if (menuReturnFocus && typeof menuReturnFocus.focus === "function") {
        try {
          menuReturnFocus.focus({ preventScroll: true });
        } catch {
          /* ignore */
        }
      }
      menuReturnFocus = null;
    };

    const openMenu = () => {
      if (header.classList.contains("header--nav-open")) return;
      menuReturnFocus = document.activeElement;
      header.classList.add("header--nav-open");
      document.body.classList.add("nav-open");
      toggle.setAttribute("aria-expanded", "true");
      syncNavAria();
      requestAnimationFrame(() => {
        const drawer = nav.querySelector(".header__menu-drawer");
        const first =
          drawer &&
          drawer.querySelector(
            'a.header__link[href], a.header__sublink[href], button.header__link:not([disabled])'
          );
        if (first && typeof first.focus === "function") {
          try {
            first.focus({ preventScroll: true });
          } catch {
            /* ignore */
          }
        }
      });
    };

    toggle.addEventListener("click", () => {
      if (!mq.matches) return;
      if (header.classList.contains("header--nav-open")) closeMenu();
      else openMenu();
    });
    if (backdrop) backdrop.addEventListener("click", closeMenu);
    // Close mobile menu only on real navigation links, not dropdown toggle buttons.
    header
      .querySelectorAll(
        "a.header__link, a.header__sublink, a.header__brand--nav, a.header__dropdown-hub-link"
      )
      .forEach((link) => link.addEventListener("click", closeMenu));

    document.addEventListener("keydown", (e) => {
      if (e.key !== "Escape" || !mq.matches) return;
      if (!header.classList.contains("header--nav-open")) return;
      e.preventDefault();
      closeMenu();
    });

    window.addEventListener("resize", () => {
      if (!mq.matches) closeMenu();
      syncNavAria();
    });
    syncNavAria();
  }

  function initNavDropdowns() {
    const roots = Array.from(document.querySelectorAll("[data-nav-dropdown]"));
    if (!roots.length) return;

    /** Hover-delay timers (desktop); one open dropdown closes all others (mobile + desktop). */
    const closeTimers = new WeakMap();

    const closeDropdown = (rootEl) => {
      const pending = closeTimers.get(rootEl);
      if (pending) {
        clearTimeout(pending);
        closeTimers.delete(rootEl);
      }
      rootEl.classList.remove("is-open");
      const tg = rootEl.querySelector("[data-dropdown-toggle]");
      if (tg) tg.setAttribute("aria-expanded", "false");
    };

    roots.forEach((root) => {
      const toggle = root.querySelector("[data-dropdown-toggle]");
      const dropdown = root.querySelector("[data-dropdown-panel]");
      const videoEl = root.querySelector("[data-dropdown-video]");
      const videoSourceEl = root.querySelector("[data-dropdown-video-source]");
      const imageEl = root.querySelector("[data-dropdown-image]");
      const descEl = root.querySelector("[data-dropdown-description]");
      const whatsappBtnEl = root.querySelector("[data-dropdown-whatsapp-button]");
      const panelTitleEl = root.querySelector("[data-dropdown-panel-title]");
      const panelIntroEl = root.querySelector("[data-dropdown-panel-intro]");
      const links = Array.from(root.querySelectorAll("[data-dropdown-link]"));
      if (!toggle || !dropdown || !descEl || !links.length) return;

      const isCurrentLink = (link) => {
        try {
          const here = new URL(window.location.href);
          const there = new URL(link.getAttribute("href") || "", window.location.href);
          if (there.pathname !== here.pathname) return false;
          if (there.search !== here.search) return false;
          if (there.hash) return there.hash === here.hash;
          return true;
        } catch {
          return false;
        }
      };

      const isMobile = () => window.matchMedia("(max-width: 920px)").matches;

      const positionDropdownInViewport = () => {
        if (isMobile()) {
          dropdown.style.removeProperty("--dropdown-shift");
          return;
        }
        dropdown.style.setProperty("--dropdown-shift", "0px");
        const rect = dropdown.getBoundingClientRect();
        const viewportWidth = document.documentElement.clientWidth || window.innerWidth || 0;
        const edgeGap = 8;
        let shift = 0;
        if (rect.left < edgeGap) shift = edgeGap - rect.left;
        if (rect.right > viewportWidth - edgeGap) shift = (viewportWidth - edgeGap) - rect.right;
        dropdown.style.setProperty("--dropdown-shift", `${Math.round(shift)}px`);
      };

      const close = () => closeDropdown(root);

      const open = () => {
        const ownPending = closeTimers.get(root);
        if (ownPending) {
          clearTimeout(ownPending);
          closeTimers.delete(root);
        }
        roots.forEach((other) => {
          if (other !== root) closeDropdown(other);
        });
        root.classList.add("is-open");
        toggle.setAttribute("aria-expanded", "true");
        requestAnimationFrame(positionDropdownInViewport);
      };

      const scheduleClose = () => {
        const prev = closeTimers.get(root);
        if (prev) clearTimeout(prev);
        closeTimers.set(
          root,
          setTimeout(() => {
            closeTimers.delete(root);
            closeDropdown(root);
          }, 140)
        );
      };

      const setPreview = (link, { markActive = true } = {}) => {
        const video = link.getAttribute("data-dropdown-video");
        const image = link.getAttribute("data-dropdown-image");
        const descKey = link.getAttribute("data-dropdown-desc-key");
        const descHtml = link.getAttribute("data-dropdown-desc-html");
        const whatsappFlag = link.getAttribute("data-dropdown-whatsapp");
        const whatsappUrl = link.getAttribute("data-dropdown-whatsapp-url");
        const dict = STRINGS[getLang()] || {};
        const hasVideo = Boolean(video && videoSourceEl && videoEl);
        const hasImage = Boolean(image && imageEl);

        if (videoEl) {
          if (hasVideo) {
            videoEl.hidden = false;
            if (videoSourceEl.getAttribute("src") !== video) {
              videoSourceEl.setAttribute("src", video);
              syncVideoSourceMime(videoSourceEl);
              videoEl.load();
            }
            videoEl.play().catch(() => {});
          } else {
            videoEl.pause?.();
            videoEl.hidden = true;
          }
        }

        if (imageEl) {
          if (hasVideo) {
            // Always prioritize video when both URLs are present.
            imageEl.hidden = true;
          } else if (hasImage) {
            imageEl.hidden = false;
            imageEl.setAttribute("src", image);
          } else {
            imageEl.hidden = true;
          }
        }
        if (link.hasAttribute("data-dropdown-desc-html")) {
          // Rich description from WP editor (ACF description_menu).
          descEl.innerHTML = descHtml || "";
        } else if (descKey) {
          const text = dict[descKey] || "";
          if (text) descEl.textContent = text;
        }
        if (whatsappBtnEl) {
          if (whatsappFlag === "1") {
            whatsappBtnEl.hidden = false;
            if (whatsappUrl) {
              let finalWhatsappUrl = whatsappUrl;
              const selectedName = (link.textContent || "").trim();
              const menuName = (toggle.textContent || "").trim();
              const defaultMessage = selectedName
                ? `Hello, I want to book: ${selectedName}.`
                : menuName
                  ? `Hello, I want to book: ${menuName}.`
                  : "Hello, I want to book a stay.";

              try {
                const waUrl = new URL(whatsappUrl, window.location.href);
                waUrl.searchParams.set("text", defaultMessage);
                finalWhatsappUrl = waUrl.toString();
              } catch {
                finalWhatsappUrl = whatsappUrl;
              }

              whatsappBtnEl.setAttribute("href", finalWhatsappUrl);
            }
          } else {
            whatsappBtnEl.hidden = true;
          }
        }
        const titleKey = link.getAttribute("data-dropdown-title-key");
        const introKey = link.getAttribute("data-dropdown-intro-key");
        if (panelTitleEl && titleKey) {
          const t = dict[titleKey];
          if (t != null && t !== "") panelTitleEl.textContent = t;
        }
        if (panelIntroEl && introKey) {
          const t = dict[introKey];
          if (t != null && t !== "") panelIntroEl.textContent = t;
        }
        links.forEach((item) => item.classList.toggle("header__sublink--active", markActive && item === link));
      };

      const syncPreviewToLang = () => {
        const matched = links.find((link) => isCurrentLink(link));
        if (matched) {
          setPreview(matched, { markActive: true });
        } else {
          const initial = links.find((link) => link.classList.contains("header__sublink--active")) || links[0];
          if (initial) setPreview(initial, { markActive: false });
        }
      };
      navDropdownPreviewRefreshers.push(syncPreviewToLang);
      syncPreviewToLang();

      links.forEach((link) => {
        link.addEventListener("mouseenter", () => setPreview(link));
        link.addEventListener("focus", () => setPreview(link));
        link.addEventListener("click", () => {
          setPreview(link);
          if (isMobile()) close();
        });
      });

      toggle.addEventListener("click", (e) => {
        const targetUrl = toggle.getAttribute("data-dropdown-url");
        if (targetUrl && !isMobile()) {
          window.location.href = targetUrl;
          return;
        }
        if (!isMobile()) return;
        e.preventDefault();
        if (root.classList.contains("is-open")) close();
        else open();
      });

      root.addEventListener("mouseleave", () => {
        if (!isMobile()) scheduleClose();
      });
      root.addEventListener("mouseenter", () => {
        if (!isMobile()) open();
      });

      window.addEventListener("resize", () => {
        if (root.classList.contains("is-open")) {
          requestAnimationFrame(positionDropdownInViewport);
        }
      });
    });

    window.addEventListener("resize", () => {
      roots.forEach((r) => closeDropdown(r));
    });
  }

  function initScrollButtons() {
    const down = document.querySelector("[data-scroll-down]");
    const hero = document.getElementById("hero");
    if (down) {
      down.addEventListener("click", () => {
        const nextSection = hero
          ? hero.closest(".header-hero-wrap")?.nextElementSibling || hero.parentElement?.nextElementSibling
          : null;
        if (nextSection && typeof nextSection.scrollIntoView === "function") {
          nextSection.scrollIntoView({ behavior: prefersReducedMotion() ? "auto" : "smooth" });
        }
      });
    }

    const topBtn = document.querySelector("[data-scroll-top]");
    if (!topBtn) return;
    let isShown = false;
    const sync = () => {
      const y = window.scrollY;
      const nextShown = isShown ? y > 240 : y > 320;
      if (nextShown === isShown) return;
      isShown = nextShown;
      topBtn.hidden = !isShown;
      topBtn.setAttribute("aria-hidden", isShown ? "false" : "true");
      const floatingWa = document.querySelector("[data-floating-whatsapp]");
      if (floatingWa) {
        floatingWa.classList.toggle("floating-wa--raised", isShown);
      }
    };
    topBtn.addEventListener("click", () => window.scrollTo({ top: 0, behavior: prefersReducedMotion() ? "auto" : "smooth" }));
    window.addEventListener("scroll", sync, { passive: true });
    sync();
  }

  /** Scroll-linked parallax for section backgrounds (`[data-parallax-bg]` on index, etc.). */
  function initSectionParallaxBackgrounds() {
    if (prefersReducedMotion()) return;
    const layers = Array.from(document.querySelectorAll("[data-parallax-bg]"));
    if (!layers.length) return;

    const readSpeed = (el) => {
      const raw = getComputedStyle(el).getPropertyValue("--parallax-speed").trim();
      const n = parseFloat(raw);
      return Number.isFinite(n) && n > 0 ? n : 0.12;
    };

    let ticking = false;
    const update = () => {
      ticking = false;
      const vh = window.innerHeight || 1;
      layers.forEach((el) => {
        const host = el.closest(".section, .about-cinematic, .hero");
        if (!host) return;
        const rect = host.getBoundingClientRect();
        if (rect.bottom < -vh || rect.top > vh * 2) {
          el.style.transform = "translate3d(0, 0, 0)";
          return;
        }
        const centerOffset = rect.top + rect.height / 2 - vh / 2;
        const y = centerOffset * readSpeed(el) * 1.35;
        el.style.transform = `translate3d(0, ${y.toFixed(2)}px, 0)`;
      });
    };

    const onScrollOrResize = () => {
      if (!ticking) {
        ticking = true;
        requestAnimationFrame(update);
      }
    };

    window.addEventListener("scroll", onScrollOrResize, { passive: true });
    window.addEventListener("resize", onScrollOrResize);
    onScrollOrResize();
  }

  const VIDEO_MIME_BY_EXT = {
    mp4: "video/mp4",
    m4v: "video/mp4",
    webm: "video/webm",
    mov: "video/quicktime",
    qt: "video/quicktime",
    ogv: "video/ogg",
    ogg: "video/ogg",
  };

  function getVideoMimeFromUrl(url) {
    if (!url) return "video/mp4";
    const clean = String(url).split("?")[0].split("#")[0];
    const parts = clean.split(".");
    const ext = parts.length > 1 ? parts.pop().toLowerCase() : "";
    return VIDEO_MIME_BY_EXT[ext] || "video/mp4";
  }

  function syncVideoSourceMime(sourceEl) {
    if (!sourceEl) return;
    const src = sourceEl.getAttribute("src") || "";
    if (!src) return;
    sourceEl.setAttribute("type", getVideoMimeFromUrl(src));
  }

  function initVideoSourceMimeTypes() {
    document.querySelectorAll("video source[src]").forEach((source) => syncVideoSourceMime(source));
  }

  /** Run after the page is fully loaded (and the main thread is idle) so heavy media never blocks first paint. */
  function afterPageLoad(callback) {
    const schedule = () => {
      if (typeof requestIdleCallback === "function") {
        requestIdleCallback(callback, { timeout: 1500 });
      } else {
        setTimeout(callback, 150);
      }
    };

    if (document.readyState === "complete") schedule();
    else window.addEventListener("load", schedule, { once: true });
  }

  function initVideo() {
    initVideoSourceMimeTypes();

    document.querySelectorAll(".hero__video").forEach((heroVideo) => {
      if (!(heroVideo instanceof HTMLVideoElement)) return;

      const heroSource = heroVideo.querySelector("source[src], source[data-src]");
      if (heroSource) syncVideoSourceMime(heroSource);

      const src = (
        heroSource?.getAttribute("src") ||
        heroSource?.getAttribute("data-src") ||
        heroVideo.getAttribute("src") ||
        heroVideo.getAttribute("data-src") ||
        ""
      ).trim();
      if (!src) {
        heroVideo.classList.add("is-hidden");
        return;
      }

      heroVideo.removeAttribute("hidden");
      heroVideo.muted = true;
      heroVideo.defaultMuted = true;
      heroVideo.playsInline = true;
      heroVideo.setAttribute("muted", "");
      heroVideo.setAttribute("playsinline", "");

      const tryPlay = () => {
        const playPromise = heroVideo.play();
        if (playPromise && typeof playPromise.catch === "function") {
          playPromise.catch(() => {});
        }
      };

      heroVideo.addEventListener("loadeddata", tryPlay);
      heroVideo.addEventListener("canplay", tryPlay);
      heroVideo.addEventListener(
        "error",
        () => {
          heroVideo.classList.add("is-hidden");
        },
        { once: true }
      );

      afterPageLoad(() => {
        if (heroSource && !heroSource.getAttribute("src")) {
          heroSource.setAttribute("src", src);
          syncVideoSourceMime(heroSource);
        } else if (!heroSource && !heroVideo.getAttribute("src")) {
          heroVideo.setAttribute("src", src);
        }
        if (heroVideo.preload === "none") heroVideo.preload = "auto";
        heroVideo.load();
        tryPlay();
      });
    });
  }

  function initVideoGallery() {
    const videos = document.querySelectorAll(".video-card__inner video");
    videos.forEach((v) => {
      v.addEventListener("play", () => {
        videos.forEach((o) => {
          if (o !== v) o.pause();
        });
      });
    });
  }

  /** Keep home / events quick-card videos playing (muted autoplay is often blocked until play()). */
  function initEventsQuickVideos() {
    const videos = Array.from(document.querySelectorAll(".events-quick__video"));
    if (!videos.length) return;

    let pageLoaded = false;
    const visible = new Set();

    /** Настоящий src подставляем только после window.load — до этого в карточке виден poster. */
    const ensureSource = (video) => {
      if (!pageLoaded) return false;

      const lazySource = video.querySelector("source[data-src]:not([src])");
      if (lazySource) {
        const nextSrc = (lazySource.getAttribute("data-src") || "").trim();
        if (nextSrc) {
          lazySource.setAttribute("src", nextSrc);
          syncVideoSourceMime(lazySource);
          if (video.preload === "none") video.preload = "auto";
          video.load();
        }
      } else if (!video.getAttribute("src") && video.getAttribute("data-src")) {
        video.setAttribute("src", (video.getAttribute("data-src") || "").trim());
        if (video.preload === "none") video.preload = "auto";
        video.load();
      }
      return true;
    };

    const tryPlay = (video) => {
      if (!(video instanceof HTMLVideoElement)) return;
      video.muted = true;
      video.defaultMuted = true;
      video.playsInline = true;
      video.setAttribute("muted", "");
      video.setAttribute("playsinline", "");
      if (!ensureSource(video)) return;
      const playPromise = video.play();
      if (playPromise && typeof playPromise.catch === "function") {
        playPromise.catch(() => {});
      }
    };

    videos.forEach((video) => {
      video.addEventListener("loadeddata", () => tryPlay(video), { once: true });
      video.addEventListener("canplay", () => tryPlay(video), { once: true });
    });

    if (typeof IntersectionObserver === "undefined") {
      afterPageLoad(() => {
        pageLoaded = true;
        videos.forEach(tryPlay);
      });
      return;
    }

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          const video = entry.target;
          if (!(video instanceof HTMLVideoElement)) return;
          if (entry.isIntersecting) {
            visible.add(video);
            tryPlay(video);
          } else {
            visible.delete(video);
            if (!video.paused) video.pause();
          }
        });
      },
      { rootMargin: "10% 0px", threshold: 0.15 }
    );

    videos.forEach((video) => observer.observe(video));

    afterPageLoad(() => {
      pageLoaded = true;
      visible.forEach(tryPlay);
    });
  }

  function initReveal() {
    const items = document.querySelectorAll("[data-reveal]");
    if (!items.length) return;

    if (prefersReducedMotion() || typeof IntersectionObserver === "undefined") {
      items.forEach((el) => el.classList.add("reveal--visible"));
      return;
    }

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;
          entry.target.classList.add("reveal--visible");
          observer.unobserve(entry.target);
        });
      },
      { rootMargin: "0px 0px -8% 0px", threshold: 0.08 }
    );

    items.forEach((el) => observer.observe(el));
  }

  function initAboutSliders() {
    const sliders = Array.from(document.querySelectorAll("[data-about-slider]"));
    if (!sliders.length) return;

    sliders.forEach((slider) => {
      if (slider.dataset.aboutSliderInitialized === "1") return;
      slider.dataset.aboutSliderInitialized = "1";
      const slides = Array.from(slider.querySelectorAll(".about-slider__img"));
      const prev = slider.querySelector("[data-about-prev]");
      const next = slider.querySelector("[data-about-next]");
      if (!slides.length) return;

      let current = Math.max(0, slides.findIndex((img) => img.classList.contains("is-active")));
      if (current < 0) current = 0;

      const show = (idx) => {
        current = ((idx % slides.length) + slides.length) % slides.length;
        slides.forEach((img, i) => img.classList.toggle("is-active", i === current));
      };

      if (prev) prev.addEventListener("click", () => show(current - 1));
      if (next) next.addEventListener("click", () => show(current + 1));

      setInterval(() => show(current + 1), 4500);
      show(current);
    });
  }

  function initGalleryLightbox() {
    const root = document.getElementById("lightbox");
    if (!root) return;
    const imgEl = root.querySelector(".lightbox__img");
    const counter = root.querySelector("[data-lightbox-counter]");
    const btnPrev = root.querySelector("[data-lightbox-prev]");
    const btnNext = root.querySelector("[data-lightbox-next]");
    const btnClose = root.querySelector("[data-lightbox-close]");
    const backdrop = root.querySelector(".lightbox__backdrop");
    const openButtons = Array.from(
      document.querySelectorAll("[data-gallery-root] [data-gallery-open], #galleryGrid [data-gallery-open]")
    );
    // Prefer unique buttons (grid may match both selectors).
    const uniqueButtons = Array.from(new Set(openButtons));
    const urls = uniqueButtons
      .map((btn) => btn.getAttribute("data-gallery-full") || btn.querySelector("img")?.getAttribute("src"))
      .filter(Boolean)
      .map((src) => safeResolveUrl(src, getAssetResolveBase()) || src);

    let current = 0;

    function show() {
      if (!imgEl || !urls.length) return;
      const idx = ((current % urls.length) + urls.length) % urls.length;
      current = idx;
      imgEl.src = urls[idx];
      if (counter) counter.textContent = `${idx + 1} / ${urls.length}`;
    }

    function open(index) {
      if (!urls.length) return;
      current = index;
      root.hidden = false;
      root.setAttribute("aria-hidden", "false");
      document.body.classList.add("lightbox-open");
      show();
    }

    function close() {
      root.hidden = true;
      root.setAttribute("aria-hidden", "true");
      document.body.classList.remove("lightbox-open");
      if (imgEl) imgEl.removeAttribute("src");
    }

    uniqueButtons.forEach((btn, idx) => {
      btn.addEventListener("click", (e) => {
        e.preventDefault();
        open(idx);
      });
    });

    if (btnPrev) btnPrev.addEventListener("click", () => { current -= 1; show(); });
    if (btnNext) btnNext.addEventListener("click", () => { current += 1; show(); });
    if (btnClose) btnClose.addEventListener("click", close);
    if (backdrop) backdrop.addEventListener("click", close);
    document.addEventListener("keydown", (e) => {
      if (root.hidden) return;
      if (e.key === "Escape") close();
      if (e.key === "ArrowLeft") {
        current -= 1;
        show();
      }
      if (e.key === "ArrowRight") {
        current += 1;
        show();
      }
    });
  }

  function initStayUnitDetails() {
    const section = document.getElementById("stay-unit-details");
    if (!section) return;

    const units = {
      "cottage-1": {
        kind: "Cottage",
        kindI18nKey: "stay_unit_kind_cottage",
        title: "Cottage 1",
        accommodation: "Cottage 1",
        intro: "Family cottage stay surrounded by Dilijan nature.",
        video: "./videos/1273967704269299.mp4",
        image1: "./images/504327955_17896732026230600_4835684111846667008_n.jpg",
        image2: "./images/473376954_122159344094307960_6278352885856868274_n.jpg",
        options: [
          { icon: "👥", label: "Up to 4 guests" },
          { icon: "🛁", label: "Jacuzzi available" },
          { icon: "🔥", label: "Fireplace" },
          { icon: "📶", label: "Free WiFi" }
        ],
        inside: [
          { icon: "🛏", label: "One-bedroom layout" },
          { icon: "🪟", label: "Private terrace" },
          { icon: "🏞", label: "Mountain-view windows" },
          { icon: "🪵", label: "Warm wood interior" }
        ],
        amenities: [
          { icon: "📶", label: "High-speed WiFi" },
          { icon: "🚿", label: "Hot shower" },
          { icon: "☕", label: "Coffee and tea set" },
          { icon: "🅿️", label: "Free parking" },
          { icon: "🔥", label: "Fireplace", extra: true },
          { icon: "🧴", label: "Bath essentials", extra: true }
        ],
        features: [
          { icon: "❄️", label: "Air conditioning" },
          { icon: "🛏", label: "Premium bedding" },
          { icon: "📺", label: "Cable / satellite TV" },
          { icon: "🚿", label: "Walk-in shower" },
          { icon: "🌲", label: "Forest view" }
        ],
        types: ["One-bedroom cottage", "Family cottage"],
        waText: "Hello, I would like to book Cottage 1."
      },
      "cottage-2": {
        kind: "Cottage",
        kindI18nKey: "stay_unit_kind_cottage",
        title: "Cottage 2",
        accommodation: "Cottage 2",
        intro: "Forest cottage with eco atmosphere for peaceful rest.",
        video: "./videos/1538888158242097.mp4",
        image1: "./images/514012761_17898102333230600_2575461312152910766_n.jpg",
        image2: "./images/504519449_17899676193230600_2067184941665652975_n.jpg",
        options: [
          { icon: "👥", label: "Up to 4 guests" },
          { icon: "🍃", label: "Eco design" },
          { icon: "🛁", label: "Jacuzzi" },
          { icon: "📶", label: "Free WiFi" }
        ],
        inside: [
          { icon: "🛏", label: "Two cozy bedrooms" },
          { icon: "🌿", label: "Private outdoor area" },
          { icon: "🪟", label: "Large forest-facing windows" },
          { icon: "🍃", label: "Natural eco materials" }
        ],
        amenities: [
          { icon: "📶", label: "High-speed WiFi" },
          { icon: "🛁", label: "Jacuzzi" },
          { icon: "🌿", label: "Eco-friendly design" },
          { icon: "🅿️", label: "Free parking" },
          { icon: "☕", label: "Breakfast on request", extra: true },
          { icon: "🐾", label: "Pet-friendly area", extra: true }
        ],
        features: [
          { icon: "❄️", label: "Air conditioning" },
          { icon: "🔥", label: "Fireplace" },
          { icon: "🛏", label: "Extra long beds" },
          { icon: "📺", label: "Smart TV" },
          { icon: "🌲", label: "Forest balcony" },
          { icon: "🍳", label: "Kitchenette" }
        ],
        types: ["Forest cottage", "Cottage with jacuzzi"],
        waText: "Hello, I would like to book Cottage 2."
      },
      "villa-1": {
        kind: "Private villa",
        kindI18nKey: "stay_unit_kind_villa",
        title: "Private villa 1",
        accommodation: "Private villa 1",
        intro: "Spacious villa designed for families and friend groups.",
        video: "./videos/2440293959732669.mp4",
        image1: "./images/658903849_17930385360230600_7047285425196588211_n.jpg",
        image2: "./images/518675308_17899676184230600_7116829930131710070_n.jpg",
        options: [
          { icon: "👥", label: "Up to 10 guests" },
          { icon: "🛏", label: "Multiple bedrooms" },
          { icon: "🔥", label: "Fireplace" },
          { icon: "📶", label: "Free WiFi" }
        ],
        inside: [
          { icon: "🛋", label: "Large lounge area" },
          { icon: "🍽", label: "Dining for up to 10" },
          { icon: "🛏", label: "Multiple bedrooms" },
          { icon: "🌳", label: "Shared terrace and garden" }
        ],
        amenities: [
          { icon: "📶", label: "High-speed WiFi" },
          { icon: "🔥", label: "Fireplace" },
          { icon: "🍳", label: "Full kitchen" },
          { icon: "🅿️", label: "Free parking" },
          { icon: "🏞", label: "Mountain view", extra: true },
          { icon: "🎶", label: "Speaker system", extra: true }
        ],
        features: [
          { icon: "❄️", label: "Air conditioning" },
          { icon: "🛏", label: "Multiple bedrooms" },
          { icon: "🚿", label: "Multiple bathrooms" },
          { icon: "📺", label: "Smart TV" },
          { icon: "🪟", label: "Large terrace" },
          { icon: "🍽", label: "Group dining area" }
        ],
        types: ["Private villa", "Family rooms", "Mountain-view rooms"],
        waText: "Hello, I would like to book Private villa 1."
      },
      "villa-2": {
        kind: "Private villa",
        kindI18nKey: "stay_unit_kind_villa",
        title: "Private villa 2",
        accommodation: "Private villa 2",
        intro: "Premium private villa for longer stays and events.",
        video: "./videos/1273967704269299.mp4",
        image1: "./images/506132448_122183172770307960_8060175010565024578_n.jpg",
        image2: "./images/504327955_17896732026230600_4835684111846667008_n.jpg",
        options: [
          { icon: "👥", label: "Up to 12 guests" },
          { icon: "🛁", label: "Spa amenities" },
          { icon: "🎉", label: "Events ready" },
          { icon: "📶", label: "Free WiFi" }
        ],
        inside: [
          { icon: "💎", label: "Premium suites" },
          { icon: "🛁", label: "Spa-style bathrooms" },
          { icon: "🎉", label: "Large event-ready terrace" },
          { icon: "🛋", label: "Private indoor lounge" }
        ],
        amenities: [
          { icon: "📶", label: "High-speed WiFi" },
          { icon: "🛁", label: "Jacuzzi and sauna" },
          { icon: "🍳", label: "Full kitchen" },
          { icon: "🎉", label: "Event-ready spaces" },
          { icon: "🅿️", label: "Free parking", extra: true },
          { icon: "🚿", label: "Spa bathrooms", extra: true }
        ],
        features: [
          { icon: "❄️", label: "Air conditioning" },
          { icon: "💎", label: "Premium suites" },
          { icon: "🍽", label: "Dining for groups" },
          { icon: "🪟", label: "Large terrace" },
          { icon: "🎶", label: "Speaker system" },
          { icon: "📺", label: "Smart TV" }
        ],
        types: ["Premium suite", "Event-ready villa", "Mountain-view villa"],
        waText: "Hello, I would like to book Private villa 2."
      }
    };

    const params = new URLSearchParams(window.location.search);
    const unitKey = params.get("unit") || "cottage-1";
    const data = units[unitKey] || units["cottage-1"];

    const kindEl = section.querySelector("[data-stay-unit-kind]");
    const titleEl = section.querySelector("[data-stay-unit-title]");
    const introEl = section.querySelector("[data-stay-unit-intro]");
    const videoEl = section.querySelector("[data-stay-unit-video]");
    const imageEl1 = section.querySelector("[data-stay-unit-image-1]");
    const imageEl2 = section.querySelector("[data-stay-unit-image-2]");
    const optionsEl = section.querySelector("[data-stay-unit-options]");
    const insideEl = section.querySelector("[data-stay-unit-inside]");
    const amenitiesEl = section.querySelector("[data-stay-unit-amenities]");
    const featuresEl = section.querySelector("[data-stay-unit-features]");
    const typesEl = section.querySelector("[data-stay-unit-types]");
    const popupAmenitiesEl = document.querySelector("[data-stay-popup-amenities]");
    const popupFeaturesEl = document.querySelector("[data-stay-popup-features]");
    const popupTypesEl = document.querySelector("[data-stay-popup-types]");
    const accommodationEl = document.querySelector("[data-stay-unit-accommodation]");

    if (kindEl) {
      const dict = STRINGS[getLang()] || STRINGS.en;
      if (data.kindI18nKey) {
        kindEl.setAttribute("data-i18n", data.kindI18nKey);
        kindEl.textContent = dict[data.kindI18nKey] || data.kind;
      } else {
        kindEl.removeAttribute("data-i18n");
        kindEl.textContent = data.kind;
      }
    }
    if (titleEl) titleEl.textContent = data.title;
    if (introEl) introEl.textContent = data.intro;

    if (videoEl) {
      const source = videoEl.querySelector("source");
      const resolvedVideo = safeResolveUrl(data.video, getAssetResolveBase()) || data.video;
      if (source) source.src = resolvedVideo;
      videoEl.load();
      try { videoEl.play(); } catch {}
    }

    if (imageEl1) {
      const resolvedImage1 = safeResolveUrl(data.image1, getAssetResolveBase()) || data.image1;
      imageEl1.src = resolvedImage1;
      imageEl1.alt = `${data.title} view 1`;
    }
    if (imageEl2) {
      const resolvedImage2 = safeResolveUrl(data.image2, getAssetResolveBase()) || data.image2;
      imageEl2.src = resolvedImage2;
      imageEl2.alt = `${data.title} view 2`;
    }
    if (optionsEl) {
      optionsEl.innerHTML = (data.options || [])
        .map((item) => `<li><span aria-hidden="true">${item.icon}</span> ${item.label}</li>`)
        .join("");
    }

    const fillList = (listEl, values, withIcons = false) => {
      if (!listEl) return;
      listEl.innerHTML = values.map((value) => {
        if (withIcons && typeof value === "object") {
          return `<li class="${value.extra ? "is-extra" : ""}"><span aria-hidden="true">${value.icon || "•"}</span>${value.label || ""}</li>`;
        }
        return `<li>${typeof value === "string" ? value : value.label || ""}</li>`;
      }).join("");
    };
    fillList(insideEl, data.inside, true);
    fillList(amenitiesEl, data.amenities, true);
    fillList(featuresEl, data.features, true);
    fillList(
      typesEl,
      data.types.map((label, i) => ({ icon: ["🏡", "👨‍👩‍👧", "🏞"][i] || "✓", label })),
      true
    );
    fillList(popupAmenitiesEl, data.amenities, true);
    fillList(popupFeaturesEl, data.features.map((item, i) => ({ icon: item.icon || ["❄️", "🛏", "🚿", "📺", "🪟", "🍽"][i] || "✓", label: item.label || "", extra: i > 3 })), true);
    fillList(popupTypesEl, data.types.map((label, i) => ({ icon: ["🏡", "👨‍👩‍👧", "🏞"][i] || "✓", label })), true);

    if (accommodationEl) accommodationEl.value = data.accommodation;
  }

  function initAmenitiesGroups() {
    const groups = Array.from(document.querySelectorAll("[data-amenities-group]"));
    if (!groups.length) return;

    groups.forEach((group) => {
      const btn = group.querySelector("[data-amenities-more]");
      if (!btn) return;

      const labelMore = "Show more";
      const labelLess = "Show less";

      btn.addEventListener("click", () => {
        const isOpen = group.classList.toggle("is-open");
        btn.setAttribute("aria-expanded", isOpen ? "true" : "false");
        const chev = btn.querySelector(".amenities__chev");
        const chevHtml = chev ? chev.outerHTML : "";
        btn.innerHTML = (isOpen ? labelLess : labelMore) + " " + chevHtml;
      });
    });
  }

  function initContactPopup() {
    const popup = document.querySelector("[data-contact-popup]");
    if (!popup) return;

    const openBtns = Array.from(document.querySelectorAll("[data-contact-popup-open]"));
    const closeBtns = Array.from(popup.querySelectorAll("[data-contact-popup-close]"));

    const open = () => {
      popup.removeAttribute("hidden");
      popup.setAttribute("aria-hidden", "false");
      document.body.classList.add("popup-open");
    };

    const close = () => {
      popup.setAttribute("hidden", "");
      popup.setAttribute("aria-hidden", "true");
      document.body.classList.remove("popup-open");
    };

    openBtns.forEach((btn) => btn.addEventListener("click", open));
    closeBtns.forEach((btn) => btn.addEventListener("click", close));

    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && !popup.hasAttribute("hidden")) close();
    });
  }

  function initBookingPopup() {
    const popup = document.querySelector("[data-booking-popup]");
    if (!popup) return;

    const openBtns = Array.from(document.querySelectorAll("[data-booking-popup-open]"));
    const closeBtns = Array.from(popup.querySelectorAll("[data-booking-popup-close]"));

    const open = () => {
      popup.removeAttribute("hidden");
      popup.setAttribute("aria-hidden", "false");
      document.body.classList.add("popup-open");
    };

    const close = () => {
      popup.setAttribute("hidden", "");
      popup.setAttribute("aria-hidden", "true");
      document.body.classList.remove("popup-open");
    };

    openBtns.forEach((btn) => btn.addEventListener("click", () => open()));
    closeBtns.forEach((btn) => btn.addEventListener("click", close));

    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && !popup.hasAttribute("hidden")) close();
    });
  }

  function initStaySliders() {
    const sliders = Array.from(document.querySelectorAll("[data-stay-slider]"));
    if (!sliders.length) return;

    sliders.forEach((slider) => {
      const slides = Array.from(slider.querySelectorAll("[data-stay-slide]"));
      const prevBtn = slider.querySelector("[data-stay-prev]");
      const nextBtn = slider.querySelector("[data-stay-next]");
      const dotsWrap = slider.querySelector("[data-stay-dots]");
      if (!slides.length) return;

      let current = Math.max(0, slides.findIndex((s) => s.classList.contains("is-active")));
      if (current < 0) current = 0;

      if (slides.length < 2) {
        if (prevBtn) prevBtn.hidden = true;
        if (nextBtn) nextBtn.hidden = true;
        if (dotsWrap) dotsWrap.hidden = true;
        slider.classList.add("stay-slider--single");
        slides.forEach((slide, i) => slide.classList.toggle("is-active", i === 0));
        const onlyVideo = slides[0] && slides[0].querySelector("[data-stay-slide-video], video");
        if (onlyVideo) {
          try { onlyVideo.play(); } catch {}
        }
        return;
      }

      const dots = slides.map((_, i) => {
        const dot = document.createElement("button");
        dot.type = "button";
        dot.className = "stay-slider__dot";
        dot.setAttribute("aria-label", `Slide ${i + 1}`);
        dot.addEventListener("click", () => show(i));
        if (dotsWrap) dotsWrap.appendChild(dot);
        return dot;
      });

      function syncVideos(activeIndex) {
        slides.forEach((slide, i) => {
          const video = slide.querySelector("[data-stay-slide-video]");
          if (!video) return;
          if (i === activeIndex) {
            try { video.play(); } catch {}
          } else {
            try {
              video.pause();
              video.currentTime = 0;
            } catch {}
          }
        });
      }

      function show(idx) {
        current = ((idx % slides.length) + slides.length) % slides.length;
        slides.forEach((slide, i) => slide.classList.toggle("is-active", i === current));
        dots.forEach((dot, i) => dot.classList.toggle("is-active", i === current));
        syncVideos(current);
      }

      if (prevBtn) prevBtn.addEventListener("click", () => show(current - 1));
      if (nextBtn) nextBtn.addEventListener("click", () => show(current + 1));

      let timer = setInterval(() => show(current + 1), 5500);
      slider.addEventListener("mouseenter", () => clearInterval(timer));
      slider.addEventListener("mouseleave", () => {
        clearInterval(timer);
        timer = setInterval(() => show(current + 1), 5500);
      });

      show(current);
    });
  }

  function initBookingSelectors() {
    const forms = Array.from(document.querySelectorAll("[data-booking-form]"));
    if (!forms.length) return;

    const bookingConfig = (window.DV_BOOKING && typeof window.DV_BOOKING === "object") ? window.DV_BOOKING : null;
    const ajaxUrl = bookingConfig && bookingConfig.ajaxUrl ? String(bookingConfig.ajaxUrl) : "";
    const nonce = bookingConfig && bookingConfig.nonce ? String(bookingConfig.nonce) : "";
    const accommodations = bookingConfig && Array.isArray(bookingConfig.accommodations) ? bookingConfig.accommodations : [];

    forms.forEach((form) => {
      const childrenToggle = form.querySelector("[data-booking-children-toggle]");
      const childrenCountWrap = form.querySelector("[data-booking-children-count]");
      const childrenCountSelect = childrenCountWrap ? childrenCountWrap.querySelector("select") : null;
      const guestsSelect = form.querySelector('select[name="guests"]');
      const accommodationSelect = form.querySelector("[data-booking-accommodation]");
      const accommodationIsSelect = accommodationSelect && typeof accommodationSelect.tagName === "string" && accommodationSelect.tagName.toLowerCase() === "select";
      const fullNameInput = form.querySelector('input[name="fullName"]');
      const phoneInput = form.querySelector('input[name="phone"]');
      const emailInput = form.querySelector('input[name="email"]');

      const phoneCountrySelect = form.querySelector("[data-booking-phone-country]");

      if (phoneInput) {
        const getCountryCode = () => {
          if (phoneCountrySelect && phoneCountrySelect.value) {
            return String(phoneCountrySelect.value).replace(/\D/g, "") || "374";
          }
          return "374";
        };
        const groupNational = (digits) => {
          if (!digits) return "";
          const out = [];
          for (let i = 0; i < digits.length; i += 3) {
            out.push(digits.slice(i, i + 3));
          }
          return out.join(" ");
        };
        const formatNational = (raw) => {
          let digits = String(raw || "").replace(/\D/g, "");
          if (!digits) return "";
          const cc = getCountryCode();
          if (digits.startsWith(cc) && digits.length > cc.length) {
            digits = digits.slice(cc.length);
          }
          digits = digits.slice(0, 12);
          return groupNational(digits);
        };
        const reformatInput = () => {
          const start = phoneInput.selectionStart;
          const before = phoneInput.value;
          const formatted = formatNational(before);
          if (formatted !== before) {
            phoneInput.value = formatted;
            const cursor = Math.min(formatted.length, (start || formatted.length) + (formatted.length - before.length));
            try { phoneInput.setSelectionRange(cursor, cursor); } catch (_) {}
          }
        };
        phoneInput.setAttribute("inputmode", "tel");
        phoneInput.setAttribute("maxlength", "16");
        if (phoneInput.value) {
          phoneInput.value = formatNational(phoneInput.value);
        }
        phoneInput.addEventListener("input", reformatInput);
        phoneInput.addEventListener("paste", () => setTimeout(reformatInput, 0));
        if (phoneCountrySelect) {
          phoneCountrySelect.addEventListener("change", () => {
            if (phoneInput.value) phoneInput.value = formatNational(phoneInput.value);
            phoneInput.focus();
          });
        }
      }
      const availabilityEl = form.querySelector("[data-booking-availability]");
      const startDateInput = form.querySelector('input[name="startDate"]');
      const endDateInput = form.querySelector('input[name="endDate"]');
      const rangeInput = form.querySelector("[data-booking-date-range]");
      const priceAmountEl = form.querySelector("[data-booking-price-amount]");
      const summaryRoots = Array.from(document.querySelectorAll("[data-booking-summary]"));
      const nightlyUsd = parseFloat(String(form.getAttribute("data-booking-nightly-usd") || "").trim());
      let latestAvailability = null;
      let blockedRanges = [];
      let rangePicker = null;

      if (accommodationIsSelect && accommodations.length) {
        const hasValues = Array.from(accommodationSelect.options).some((opt) => String(opt.value || "").trim() !== "");
        if (!hasValues) {
          accommodations.forEach((acc) => {
            const option = document.createElement("option");
            option.value = String(acc.id);
            option.textContent = String(acc.title || `#${acc.id}`);
            accommodationSelect.appendChild(option);
          });
        }
      }

      const pad2 = (n) => String(n).padStart(2, "0");
      const toDateStr = (d) => `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`;
      const shiftDate = (dateStr, days) => {
        const dt = new Date(`${dateStr}T12:00:00`);
        dt.setDate(dt.getDate() + days);
        return toDateStr(dt);
      };
      const rangesOverlap = (startA, endA, startB, endB) => startA <= endB && endA >= startB;

      const syncChildrenField = () => {
        const showChildren = childrenToggle && childrenToggle.value === "yes";
        if (childrenCountWrap) childrenCountWrap.classList.toggle("is-hidden", !showChildren);
        if (childrenCountSelect && !showChildren) childrenCountSelect.value = "1";
      };

      const updateBookingSummary = () => {
        if (!summaryRoots.length) return;
        const dict = STRINGS[getLang()] || STRINGS.en;

        const startDate = startDateInput ? String(startDateInput.value || "").trim() : "";
        const endDate = endDateInput ? String(endDateInput.value || "").trim() : "";
        const datesValue = startDate && endDate ? `${startDate} - ${endDate}` : "—";
        const guestsValue = guestsSelect ? String(guestsSelect.value || "2") : "2";
        const hasChildren = childrenToggle && String(childrenToggle.value || "no") === "yes";
        const childrenCount = childrenCountSelect ? String(childrenCountSelect.value || "1") : "1";
        const yesLabel = dict.booking_children_opt_yes || "Yes";
        const noLabel = dict.booking_children_opt_no || "No";
        const childrenValue = hasChildren ? `${yesLabel} (${childrenCount})` : noLabel;

        summaryRoots.forEach((root) => {
          const datesEl = root.querySelector("[data-booking-summary-dates]");
          const guestsEl = root.querySelector("[data-booking-summary-guests]");
          const childrenEl = root.querySelector("[data-booking-summary-children]");
          if (datesEl) datesEl.textContent = datesValue;
          if (guestsEl) guestsEl.textContent = guestsValue;
          if (childrenEl) childrenEl.textContent = childrenValue;
          root.hidden = false;
        });
      };

      const computeNightsAndTotal = () => {
        const s = startDateInput ? String(startDateInput.value || "").trim() : "";
        const e = endDateInput ? String(endDateInput.value || "").trim() : "";
        if (!s || !e || e < s || !Number.isFinite(nightlyUsd) || nightlyUsd <= 0) {
          return { nights: 0, total: 0 };
        }
        const start = new Date(`${s}T12:00:00`);
        const end = new Date(`${e}T12:00:00`);
        const nights = Math.max(1, Math.round((end.getTime() - start.getTime()) / 86400000) + 1);
        return { nights, total: nights * nightlyUsd };
      };

      const priceSummaryEl = form.querySelector(".booking-selector__price-summary");
      const updatePriceDisplay = () => {
        if (!priceAmountEl) return;
        const serverPrice = latestAvailability && latestAvailability.price ? latestAvailability.price : null;
        const hasAdminPrice = !!(serverPrice && serverPrice.has_admin_price);
        const total = hasAdminPrice && Number(serverPrice.total) > 0 ? Number(serverPrice.total) : 0;

        if (priceSummaryEl) {
          if (hasAdminPrice && total > 0) {
            priceSummaryEl.hidden = false;
            priceSummaryEl.classList.remove("is-empty");
          } else {
            priceSummaryEl.hidden = true;
            priceSummaryEl.classList.add("is-empty");
          }
        }

        if (hasAdminPrice && total > 0) {
          priceAmountEl.textContent = new Intl.NumberFormat("en-US", { maximumFractionDigits: 0 }).format(total);
        } else {
          priceAmountEl.textContent = "—";
        }
      };

      const setAvailabilityMessage = (message, state = "idle") => {
        if (!availabilityEl) return;
        const text = message ? String(message) : "";
        availabilityEl.textContent = text;
        availabilityEl.classList.remove("is-loading", "is-error", "is-success");
        if (state === "loading") availabilityEl.classList.add("is-loading");
        if (state === "error") availabilityEl.classList.add("is-error");
        if (state === "success") availabilityEl.classList.add("is-success");
        if (text.trim() === "") {
          availabilityEl.hidden = true;
          availabilityEl.classList.add("is-empty");
        } else {
          availabilityEl.hidden = false;
          availabilityEl.classList.remove("is-empty");
        }
      };
      if (availabilityEl && availabilityEl.textContent.trim() === "") {
        availabilityEl.hidden = true;
        availabilityEl.classList.add("is-empty");
      }

      const isBlockedRange = (startDate, endDate) => {
        if (!startDate || !endDate || !blockedRanges.length) return false;
        return blockedRanges.some((range) => rangesOverlap(startDate, endDate, String(range.start || ""), String(range.end || "")));
      };

      const refreshBlockedRanges = async () => {
        if (!ajaxUrl || !nonce || !accommodationSelect) {
          blockedRanges = [];
          if (rangePicker) rangePicker.set("disable", []);
          return;
        }

        const accommodationId = String(accommodationSelect.value || "").trim();
        if (!accommodationId) {
          blockedRanges = [];
          if (rangePicker) rangePicker.set("disable", []);
          return;
        }

        try {
          const body = new URLSearchParams();
          body.set("action", "dilijanvillas_get_blocked_ranges");
          body.set("nonce", nonce);
          body.set("accommodation_id", accommodationId);
          body.set("_", String(Date.now()));

          const response = await fetch(ajaxUrl, {
            method: "POST",
            headers: {
              "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
              "Cache-Control": "no-cache, no-store, max-age=0",
              "Pragma": "no-cache",
            },
            cache: "no-store",
            credentials: "same-origin",
            body: body.toString(),
          });
          const json = await response.json();
          const fetchedRanges = json && json.success && json.data && Array.isArray(json.data.ranges) ? json.data.ranges : [];
          blockedRanges = fetchedRanges
            .map((range) => ({
              start: String(range.start || "").trim(),
              end: String(range.end || "").trim(),
            }))
            .filter((range) => range.start && range.end && range.end >= range.start);
        } catch (err) {
          blockedRanges = [];
        }

        if (!rangePicker) return;
        const disabled = blockedRanges.map((range) => ({
          from: range.start,
          to: range.end,
        }));
        rangePicker.set("disable", disabled);

        if (rangePicker.days) {
          Array.from(rangePicker.days.children).forEach((dayElem) => {
            const dObj = dayElem.dateObj;
            if (!dObj) return;
            if (typeof markBlockedDay === "function") markBlockedDay(dayElem, dObj);
          });
        }

        const selectedStart = startDateInput ? String(startDateInput.value || "").trim() : "";
        const selectedEnd = endDateInput ? String(endDateInput.value || "").trim() : "";
        if (selectedStart && selectedEnd && isBlockedRange(selectedStart, selectedEnd)) {
          rangePicker.clear();
          if (startDateInput) startDateInput.value = "";
          if (endDateInput) endDateInput.value = "";
          latestAvailability = null;
          setAvailabilityMessage(((STRINGS[getLang()] || STRINGS.en).booking_msg_unavailable) || "Selected dates are not available.", "error");
          updateBookingSummary();
          updatePriceDisplay();
        }
      };

      const checkAvailability = async () => {
        if (!ajaxUrl || !nonce || !accommodationSelect || !startDateInput || !endDateInput) return null;

        const dictAvail = STRINGS[getLang()] || STRINGS.en;
        const accommodationId = String(accommodationSelect.value || "").trim();
        const startDate = String(startDateInput.value || "").trim();
        const endDate = String(endDateInput.value || "").trim();
        if (!accommodationId || !startDate || !endDate || endDate < startDate) {
          latestAvailability = null;
          updatePriceDisplay();
          setAvailabilityMessage("");
          return null;
        }
        if (isBlockedRange(startDate, endDate)) {
          latestAvailability = { available: false };
          setAvailabilityMessage(dictAvail.booking_msg_unavailable || "Selected dates are not available.", "error");
          updatePriceDisplay();
          return latestAvailability;
        }

        try {
          setAvailabilityMessage(dictAvail.booking_msg_checking || "Checking availability...", "loading");
          const body = new URLSearchParams();
          body.set("action", "dilijanvillas_check_booking");
          body.set("nonce", nonce);
          body.set("accommodation_id", accommodationId);
          body.set("start_date", startDate);
          body.set("end_date", endDate);
          body.set("guests", guestsSelect ? String(guestsSelect.value || "1") : "1");

          const response = await fetch(ajaxUrl, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
            body: body.toString(),
          });
          const json = await response.json();
          if (!json || !json.success) {
            latestAvailability = null;
            setAvailabilityMessage(dictAvail.booking_msg_check_error || "Could not check availability. Please try again.", "error");
            updatePriceDisplay();
            return null;
          }

          latestAvailability = json.data || null;
          if (latestAvailability && latestAvailability.available) {
            const hasAdminPrice = !!(latestAvailability.price && latestAvailability.price.has_admin_price);
            if (hasAdminPrice) {
              setAvailabilityMessage(dictAvail.booking_msg_available || "Available for selected dates.", "success");
            } else {
              setAvailabilityMessage(
                dictAvail.booking_msg_open_no_price
                  || "These dates are open, but no price is published yet. Please contact us for a quote.",
                "loading"
              );
            }
          } else {
            setAvailabilityMessage(dictAvail.booking_msg_unavailable || "Selected dates are not available.", "error");
          }
          updatePriceDisplay();
          return latestAvailability;
        } catch (err) {
          latestAvailability = null;
          setAvailabilityMessage(dictAvail.booking_msg_check_error || "Could not check availability. Please try again.", "error");
          updatePriceDisplay();
          return null;
        }
      };

      const onDatesChange = () => {
        checkAvailability();
        updateBookingSummary();
      };

      if (childrenToggle) {
        childrenToggle.addEventListener("change", () => {
          syncChildrenField();
          updateBookingSummary();
        });
        syncChildrenField();
      }

      if (childrenCountSelect) childrenCountSelect.addEventListener("change", updateBookingSummary);
      if (guestsSelect) guestsSelect.addEventListener("change", updateBookingSummary);
      if (accommodationSelect) accommodationSelect.addEventListener("change", updateBookingSummary);

      const formatDayYMD = (date) => {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, "0");
        const d = String(date.getDate()).padStart(2, "0");
        return `${y}-${m}-${d}`;
      };

      const isBlockedDay = (ymd) => {
        if (!ymd || !blockedRanges.length) return false;
        return blockedRanges.some((range) => {
          const start = String(range.start || "");
          const end = String(range.end || "");
          if (!start || !end) return false;
          return ymd >= start && ymd <= end;
        });
      };

      const markBlockedDay = (dayElem, dateObj) => {
        if (!dayElem || !dateObj) return;
        const ymd = formatDayYMD(dateObj);
        if (isBlockedDay(ymd)) {
          dayElem.classList.add("dv-day-blocked");
          dayElem.setAttribute("aria-label", `${dayElem.getAttribute("aria-label") || ymd} – blocked`);
        } else {
          dayElem.classList.remove("dv-day-blocked");
        }
      };

      if (rangeInput && startDateInput && endDateInput && typeof window.flatpickr === "function") {
        const dict = STRINGS[getLang()] || STRINGS.en;
        rangeInput.placeholder = dict.booking_label_stay_dates || "Stay period";
        rangePicker = window.flatpickr(rangeInput, {
          mode: "range",
          dateFormat: "Y-m-d",
          minDate: "today",
          disableMobile: true,
          clickOpens: true,
          defaultDate: (startDateInput.value && endDateInput.value) ? [startDateInput.value, endDateInput.value] : [],
          onDayCreate: (_dObj, _dStr, _fp, dayElem) => {
            markBlockedDay(dayElem, dayElem.dateObj);
          },
          onMonthChange: (_sel, _str, instance) => {
            if (!instance || !instance.days) return;
            Array.from(instance.days.children).forEach((dayElem) => markBlockedDay(dayElem, dayElem.dateObj));
          },
          onYearChange: (_sel, _str, instance) => {
            if (!instance || !instance.days) return;
            Array.from(instance.days.children).forEach((dayElem) => markBlockedDay(dayElem, dayElem.dateObj));
          },
          onChange: (selectedDates, dateStr, instance) => {
            if (selectedDates.length >= 1) {
              startDateInput.value = instance.formatDate(selectedDates[0], "Y-m-d");
            } else {
              startDateInput.value = "";
            }
            if (selectedDates.length >= 2) {
              endDateInput.value = instance.formatDate(selectedDates[1], "Y-m-d");
            } else {
              endDateInput.value = "";
            }
            onDatesChange();
          },
        });
      } else if (startDateInput && endDateInput) {
        startDateInput.addEventListener("change", onDatesChange);
        endDateInput.addEventListener("change", onDatesChange);
      }

      if (accommodationSelect) {
        accommodationSelect.addEventListener("change", async () => {
          await refreshBlockedRanges();
          checkAvailability();
        });
      }
      if (guestsSelect) guestsSelect.addEventListener("change", checkAvailability);

      refreshBlockedRanges().then(() => {
        onDatesChange();
      });
      updateBookingSummary();
      updatePriceDisplay();

      form.addEventListener("submit", (e) => {
        e.preventDefault();

        const dict = STRINGS[getLang()] || STRINGS.en;
        if (!accommodationSelect) {
          const data = new FormData(form);
          const lines = [dict.booking_msg_intro || "Hello, I want to submit a booking request."];
          const startDateWa = String(data.get("startDate") || "").trim();
          const endDateWa = String(data.get("endDate") || "").trim();
          if (startDateWa) lines.push(`- ${dict.booking_msg_checkin || "Check-in"}: ${startDateWa}`);
          if (endDateWa) lines.push(`- ${dict.booking_msg_checkout || "Check-out"}: ${endDateWa}`);
          const duration = data.get("duration");
          if (duration != null && String(duration).trim() !== "") {
            lines.push(`- ${dict.booking_msg_stay_length || "Stay length"}: ${String(duration)}`);
          }
          const guestsWa = String(data.get("guests") || "2");
          lines.push(`- ${dict.booking_msg_guests || "Guests"}: ${guestsWa}`);
          if (data.has("hasChildren")) {
            const hasChildren = String(data.get("hasChildren") || "no") === "yes";
            const childrenCount = hasChildren ? String(data.get("childrenCount") || "1") : "0";
            const yesLabel = dict.booking_children_opt_yes || "Yes";
            const noLabel = dict.booking_children_opt_no || "No";
            lines.push(`- ${dict.booking_msg_children || "Children"}: ${hasChildren ? `${yesLabel} (${childrenCount})` : noLabel}`);
          }
          const msg = encodeURIComponent(lines.join("\n"));
          window.open(`https://wa.me/37494605665?text=${msg}`, "_blank", "noopener,noreferrer");
          return;
        }

        const accommodationId = String(accommodationSelect.value || "").trim();
        const startDate = startDateInput ? String(startDateInput.value || "").trim() : "";
        const endDate = endDateInput ? String(endDateInput.value || "").trim() : "";
        if (!accommodationId) {
          alert(dict.booking_msg_accommodation || "Please choose accommodation type.");
          return;
        }
        if (!startDate || !endDate || endDate < startDate) {
          alert(dict.booking_error_dates || "Check-out must be on or after check-in.");
          return;
        }

        const adminPriceReady = !!(latestAvailability && latestAvailability.price && latestAvailability.price.has_admin_price);
        if (!adminPriceReady) {
          alert(
            dict.booking_msg_no_price
              || "These dates don't have a published price yet. Please contact us to confirm pricing before booking."
          );
          return;
        }

        const submitBooking = async () => {
          if (!ajaxUrl || !nonce) {
            alert("Booking endpoint is not configured.");
            return;
          }
          const body = new URLSearchParams();
          body.set("action", "dilijanvillas_create_booking");
          body.set("nonce", nonce);
          body.set("accommodation_id", accommodationId);
          body.set("start_date", startDate);
          body.set("end_date", endDate);
          body.set("guests", guestsSelect ? String(guestsSelect.value || "1") : "1");
          body.set("has_children", childrenToggle ? String(childrenToggle.value || "no") : "no");
          body.set("children_count", childrenCountSelect ? String(childrenCountSelect.value || "0") : "0");
          body.set("name", fullNameInput ? String(fullNameInput.value || "").trim() : "");
          let phoneValue = phoneInput ? String(phoneInput.value || "").trim() : "";
          if (phoneInput) {
            const ccValue = phoneCountrySelect ? String(phoneCountrySelect.value || "").replace(/\D/g, "") : "";
            const nationalDigits = phoneValue.replace(/\D/g, "");
            if (ccValue && nationalDigits) {
              phoneValue = "+" + ccValue + " " + phoneValue.trim();
            }
          }
          body.set("phone", phoneValue);
          body.set("email", emailInput ? String(emailInput.value || "").trim() : "");
          const hp = form.querySelector('input[name="website"]');
          body.set("website", hp ? String(hp.value || "") : "");
          // PayLink sends the guest back here after paying.
          body.set("return_url", window.location.href.split("#")[0]);

          const response = await fetch(ajaxUrl, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
            body: body.toString(),
          });
          const json = await response.json();
          if (!json || !json.success) {
            alert((json && json.data && json.data.message) ? json.data.message : "Booking failed. Please try again.");
            return;
          }

          updateBookingSummary();
          if (json.data && json.data.payment_url) {
            window.location.href = String(json.data.payment_url);
            return;
          }
          // No payment URL means PayLink refused the request — never claim success.
          console.error("PayLink did not return a payment URL.", (json.data && json.data.payment_error) || "");
          alert(dict.booking_msg_payment_failed || "We could not open the payment page. Your request was saved — please contact us to complete the payment.");
        };

        checkAvailability().then((result) => {
          if (result && result.available === false) {
            alert("Selected dates are not available.");
            return;
          }
          submitBooking();
        });
      });
    });
  }

  function initSiteAccessGate() {
    const gate = document.getElementById("site-access-gate");
    const cfg = typeof DV_SITE_ACCESS !== "undefined" ? DV_SITE_ACCESS : null;
    if (!gate || !cfg || !cfg.ajaxUrl) return;

    const form = gate.querySelector("[data-site-access-form]");
    const input = gate.querySelector("[data-site-access-input]");
    const errorEl = gate.querySelector("[data-site-access-error]");
    const submitBtn = gate.querySelector("[data-site-access-submit]");

    if (!form || !input) return;

    const storageKey = cfg.storageKey || "dv_site_access_ok";

    const saveBrowserAccess = (browserToken) => {
      if (!browserToken) return;
      try {
        localStorage.setItem(storageKey, String(browserToken));
      } catch (err) {
        /* localStorage may be blocked in private mode */
      }
    };

    const showError = (message) => {
      if (!errorEl) return;
      if (!message) {
        errorEl.hidden = true;
        errorEl.textContent = "";
        return;
      }
      errorEl.hidden = false;
      errorEl.textContent = message;
    };

    const dismissGate = () => {
      gate.hidden = true;
      document.body.classList.remove("site-access-locked");
      gate.setAttribute("aria-hidden", "true");
    };

    const tryRestoreFromBrowser = async () => {
      let stored = "";
      try {
        stored = localStorage.getItem(storageKey) || "";
      } catch (err) {
        return false;
      }

      if (!stored) return false;

      if (submitBtn) submitBtn.disabled = true;
      showError("");

      try {
        const body = new URLSearchParams();
        body.set("action", cfg.restoreAction || "dilijanvillas_site_access_restore");
        body.set("nonce", cfg.nonce || "");
        body.set("token", stored);

        const response = await fetch(cfg.ajaxUrl, {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
          credentials: "same-origin",
          body: body.toString(),
        });

        const json = await response.json();
        if (json && json.success) {
          window.location.reload();
          return true;
        }

        try {
          localStorage.removeItem(storageKey);
        } catch (err) {
          /* ignore */
        }
      } catch (err) {
        /* ignore — user can enter password manually */
      } finally {
        if (submitBtn) submitBtn.disabled = false;
      }

      return false;
    };

    tryRestoreFromBrowser().then((restored) => {
      if (restored) return;

      form.addEventListener("submit", async (event) => {
        event.preventDefault();
        showError("");

        const password = String(input.value || "");
        if (!password) {
          showError("Please enter the password.");
          return;
        }

        if (submitBtn) submitBtn.disabled = true;

        try {
          const body = new URLSearchParams();
          body.set("action", cfg.action || "dilijanvillas_site_access_unlock");
          body.set("nonce", cfg.nonce || "");
          body.set("password", password);

          const response = await fetch(cfg.ajaxUrl, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" },
            credentials: "same-origin",
            body: body.toString(),
          });

          const json = await response.json();
          if (json && json.success) {
            const token =
              json.data && json.data.browserToken ? String(json.data.browserToken) : "";
            saveBrowserAccess(token);
            dismissGate();
            window.location.reload();
            return;
          }

          const message =
            (json && json.data && json.data.message) ||
            "Incorrect password. Please try again.";
          showError(message);
          input.focus();
          input.select();
        } catch (err) {
          showError("Could not verify password. Please try again.");
        } finally {
          if (submitBtn) submitBtn.disabled = false;
        }
      });
    });
  }

  function initFloatingWhatsApp() {
    if (document.querySelector("[data-floating-whatsapp]")) return;

    const link = document.createElement("a");
    link.className = "floating-wa";
    link.href = "https://wa.me/37494605665";
    link.target = "_blank";
    link.rel = "noopener noreferrer";
    link.setAttribute("aria-label", "Chat on WhatsApp");
    link.setAttribute("data-floating-whatsapp", "");
    link.innerHTML =
      '<svg class="floating-wa__icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.52 3.48A11.86 11.86 0 0 0 12.06 0C5.5 0 .16 5.33.16 11.9c0 2.1.55 4.16 1.6 5.98L0 24l6.3-1.64a11.8 11.8 0 0 0 5.76 1.48h.01c6.56 0 11.9-5.34 11.9-11.9 0-3.18-1.24-6.17-3.45-8.46zM12.07 21.8h-.01a9.83 9.83 0 0 1-5.01-1.37l-.36-.22-3.74.97 1-3.65-.24-.38a9.84 9.84 0 0 1-1.5-5.25c0-5.44 4.42-9.86 9.86-9.86 2.63 0 5.1 1.02 6.96 2.9a9.78 9.78 0 0 1 2.88 6.95c0 5.44-4.43 9.87-9.84 9.87zm5.4-7.4c-.3-.15-1.78-.88-2.06-.98-.28-.1-.48-.15-.68.15-.2.3-.78.98-.95 1.18-.18.2-.35.23-.65.08-.3-.15-1.25-.46-2.38-1.47-.88-.78-1.47-1.75-1.65-2.05-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.53.15-.18.2-.3.3-.5.1-.2.05-.38-.03-.53-.08-.15-.68-1.64-.94-2.25-.25-.6-.5-.5-.68-.51h-.58c-.2 0-.53.08-.8.38-.28.3-1.05 1.03-1.05 2.5s1.08 2.9 1.23 3.1c.15.2 2.12 3.24 5.14 4.54.72.31 1.28.5 1.72.64.72.23 1.37.2 1.88.12.58-.09 1.78-.73 2.03-1.43.25-.7.25-1.3.18-1.43-.07-.13-.27-.2-.57-.35z"/></svg><span class="floating-wa__label">WhatsApp</span>';

    // Ensure correct initial position on refresh when page loads already scrolled.
    const topBtn = document.querySelector("[data-scroll-top]");
    const shouldRaise = topBtn ? !topBtn.hidden : window.scrollY > 300;
    link.classList.toggle("floating-wa--raised", shouldRaise);

    document.body.appendChild(link);
  }

  function boot() {
    resolveAssetUrls();
    initHeroBackdrop();
    initSectionParallaxBackgrounds();
    initLang();
    initHeaderScrollState();
    initNavDropdowns();
    initMobileNav();
    initScrollButtons();
    initReveal();
    initAboutSliders();
    initVideo();
    initVideoGallery();
    initEventsQuickVideos();
    initGalleryLightbox();
    initStayUnitDetails();
    initStaySliders();
    initContactPopup();
    initBookingPopup();
    initAmenitiesGroups();
    initBookingSelectors();
    initFloatingWhatsApp();
    initSiteAccessGate();
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", boot);
  } else {
    boot();
  }
})();

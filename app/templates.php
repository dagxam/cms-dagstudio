<?php
declare(strict_types=1);

/**
 * DAG STUDIO CMS: независимые шаблоны оформления.
 * Все настройки хранятся в существующей таблице settings; миграция не требуется.
 */
function template_catalog(): array
{
    return [
        'organization' => [
            'label'=>'Организация', 'caption'=>'Сообщества, фонды и учреждения',
            'description'=>'Открытая структура с миссией, направлениями деятельности и событиями.',
            'accent'=>'#a66a41', 'background'=>'#fcfaf7', 'ink'=>'#292620',
            'font'=>'manrope', 'hero'=>'split', 'cards'=>'soft', 'header'=>'classic',
            'radius'=>'16', 'width'=>'1240',
            'eyebrow'=>'Вместе мы можем больше',
            'title'=>'Создаём полезные перемены',
            'description_text'=>'Рассказываем о своей работе, проектах и людях, которые делают мир лучше.',
            'cta'=>'Наши проекты', 'secondary'=>'О нас',
            'features_title'=>'Наши направления', 'news_title'=>'События и новости',
            'contact_title'=>'Свяжитесь с нами',
            'sections'=>['features','page','news','service','contact'],
        ],
        'company' => [
            'label'=>'Компания', 'caption'=>'Бизнес и профессиональные услуги',
            'description'=>'Выразительный первый экран, услуги, преимущества и призыв к действию.',
            'accent'=>'#cc7850', 'background'=>'#11161c', 'ink'=>'#f4efe9',
            'font'=>'montserrat', 'hero'=>'split', 'cards'=>'outlined', 'header'=>'classic',
            'radius'=>'12', 'width'=>'1320',
            'eyebrow'=>'Решения для вашего бизнеса',
            'title'=>'Создаём идеи, которые работают',
            'description_text'=>'Надёжная команда, современные технологии и внимание к деталям каждого проекта.',
            'cta'=>'Наши услуги', 'secondary'=>'Связаться',
            'features_title'=>'Наши преимущества', 'news_title'=>'Новости компании',
            'contact_title'=>'Обсудим ваш проект',
            'sections'=>['features','service','page','news','contact'],
        ],
        'store' => [
            'label'=>'Магазин', 'caption'=>'Каталог товаров и витрина',
            'description'=>'Товарные карточки с ценами, заметные категории и сезонные предложения.',
            'accent'=>'#e18a4c', 'background'=>'#fbf9f6', 'ink'=>'#24201e',
            'font'=>'manrope', 'hero'=>'banner', 'cards'=>'elevated', 'header'=>'catalog',
            'radius'=>'18', 'width'=>'1380',
            'eyebrow'=>'Новые поступления и предложения',
            'title'=>'Вещи, которые хочется выбрать',
            'description_text'=>'Посмотрите наши товары, узнайте подробности и свяжитесь с нами для заказа.',
            'cta'=>'Смотреть товары', 'secondary'=>'О магазине',
            'features_title'=>'Почему выбирают нас', 'news_title'=>'Новости магазина',
            'contact_title'=>'Вопрос о товаре?',
            'sections'=>['product','features','news','page','contact'],
        ],
        'government' => [
            'label'=>'Администрация', 'caption'=>'Официальный сайт',
            'description'=>'Строгая структура: новости, документы, услуги и обращения граждан.',
            'accent'=>'#9e6747', 'background'=>'#f6f6f3', 'ink'=>'#183047',
            'font'=>'system', 'hero'=>'official', 'cards'=>'outlined', 'header'=>'official',
            'radius'=>'6', 'width'=>'1320',
            'eyebrow'=>'Официальный информационный портал',
            'title'=>'Открытость. Развитие. Ответственность.',
            'description_text'=>'Актуальные новости, официальная информация, документы и электронные обращения.',
            'cta'=>'Последние новости', 'secondary'=>'Обращения граждан',
            'features_title'=>'Полезная информация', 'news_title'=>'Новости администрации',
            'contact_title'=>'Обращение в администрацию',
            'sections'=>['news','features','service','page','contact'],
        ],
    ];
}

function template_sections(): array
{
    return [
        'features'=>'Преимущества / направления',
        'news'=>'Новости',
        'page'=>'Страницы и документы',
        'service'=>'Услуги',
        'product'=>'Товары',
        'contact'=>'Контакты и обращения',
    ];
}

function safe_template_url(string $url): bool
{
    if ($url === '') return true;
    if (str_starts_with($url, '//') || preg_match('/[\x00-\x20\x7f]/', $url)) return false;
    if ($url[0] === '/') return true;
    if ($url[0] === '#') return (bool)preg_match('/^#[A-Za-z0-9_-]+$/', $url);
    if (!preg_match('~^https://~i', $url)) return false;
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

function site_template(): string
{
    $selected = config_value('site_template', config_value('site_type', 'company'));
    return array_key_exists($selected, template_catalog()) ? $selected : 'company';
}

function template_design(?string $forTemplate = null): array
{
    $id = ($forTemplate !== null && array_key_exists($forTemplate, template_catalog())) ? $forTemplate : site_template();
    $preset = template_catalog()[$id];
    $maps = json_decode(config_value('template_design_by_type', '{}'), true);
    $stored = is_array($maps) && isset($maps[$id]) && is_array($maps[$id])
        ? $maps[$id]
        : ($id === site_template() ? json_decode(config_value('template_design', '{}'), true) : []);
    if (!is_array($stored)) $stored = [];
    $allowed = ['accent','background','ink','font','hero','cards','header','radius','width'];
    foreach ($allowed as $key) {
        if (isset($stored[$key]) && is_scalar($stored[$key])) $preset[$key] = (string)$stored[$key];
    }
    return $preset;
}

function template_content(?string $forTemplate = null): array
{
    $id = ($forTemplate !== null && array_key_exists($forTemplate, template_catalog())) ? $forTemplate : site_template();
    $preset = template_catalog()[$id];
    $defaults = [
        'eyebrow'=>$preset['eyebrow'], 'title'=>$preset['title'],
        'description'=>$preset['description_text'],
        'cta'=>$preset['cta'], 'cta_url'=>'#materials',
        'secondary'=>$preset['secondary'], 'secondary_url'=>'#contact',
        'features_title'=>$preset['features_title'], 'news_title'=>$preset['news_title'],
        'contact_title'=>$preset['contact_title'],
        'footer_text'=>'Создано с заботой о каждом посетителе.',
        'phone'=>'', 'address'=>'',
        'feature_1_title'=>'Надёжность', 'feature_1_text'=>'Мы выполняем свои обязательства.',
        'feature_2_title'=>'Качество', 'feature_2_text'=>'Внимание к каждой детали.',
        'feature_3_title'=>'Открытость', 'feature_3_text'=>'Всегда на связи с вами.',
        'logo_path'=>'', 'header_links'=>[],
    ];
    $maps = json_decode(config_value('template_content_by_type', '{}'), true);
    $data = is_array($maps) && isset($maps[$id]) && is_array($maps[$id])
        ? $maps[$id]
        : ($id === site_template() ? json_decode(config_value('template_content', '{}'), true) : []);
    if (!is_array($data)) $data = [];
    foreach ($defaults as $key => $value) {
        if ($key === 'header_links') {
            if (isset($data[$key]) && is_array($data[$key])) $defaults[$key] = array_slice($data[$key], 0, 4);
        } elseif (isset($data[$key]) && is_string($data[$key])) {
            $defaults[$key] = $data[$key];
        }
    }
    return $defaults;
}

function template_active_sections(?string $forTemplate = null): array
{
    $id = ($forTemplate !== null && array_key_exists($forTemplate, template_catalog())) ? $forTemplate : site_template();
    $original = template_catalog()[$id]['sections'];
    $maps = json_decode(config_value('template_sections_by_type', '{}'), true);
    $stored = is_array($maps) && isset($maps[$id]) && is_array($maps[$id])
        ? $maps[$id]
        : ($id === site_template() ? json_decode(config_value('template_sections', ''), true) : null);
    if (!is_array($stored)) return $original;
    $allowed = array_keys(template_sections());
    return array_values(array_unique(array_filter(
        $stored, static fn ($name): bool => is_string($name) && in_array($name, $allowed, true)
    )));
}

function template_font_stack(string $font): string
{
    return match ($font) {
        'montserrat'=>'"Montserrat", "Segoe UI", Arial, sans-serif',
        'manrope'=>'"Manrope", "Segoe UI", Arial, sans-serif',
        'georgia'=>'Georgia, "Times New Roman", serif',
        default=>'system-ui, -apple-system, "Segoe UI", Arial, sans-serif',
    };
}

function template_style(array $design, ?string $forTemplate = null): string
{
    // Значения валидируются перед сохранением и дополнительно проверяются при рендеринге.
    $id = ($forTemplate !== null && array_key_exists($forTemplate, template_catalog())) ? $forTemplate : site_template();
    $default = template_catalog()[$id];
    $colors = [];
    foreach (['accent','background','ink'] as $name) {
        $value = (string)$design[$name];
        $colors[$name] = preg_match('/^#[0-9a-fA-F]{6}$/D', $value) ? $value : $default[$name];
    }
    $radius = in_array((string)$design['radius'], ['0','6','12','16','18','24'], true) ? (int)$design['radius'] : 12;
    $width = in_array((string)$design['width'], ['1120','1240','1320','1380','1480'], true) ? (int)$design['width'] : 1320;
    return '--site-accent:' . $colors['accent'] .
        ';--site-bg:' . $colors['background'] .
        ';--site-ink:' . $colors['ink'] .
        ';--site-radius:' . $radius . 'px' .
        ';--site-width:' . $width . 'px' .
        ';--site-font:' . template_font_stack((string)$design['font']);
}

function template_section_links(string $type): string
{
    return match ($type) {
        'product'=>'/?kind=product',
        'news'=>'/?kind=news',
        'service'=>'/?kind=service',
        default=>'/?kind=page',
    };
}

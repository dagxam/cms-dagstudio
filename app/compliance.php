<?php
declare(strict_types=1);
/** Настройки доступности и информационной продукции РФ (без изменения схемы БД). */
function cms_accessibility(): array {
    $defaults=[
        'enabled'=>'1', 'font_scale'=>'150', 'contrast'=>'high',
        'line_spacing'=>'normal', 'show_images'=>'1', 'underlines'=>'1',
        'grayscale'=>'0', 'letter_spacing'=>'normal', 'motion'=>'1',
    ];
    $raw=json_decode(config_value('accessibility_options','{}'),true);
    if(!is_array($raw))return $defaults;
    foreach($defaults as $k=>$v) if(isset($raw[$k]) && is_string($raw[$k]))$defaults[$k]=$raw[$k];
    if(!in_array($defaults['font_scale'],['125','150','175','200'],true))$defaults['font_scale']='150';
    if(!in_array($defaults['contrast'],['high','blackwhite','yellowblack'],true))$defaults['contrast']='high';
    if(!in_array($defaults['line_spacing'],['normal','wide'],true))$defaults['line_spacing']='normal';
    if(!in_array($defaults['letter_spacing'],['normal','wide'],true))$defaults['letter_spacing']='normal';
    foreach(['enabled','show_images','underlines','grayscale','motion'] as $key)
        $defaults[$key]= $defaults[$key]==='1'?'1':'0';
    return $defaults;
}
function cms_age_rating(): string {
    $rating=config_value('site_age_rating','0+');
    return in_array($rating,['0+','6+','12+','16+','18+'],true)?$rating:'0+';
}
function cms_age_mark(): string {
    return '<span class="cms-age-mark" aria-label="Возрастная маркировка '.h(cms_age_rating()).'">'.h(cms_age_rating()).'</span>';
}
function cms_accessibility_control(): string {
    if(cms_accessibility()['enabled']!=='1' && site_template()!=='government')return '';
    return '<div class="cms-vision-control">'.
      '<button class="cms-vision-switch" type="button" data-accessibility-toggle aria-controls="cms-vision-toolbar" aria-expanded="false" aria-pressed="false" aria-label="Включить версию для слабовидящих">◉ <span>Для слабовидящих</span></button>'.
      '<div class="cms-vision-toolbar" id="cms-vision-toolbar" data-vision-toolbar hidden role="group" aria-label="Настройки версии для слабовидящих">'.
        '<label>Размер текста <select data-vision-option="scale" aria-label="Размер текста">'.
          '<option value="125">125%</option><option value="150">150%</option><option value="175">175%</option><option value="200">200%</option></select></label>'.
        '<label>Цветовая схема <select data-vision-option="contrast" aria-label="Цветовая схема">'.
          '<option value="high">Чёрный на белом</option><option value="blackwhite">Белый на чёрном</option><option value="yellowblack">Жёлтый на чёрном</option></select></label>'.
        '<label class="cms-vision-check"><input type="checkbox" data-vision-option="underlines"> Подчёркивать ссылки</label>'.
        '<label class="cms-vision-check"><input type="checkbox" data-vision-option="motion"> Убрать анимацию</label>'.
        '<button type="button" class="cms-vision-reset" data-vision-reset>Сбросить настройки</button>'.
      '</div></div>';
}
function cms_accessibility_attributes(): string {
    $settings=cms_accessibility();
    $attrs=[
        'data-vision-scale'=>$settings['font_scale'],
        'data-vision-contrast'=>$settings['contrast'],
        'data-vision-spacing'=>$settings['line_spacing'],
        'data-vision-letters'=>$settings['letter_spacing'],
        'data-vision-images'=>$settings['show_images'],
        'data-vision-underline'=>$settings['underlines'],
        'data-vision-grayscale'=>$settings['grayscale'],
        'data-vision-motion'=>$settings['motion'],
    ];
    $out='';
    foreach($attrs as $key=>$value)$out.=' '.$key.'="'.h($value).'"';
    return $out;
}
function cms_age_gate(): string {
    if(cms_age_rating()!=='18+')return '';
    return '<div class="cms-age-gate" data-age-gate role="dialog" aria-modal="true" aria-labelledby="cms-age-title" hidden>'.
        '<div class="cms-age-panel"><span class="cms-age-mark">18+</span>'.
        '<h2 id="cms-age-title">Материалы для совершеннолетних</h2>'.
        '<p>На сайте опубликованы материалы с маркировкой 18+. Подтвердите, что вам исполнилось 18 лет.</p>'.
        '<div><button type="button" class="button" data-age-confirm>Мне есть 18 лет</button>'.
        '<a class="button button-outline" href="https://www.gosuslugi.ru/">Покинуть сайт</a></div>'.
        '<small>Подтверждение возраста на этой странице не является проверкой личности.</small>'.
        '</div></div>';
}

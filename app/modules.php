<?php
declare(strict_types=1);

/**
 * Каталог встроенных модулей. Отключение скрывает вывод, но не удаляет материалы.
 * Привязки к расположению независимы для каждого из четырёх шаблонов.
 */
function cms_modules(): array {
    return [
        'page'=>['label'=>'Страницы и документы','description'=>'Публикации, страницы, нормативная информация','href'=>'/?kind=page'],
        'news'=>['label'=>'Новости','description'=>'Последние новости и события','href'=>'/?kind=news'],
        'service'=>['label'=>'Услуги','description'=>'Карточки услуг и направлений','href'=>'/?kind=service'],
        'product'=>['label'=>'Товары','description'=>'Каталог товаров с ценами','href'=>'/?kind=product'],
        'media'=>['label'=>'Медиатека','description'=>'Документы, фотографии и видео','href'=>'/media.php'],
        'features'=>['label'=>'Преимущества','description'=>'Три редактируемых блока о деятельности','href'=>'/?module=features'],
        'contact'=>['label'=>'Контакты и обращения','description'=>'Контактная информация и форма обращения','href'=>'/?module=contact'],
    ];
}

function cms_enabled_module_keys(): array {
    $legacy=json_decode(config_value('enabled_modules','["page","news","service","product"]'),true);
    if(!is_array($legacy))$legacy=['page','news','service','product'];
    $saved=json_decode(config_value('cms_modules_enabled',''),true);
    if(is_array($saved))return array_values(array_intersect(array_keys(cms_modules()),$saved));
    // Уже установленные сайты сохраняют прежние настройки материалов.
    return array_values(array_unique(array_merge(array_intersect(array_keys(cms_modules()),$legacy),['media','features','contact'])));
}
function cms_module_enabled(string $id): bool {
    return isset(cms_modules()[$id]) && in_array($id,cms_enabled_module_keys(),true);
}
function cms_module_areas(): array {
    return [
        'main'=>'Центральная часть',
        'left'=>'Левая колонка',
        'right'=>'Правая колонка',
        'nav'=>'Верхнее меню',
        'footer'=>'Подвал сайта',
        'hidden'=>'Не показывать в этом шаблоне',
    ];
}
function cms_module_defaults(string $template): array {
    $sections=template_active_sections($template);
    $result=[];
    $index=10;
    foreach(cms_modules() as $id=>$meta) {
        $position=array_search($id,$sections,true);
        $result[$id]=['area'=>$position===false?'hidden':'main','order'=>$position===false?($index+=10):(($position+1)*10)];
    }
    // Медиатека раньше имела собственную публичную ссылку, но не блок на главной.
    $result['media']=['area'=>'nav','order'=>50];
    return $result;
}
function cms_module_layout_configured(string $template): bool {
    $maps=json_decode(config_value('cms_module_layouts','{}'),true);
    return is_array($maps) && isset($maps[$template]) && is_array($maps[$template]);
}

function cms_module_layout(string $template): array {
    if(!array_key_exists($template,template_catalog()))$template=site_template();
    $default=cms_module_defaults($template);
    $maps=json_decode(config_value('cms_module_layouts','{}'),true);
    $stored=is_array($maps)?($maps[$template]??null):null;
    if(!is_array($stored))return $default;
    foreach($default as $id=>$settings) {
        if(!isset($stored[$id]) || !is_array($stored[$id]))continue;
        $slot=$stored[$id]['area']??null;
        $order=$stored[$id]['order']??null;
        if(is_string($slot) && isset(cms_module_areas()[$slot]))$default[$id]['area']=$slot;
        if((is_int($order) || (is_string($order) && ctype_digit($order))) && (int)$order>=1 && (int)$order<=99)
            $default[$id]['order']=(int)$order;
    }
    return $default;
}
function cms_module_ids(string $template,string $area): array {
    if(!isset(cms_module_areas()[$area]))return [];
    $positions=cms_module_layout($template);
    $ids=[];
    foreach($positions as $id=>$item)if($item['area']===$area && cms_module_enabled($id))$ids[]=$id;
    $order=array_flip(array_keys(cms_modules()));
    usort($ids,static function(string $a,string $b)use($positions,$order): int {
        return ($positions[$a]['order'] <=> $positions[$b]['order']) ?: ($order[$a]<=>$order[$b]);
    });
    return $ids;
}
function cms_module_href(string $id): string {
    return cms_modules()[$id]['href']??'/';
}
function cms_module_label(string $id): string {
    return cms_modules()[$id]['label']??$id;
}
function cms_module_compact(string $id, string $style='plain'): string {
    if(!cms_module_enabled($id))return '';
    $url=cms_module_href($id);
    $title=cms_module_label($id);
    return '<a class="cms-module-link cms-module-link-'.h($style).'" href="'.h($url).'">'
        .'<span>'.h($title).'</span><span aria-hidden="true">↗</span></a>';
}
/** Мини-виджет модуля в боковой колонке с настоящим содержимым. */
function cms_module_sidebar(string $id,string $template): void {
    if(!cms_module_enabled($id))return;
    echo '<section class="cms-module-widget cms-module-widget-'.h($id).'"><h2>'
        .'<a href="'.h(cms_module_href($id)).'">'.h(cms_module_label($id)).'</a></h2>';
    if(array_key_exists($id,kinds())) {
        $q=database()->prepare("SELECT title,slug FROM content WHERE kind=? AND status='published' ORDER BY created_at DESC LIMIT 3");
        $q->execute([$id]);$items=$q->fetchAll();
        foreach($items as $item)
            echo '<a class="cms-module-widget-entry" href="/?p='.rawurlencode($item['slug']).'">'.h($item['title']).'</a>';
        if(!$items)echo '<p>Публикаций пока нет.</p>';
    }elseif($id==='media') {
        foreach(array_slice(cms_media_list(true),0,3) as $item)
            echo '<a class="cms-module-widget-entry" href="/media.php?view='.(int)$item['id'].'">'.h($item['title']).'</a>';
    }elseif($id==='features') {
        $content=template_content($template);
        for($i=1;$i<=3;$i++)if(($content['feature_'.$i.'_title']??'')!=='')
            echo '<p>'.h($content['feature_'.$i.'_title']).'</p>';
    }elseif($id==='contact') {
        $email=config_value('contact_email');
        if($email!=='')echo '<p>'.h($email).'</p>';
        $details=template_content($template);
        if(($details['phone']??'')!=='')echo '<p>'.h($details['phone']).'</p>';
    }
    echo '<a class="cms-module-widget-more" href="'.h(cms_module_href($id)).'">Открыть раздел ↗</a></section>';
}

function cms_module_media_block(): void {
    if(!cms_module_enabled('media'))return;
    // Только опубликованные материалы, список ограничен числом карточек.
    $items=array_slice(cms_media_list(true),0,6);
    echo '<section class="cms-media-module" id="cms-media-section"><div class="section-heading">'
        .'<h2>Медиатека</h2><a href="/media.php">Все материалы ↗</a></div><div class="cms-media-module-grid">';
    foreach($items as $item) {
        $id=(int)$item['id'];
        echo '<a class="cms-media-module-card" href="/media.php?view='.$id.'">';
        if($item['category']==='photo' && $item['age_rating']!=='18+')
            echo '<img loading="lazy" src="/media.php?file='.$id.'" alt="'.h($item['alt_text']?:$item['title']).'">';
        else echo '<span class="cms-media-module-icon" aria-hidden="true">▤</span>';
        echo '<strong>'.h($item['title']).'</strong><small>'.h($item['age_rating']).'</small></a>';
    }
    if(!$items)echo '<p class="muted">Опубликованных материалов пока нет.</p>';
    echo '</div></section>';
}

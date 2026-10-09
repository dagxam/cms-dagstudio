<?php
declare(strict_types=1);
/** Индивидуальная компоновка конкретной страницы. */
function cms_page_options(int $contentId): array {
    $defaults=['before'=>'','after'=>'','left'=>[],'right'=>[]];
    if($contentId<1)return $defaults;
    $maps=json_decode(config_value('cms_page_options','{}'),true);
    $data=is_array($maps)?($maps[(string)$contentId]??null):null;
    if(!is_array($data))return $defaults;
    foreach(['before','after'] as $key) {
        $m=$data[$key]??'';
        if(is_string($m) && ($m==='' || isset(cms_modules()[$m])))$defaults[$key]=$m;
    }
    foreach(['left','right'] as $key){
        $list=$data[$key]??[];
        if(is_array($list))$defaults[$key]=array_values(array_intersect(array_keys(cms_modules()),array_filter($list,'is_string')));
    }
    return $defaults;
}
function cms_page_module(string $id,string $template): void {
    if($id==='' || !cms_module_enabled($id))return;
    if(in_array($id,['documents','photos','videos'],true)){cms_module_media_block($id);return;}
    if(in_array($id,['page','news','service','product'],true)) {
        echo '<section class="cms-inline-module"><div class="section-heading"><h2>'.h(cms_module_label($id)).'</h2><a href="'.h(cms_module_href($id)).'">Все материалы ↗</a></div>';
        $q=database()->prepare("SELECT title,slug,summary FROM content WHERE kind=? AND status='published' ORDER BY created_at DESC LIMIT 5");
        $q->execute([$id]);
        foreach($q->fetchAll() as $item) echo '<a class="cms-inline-module-entry" href="/?p='.rawurlencode($item['slug']).'">'.h($item['title']).'</a>';
        echo '</section>';
        return;
    }
    if($id==='features'){
        $c=template_content($template);
        echo '<section class="cms-inline-module"><h2>'.h($c['features_title']).'</h2>';
        for($i=1;$i<=3;$i++)echo '<p><strong>'.h($c['feature_'.$i.'_title']).'</strong> — '.h($c['feature_'.$i.'_text']).'</p>';
        echo '</section>';
    }elseif($id==='contact'){
        $c=template_content($template);
        echo '<section class="cms-inline-module"><h2>'.h($c['contact_title']).'</h2><p>'.h(config_value('contact_email')).'</p><a href="/?module=contact">Форма обращения ↗</a></section>';
    }
}
function cms_page_editor_widgets(int $id,string $template): string {
    $opts=cms_page_options($id);
    $out='<div class="cms-page-widget-controls"><h2>Модули на этой странице</h2><p class="muted">Выберите модуль над текстом или под ним, а также виджеты в боковых колонках. Можно изменить позднее.</p>';
    foreach(['before'=>'Перед текстом','after'=>'После текста'] as $key=>$label){
        $out.='<label>'.h($label).'<select name="page_'.$key.'"><option value="">Без модуля</option>';
        foreach(cms_modules() as $module=>$data)$out.='<option value="'.h($module).'"'.($opts[$key]===$module?' selected':'').'>'.h($data['label']).'</option>';
        $out.='</select></label>';
    }
    foreach(['left'=>'Слева','right'=>'Справа'] as $side=>$label){
        $out.='<fieldset><legend>Блоки '.h(mb_strtolower($label)).'</legend><div class="cms-page-widget-checks">';
        foreach(cms_modules() as $module=>$data){
            $out.='<label class="check"><input type="checkbox" name="page_'.$side.'[]" value="'.h($module).'"'
                .(in_array($module,$opts[$side],true)?' checked':'').'>'.h($data['label']).'</label>';
        }
        $out.='</div></fieldset>';
    }
    return $out.'</div>';
}

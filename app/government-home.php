<?php
declare(strict_types=1);

/** Компоновка главной администрации: модули + пользовательские блоки. */
function cms_government_home_looks():array {
    return [
        'official'=>'Официальный список',
        'cards'=>'Карточки',
        'accent'=>'Акцентная секция',
    ];
}
function cms_government_home_settings():array {
    $raw=json_decode(config_value('cms_government_home_styles','{}'),true);
    if(!is_array($raw))$raw=[];
    $result=[];
    foreach(cms_modules() as $id=>$meta){
        $item=$raw[$id]??[];
        if(!is_array($item))$item=[];
        $style=$item['style']??'official';
        if(!is_string($style)||!isset(cms_government_home_looks()[$style]))$style='official';
        $title=$item['title']??'';
        if(!is_string($title)||mb_strlen($title)>120||preg_match('/[\\x00-\\x1f\\x7f]/u',$title))$title='';
        $result[$id]=['style'=>$style,'title'=>trim($title)];
    }
    return $result;
}
function cms_government_home_available():array {
    $available=[];
    foreach(cms_home_blocks('government') as $block)
        if($block['enabled'])$available['b:'.$block['id']]=true;
    foreach(cms_module_ids('government','main') as $id)$available['m:'.$id]=true;
    return $available;
}
function cms_government_home_default_order():array {
    $result=[];
    foreach(cms_home_blocks('government') as $block)
        if($block['enabled']&&$block['placement']==='top')$result[]='b:'.$block['id'];
    foreach(cms_module_ids('government','main') as $id)$result[]='m:'.$id;
    foreach(cms_home_blocks('government') as $block)
        if($block['enabled']&&$block['placement']==='bottom')$result[]='b:'.$block['id'];
    return $result;
}
function cms_government_home_normalize_order(array $raw,array $allowed):array {
    if(count($raw)>100)throw new RuntimeException('Слишком много записей порядка главной.');
    $result=[];$seen=[];
    foreach($raw as $token){
        if(!is_string($token)||!isset($allowed[$token])||isset($seen[$token]))continue;
        $seen[$token]=true;$result[]=$token;
    }
    foreach(array_keys($allowed) as $token)if(!isset($seen[$token]))$result[]=$token;
    return $result;
}
function cms_government_home_order():array {
    $defaults=cms_government_home_default_order();
    $allowed=cms_government_home_available();
    $stored=json_decode(config_value('cms_government_home_order',''),true);
    if(!is_array($stored))return $defaults;
    return cms_government_home_normalize_order($stored,$allowed);
}
function cms_government_home_validate_layout(array $posted):array {
    if(count($posted)!==count(cms_modules())||array_diff(array_keys($posted),array_keys(cms_modules())))
        throw new RuntimeException('Получена неполная таблица модулей.');
    $result=[];
    foreach(cms_modules() as $id=>$meta){
        $item=$posted[$id]??null;
        if(!is_array($item))throw new RuntimeException('Отсутствуют параметры модуля '.$id);
        $area=$item['area']??null;$order=$item['order']??null;
        if(!is_string($area)||!isset(cms_module_areas()[$area])||
           !is_string($order)||!ctype_digit($order)||(int)$order<1||(int)$order>99)
            throw new RuntimeException('Неверная зона или порядок модуля '.$id);
        $result[$id]=['area'=>$area,'order'=>(int)$order];
    }
    return $result;
}
function cms_government_home_validate_styles(array $postedStyles,array $postedTitles):array {
    if(array_diff(array_keys($postedStyles),array_keys(cms_modules()))||
       array_diff(array_keys($postedTitles),array_keys(cms_modules())))
        throw new RuntimeException('Недопустимое поле оформления модуля.');
    $saved=[];
    foreach(cms_modules() as $id=>$meta){
        $style=$postedStyles[$id]??'official';
        $title=$postedTitles[$id]??'';
        if(!is_string($style)||!isset(cms_government_home_looks()[$style])||
           !is_string($title)||mb_strlen($title)>120||preg_match('/[\\x00-\\x1f\\x7f]/u',$title))
            throw new RuntimeException('Недопустимое оформление или заголовок модуля '.$id);
        $saved[$id]=['style'=>$style,'title'=>trim($title)];
    }
    return $saved;
}
function cms_government_home_resolve_title(string $id,string $fallback):string {
    return cms_government_home_settings()[$id]['title']?:$fallback;
}

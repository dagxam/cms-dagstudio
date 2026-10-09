<?php
declare(strict_types=1);
/** Главное меню каждого шаблона — независимое от меню других тем. */
function cms_menu_defaults(string $template): array {
    return [
        ['label'=>'Главная','url'=>'/'],
        ['label'=>'Новости','url'=>'/?kind=news'],
        ['label'=>'Страницы','url'=>'/?kind=page'],
        ['label'=>'Документы','url'=>'/media.php?type=document'],
        ['label'=>'Контакты','url'=>'/?module=contact'],
    ];
}
function cms_menu_configured(string $template): bool {
    $maps=json_decode(config_value('cms_menus_by_type','{}'),true);
    return is_array($maps) && isset($maps[$template]) && is_array($maps[$template]);
}
function cms_menu_for_template(string $template): array {
    if(!isset(template_catalog()[$template]))$template=site_template();
    $maps=json_decode(config_value('cms_menus_by_type','{}'),true);
    $data=is_array($maps)?($maps[$template]??null):null;
    if(!is_array($data))return cms_menu_defaults($template);
    $output=[];
    foreach(array_slice($data,0,20) as $item) {
        if(!is_array($item))continue;
        $label=trim((string)($item['label']??''));
        $url=trim((string)($item['url']??''));
        if($label==='' || mb_strlen($label)>80 || mb_strlen($url)>400 || !safe_template_url($url) || $url==='')continue;
        $output[]=['label'=>$label,'url'=>$url];
    }
    return $output;
}
function cms_menu_lines(string $template): string {
    return implode("\n",array_map(static fn(array $i):string=>$i['label'].' | '.$i['url'],cms_menu_for_template($template)));
}
function cms_menu_parse(string $input): array {
    if(strlen($input)>15000)throw new RuntimeException('Слишком длинное меню.');
    $result=[];
    foreach(preg_split('/\r\n|\r|\n/',$input) as $line) {
        $line=trim($line);
        if($line==='')continue;
        if(count($result)>=20)throw new RuntimeException('В меню максимум 20 пунктов.');
        $parts=explode('|',$line,2);
        if(count($parts)!==2)throw new RuntimeException('Формат строки: название | /адрес');
        $label=trim($parts[0]);$url=trim($parts[1]);
        if($label==='' || mb_strlen($label)>80 || $url==='' || mb_strlen($url)>400 || !safe_template_url($url))
            throw new RuntimeException('Некорректный пункт главного меню.');
        $result[]=['label'=>$label,'url'=>$url];
    }
    return $result;
}
function cms_menu_visible(string $template): array {
    $filtered=[];
    foreach(cms_menu_for_template($template) as $link){
        $url=$link['url'];
        if(preg_match('~^/\?kind=(page|news|service|product)(?:&|$)~',$url,$match) && !cms_module_enabled($match[1]))continue;
        if(preg_match('~^/media\.php\?type=(document|photo|video)~',$url,$match) && !cms_module_enabled(['document'=>'documents','photo'=>'photos','video'=>'videos'][$match[1]]))continue;
        $filtered[]=$link;
    }
    return $filtered;
}

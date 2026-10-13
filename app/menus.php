<?php
declare(strict_types=1);
/** Независимые меню всех тем; поддерживается один вложенный уровень. */
function cms_menu_defaults(string $template):array{
    return [
        ['label'=>'Главная','url'=>'/','children'=>[]],
        ['label'=>'Новости','url'=>'/?kind=news','children'=>[]],
        ['label'=>'Страницы','url'=>'/?kind=page','children'=>[]],
        ['label'=>'Документы','url'=>'/media.php?type=document','children'=>[]],
        ['label'=>'Контакты','url'=>'/?module=contact','children'=>[]]
    ];
}
function cms_menu_configured(string $template):bool{
    $maps=json_decode(config_value('cms_menus_by_type','{}'),true);
    return is_array($maps)&&isset($maps[$template])&&is_array($maps[$template]);
}
function cms_menu_clean_link(mixed $item):?array{
    if(!is_array($item)||!is_string($item['label']??null)||!is_string($item['url']??null))return null;
    $label=trim($item['label']);$url=trim($item['url']);
    if($label===''||mb_strlen($label)>80||$url===''||mb_strlen($url)>400||!safe_template_url($url))return null;
    return ['label'=>$label,'url'=>$url];
}
function cms_menu_for_template(string $template):array{
    if(!isset(template_catalog()[$template]))$template=site_template();
    $maps=json_decode(config_value('cms_menus_by_type','{}'),true);
    $data=is_array($maps)?($maps[$template]??null):null;
    if(!is_array($data))return cms_menu_defaults($template);
    $output=[];
    foreach(array_slice($data,0,20) as $item){
        $root=cms_menu_clean_link($item);
        if($root===null)continue;
        $root['children']=[];
        foreach(array_slice(is_array($item['children']??null)?$item['children']:[],0,10) as $child){
            $valid=cms_menu_clean_link($child);
            if($valid!==null)$root['children'][]=$valid;
        }
        $output[]=$root;
    }
    return $output;
}
function cms_menu_lines(string $template):string{
    $lines=[];
    foreach(cms_menu_for_template($template) as $link){
        $lines[]=$link['label'].' | '.$link['url'];
        foreach($link['children']??[] as $child)$lines[]='  - '.$child['label'].' | '.$child['url'];
    }
    return implode("\n",$lines);
}
function cms_menu_parse(string $input):array{
    if(strlen($input)>15000)throw new RuntimeException('Слишком длинное меню.');
    $result=[];$total=0;
    foreach(preg_split('/\r\n|\r|\n/',$input) as $line){
        if(trim($line)==='')continue;
        $isChild=(bool)preg_match('/^\s{2,4}-\s+/', $line);
        if(preg_match('/^\s+-/', $line)&&!$isChild)throw new RuntimeException('Подменю: используйте два пробела и дефис.');
        $value=$isChild?preg_replace('/^\s{2,4}-\s+/','',$line):trim($line);
        $parts=explode('|',$value,2);
        if(count($parts)!==2)throw new RuntimeException('Укажите «Название | /ссылка».');
        $item=cms_menu_clean_link(['label'=>$parts[0],'url'=>$parts[1]]);
        if($item===null)throw new RuntimeException('Некорректный пункт меню.');
        if(++$total>80)throw new RuntimeException('В меню максимум 80 ссылок.');
        if($isChild){
            if(!$result)throw new RuntimeException('Сначала создайте родительский пункт меню.');
            $last=count($result)-1;
            if(count($result[$last]['children'])>=10)throw new RuntimeException('Не больше 10 подпунктов.');
            $result[$last]['children'][]=$item;
        }else{
            if(count($result)>=20)throw new RuntimeException('Не больше 20 основных пунктов меню.');
            $result[]=$item+['children'=>[]];
        }
    }
    return $result;
}
function cms_menu_url_enabled(string $url):bool{
    if(preg_match('~^/\?kind=(page|news|service|product)(?:&|$)~',$url,$m)&&!cms_module_enabled($m[1]))return false;
    if(preg_match('~^/media\.php\?type=(document|photo|video)~',$url,$m)&&
        !cms_module_enabled(['document'=>'documents','photo'=>'photos','video'=>'videos'][$m[1]]))return false;
    return true;
}
function cms_menu_visible(string $template):array{
    $result=[];
    foreach(cms_menu_for_template($template) as $link){
        $children=array_values(array_filter($link['children']??[],static fn(array $item):bool=>cms_menu_url_enabled($item['url'])));
        if(!cms_menu_url_enabled($link['url']) && !$children)continue;
        if(!cms_menu_url_enabled($link['url']) && $children){
            // Родитель остаётся навигационной подписью, но не ссылкой на отключённый модуль.
            $link['url']='#submenu';
        }
        $link['children']=$children;
        $result[]=$link;
    }
    return $result;
}

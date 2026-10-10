<?php
declare(strict_types=1);
/** Независимые контакты и соцсети всех четырёх шаблонов. */
function cms_contact_templates(): array { return array_keys(template_catalog()); }
function cms_contacts(string $template): array {
    if(!in_array($template,cms_contact_templates(),true))$template=site_template();
    $content=template_content($template);
    $defaults=[
        'title'=>$content['contact_title'],
        'intro'=>'Свяжитесь с нами удобным способом или отправьте обращение.',
        'phones'=>($content['phone']??'')!==''?[(string)$content['phone']]:[],
        'emails'=>config_value('contact_email')!==''?[config_value('contact_email')]:[],
        'addresses'=>($content['address']??'')!==''?[(string)$content['address']]:[],
        'hours'=>'',
        'details'=>[],
    ];
    $maps=json_decode(config_value('cms_contacts_by_type','{}'),true);
    $stored=is_array($maps)?($maps[$template]??null):null;
    if(!is_array($stored))return $defaults;
    foreach(['title','intro','hours'] as $field)
        if(isset($stored[$field])&&is_string($stored[$field]))$defaults[$field]=$stored[$field];
    foreach(['phones','emails','addresses'] as $field) {
        if(isset($stored[$field])&&is_array($stored[$field]))
            $defaults[$field]=array_values(array_filter(array_slice($stored[$field],0,15),
                static fn($v):bool=>is_string($v)&&trim($v)!==''));
    }
    if(isset($stored['details'])&&is_array($stored['details'])) {
        $defaults['details']=[];
        foreach(array_slice($stored['details'],0,15) as $detail) {
            if(!is_array($detail))continue;
            $label=$detail['label']??'';$value=$detail['value']??'';
            if(is_string($label)&&is_string($value)&&$label!==''&&$value!=='')
                $defaults['details'][]=['label'=>$label,'value'=>$value];
        }
    }
    return $defaults;
}
function cms_contact_lines(array $values): string {
    return implode("\n",array_map('strval',$values));
}
function cms_contact_parse_lines(string $text,string $type): array {
    if(strlen($text)>5000)throw new RuntimeException('Слишком длинный список контактов.');
    $rows=[];
    foreach(preg_split('/\r\n|\n|\r/',$text) as $line){
        $line=trim($line);
        if($line==='')continue;
        if(count($rows)>=15||mb_strlen($line)>300)
            throw new RuntimeException('Максимум 15 строк, до 300 символов в каждой.');
        if($type==='emails'&&!filter_var($line,FILTER_VALIDATE_EMAIL))
            throw new RuntimeException('Проверьте адрес электронной почты: '.$line);
        if($type==='phones' && !preg_match('/^\+?[0-9()\- .]{5,80}$/D',$line))
            throw new RuntimeException('Телефоны должны содержать цифры, пробелы и допустимые знаки.');
        if(preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/',$line))
            throw new RuntimeException('Недопустимые символы в контактах.');
        $rows[]=$line;
    }
    return $rows;
}
function cms_contact_parse_details(string $text): array {
    if(strlen($text)>5000)throw new RuntimeException('Слишком длинный список реквизитов.');
    $items=[];
    foreach(preg_split('/\r\n|\n|\r/',$text) as $line) {
        $line=trim($line);
        if($line==='')continue;
        $split=explode('|',$line,2);
        if(count($split)!==2||count($items)>=15)throw new RuntimeException('Формат реквизитов: название | значение.');
        $label=trim($split[0]);$value=trim($split[1]);
        if($label===''||$value===''||mb_strlen($label)>90||mb_strlen($value)>300)
            throw new RuntimeException('Некорректный дополнительный контакт.');
        $items[]=['label'=>$label,'value'=>$value];
    }
    return $items;
}
function cms_contact_details_lines(array $items): string {
    return implode("\n",array_map(static fn($x):string=>($x['label']??'').' | '.($x['value']??''),$items));
}
function cms_social_catalog(): array {
    return [
        'vk'=>['name'=>'ВКонтакте','host'=>['vk.com','www.vk.com','m.vk.com'],'abbr'=>'VK','icon'=>'fa-brands fa-vk'],
        'telegram'=>['name'=>'Telegram','host'=>['t.me','telegram.me'],'abbr'=>'TG','icon'=>'fa-brands fa-telegram'],
        'max'=>['name'=>'MAX','host'=>['max.ru','www.max.ru'],'abbr'=>'М','icon'=>'fa-solid fa-comment-dots'],
        'rutube'=>['name'=>'Rutube','host'=>['rutube.ru','www.rutube.ru'],'abbr'=>'R','icon'=>'fa-solid fa-circle-play'],
        'ok'=>['name'=>'Одноклассники','host'=>['ok.ru','www.ok.ru'],'abbr'=>'OK','icon'=>'fa-brands fa-odnoklassniki'],
        'dzen'=>['name'=>'Дзен','host'=>['dzen.ru','www.dzen.ru'],'abbr'=>'Д','icon'=>'fa-solid fa-rss'],
        'youtube'=>['name'=>'YouTube','host'=>['youtube.com','www.youtube.com','youtu.be'],'abbr'=>'YT','icon'=>'fa-brands fa-youtube'],
        'whatsapp'=>['name'=>'WhatsApp','host'=>['wa.me','api.whatsapp.com'],'abbr'=>'WA','icon'=>'fa-brands fa-whatsapp'],
        'instagram'=>['name'=>'Instagram','host'=>['instagram.com','www.instagram.com'],'abbr'=>'IG','icon'=>'fa-brands fa-instagram'],
    ];
}
function cms_social_valid_url(string $platform,string $url): bool {
    $catalog=cms_social_catalog();
    if(!isset($catalog[$platform])||strlen($url)>500)return false;
    $url=trim($url);
    if($url==='')return true;
    $parsed=parse_url($url);
    if(!is_array($parsed)||($parsed['scheme']??'')!=='https'||
       !in_array(strtolower((string)($parsed['host']??'')),$catalog[$platform]['host'],true)||
       isset($parsed['user'])||isset($parsed['pass'])||isset($parsed['port']))return false;
    if(preg_match('/[\x00-\x20\x7f]/',$url))return false;
    return true;
}
function cms_socials(string $template): array {
    if(!in_array($template,cms_contact_templates(),true))$template=site_template();
    $maps=json_decode(config_value('cms_socials_by_type','{}'),true);
    $stored=is_array($maps)?($maps[$template]??null):null;
    $result=['enabled'=>true,'location'=>'both','links'=>[]];
    if(!is_array($stored))return $result;
    $result['enabled']=($stored['enabled']??true)===true;
    $result['location']=in_array($stored['location']??'both',['footer','contact','both'],true)?$stored['location']:'both';
    foreach(cms_social_catalog() as $id=>$meta){
        $url=$stored['links'][$id]??'';
        if(is_string($url)&&$url!==''&&cms_social_valid_url($id,$url))$result['links'][$id]=$url;
    }
    return $result;
}
function cms_render_social_links(string $template,string $location='footer'): string {
    $cfg=cms_socials($template);
    if(!$cfg['enabled']||!$cfg['links']||($cfg['location']!=='both'&&$cfg['location']!==$location))
        return '';
    $html='<nav class="cms-social-links cms-social-links-'.h($location).'" aria-label="Мы в социальных сетях">';
    foreach($cfg['links'] as $id=>$url) {
        $meta=cms_social_catalog()[$id];
        $html.='<a href="'.h($url).'" target="_blank" rel="noopener noreferrer" aria-label="'.h($meta['name']).' (открывается в новой вкладке)"><span class="cms-social-symbol" aria-hidden="true"><i class="'.h($meta['icon']).'"></i></span><span>'.h($meta['name']).'</span></a>';
    }
    return $html.'</nav>';
}
function cms_render_contact_details(string $template): string {
    $c=cms_contacts($template);
    $html='<div class="cms-contact-details">';
    $list=[
        'phones'=>['heading'=>'Телефоны','type'=>'phone'],
        'emails'=>['heading'=>'Электронная почта','type'=>'email'],
        'addresses'=>['heading'=>'Адреса','type'=>'address']
    ];
    foreach($list as $key=>$config) {
        if(!$c[$key])continue;
        $html.='<div class="cms-contact-details-group"><h3>'.h($config['heading']).'</h3>';
        foreach($c[$key] as $value) {
            $link='';
            if($config['type']==='email'&&filter_var($value,FILTER_VALIDATE_EMAIL))
                $link='mailto:'.$value;
            if($config['type']==='phone'){
                $number=preg_replace('/[^0-9+]/','',$value);
                if(strlen($number)>=5)$link='tel:'.$number;
            }
            $html.='<p>'.($link!==''?'<a href="'.h($link).'">'.h($value).'</a>':h($value)).'</p>';
        }
        $html.='</div>';
    }
    if($c['hours']!=='')$html.='<div class="cms-contact-details-group"><h3>Режим работы</h3><p>'.nl2br(h($c['hours'])).'</p></div>';
    foreach($c['details'] as $detail)$html.='<div class="cms-contact-details-group"><h3>'.h($detail['label']).'</h3><p>'.nl2br(h($detail['value'])).'</p></div>';
    return $html.'</div>'.cms_render_social_links($template,'contact');
}

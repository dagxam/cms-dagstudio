<?php
declare(strict_types=1);

/** Настройки официального сайта без отдельной миграции MySQL. */
function government_defaults(): array
{
    return [
        'name'=>'Администрация',
        'name_detail'=>'Официальный сайт муниципального образования',
        'location'=>'Адрес администрации укажите в настройках',
        'working_hours'=>'Пн–Пт, 09:00–18:00',
        'office_phone'=>'',
        'top_note'=>'Официальный информационный портал',
        'banner_title'=>'Наш район — наша история',
        'banner_text'=>'Новости, информация и полезные сервисы для жителей',
        'search_placeholder'=>'Поиск по сайту...',
        'search_button'=>'Найти',
        'quick_title'=>'Интернет-приёмная',
        'quick_url'=>'#contact',
        'left_title'=>'Разделы сайта',
        'center_title'=>'Главная',
        'center_intro'=>'Добро пожаловать на официальный сайт. Здесь публикуются новости, документы, сведения о работе администрации и информация для жителей.',
        'leader_title'=>'Глава администрации',
        'leader_name'=>'',
        'leader_description'=>'',
        'leader_image'=>'',
        'schedule_title'=>'График приёма',
        'schedule_text'=>'Расписание личного приёма граждан можно уточнить по телефону администрации.',
        'announcements_title'=>'Анонсы и события',
        'announcements_text'=>'Актуальные объявления и сообщения публикуются в разделе новостей.',
        'links_title'=>'Полезные ссылки',
        'footer_note'=>'Официальный информационный сайт.',
        'left_width'=>'240',
        'right_width'=>'240',
        'layout'=>'both',
        'show_topbar'=>'1',
        'show_banner'=>'1',
        'show_search'=>'1',
        'show_leader'=>'1',
        'show_schedule'=>'1',
        'show_announcements'=>'1',
        'show_links'=>'1',
        'show_date'=>'1',
        'show_accessibility'=>'1',
        'left_menu'=>[
            ['label'=>'Поселение','url'=>'/?kind=page'],
            ['label'=>'Администрация','url'=>'/?p=administratsiya'],
            ['label'=>'Документы','url'=>'/?kind=page'],
            ['label'=>'Муниципальные услуги','url'=>'/?kind=service'],
            ['label'=>'Новости','url'=>'/?kind=news'],
            ['label'=>'Обращения граждан','url'=>'#contact'],
        ],
        'right_links'=>[
            ['label'=>'Контакты администрации','url'=>'#contact'],
            ['label'=>'Документы','url'=>'/?kind=page'],
            ['label'=>'Новости','url'=>'/?kind=news'],
        ],
        'quick_links'=>[
            ['label'=>'Официальные документы','url'=>'/?kind=page'],
            ['label'=>'Муниципальные услуги','url'=>'/?kind=service'],
        ],
    ];
}

function government_link_list(array $links, int $max = 24): array
{
    $result=[];
    foreach (array_slice($links,0,$max) as $item) {
        if (!is_array($item)) continue;
        $label=$item['label']??'';
        $url=$item['url']??'';
        if (!is_string($label)||!is_string($url)) continue;
        $label=trim($label);$url=trim($url);
        if ($label==='' || mb_strlen($label)>85 || mb_strlen($url)>300 ||
            !safe_template_url($url) || $url==='') continue;
        $result[]=['label'=>$label,'url'=>$url];
    }
    return $result;
}

/** Текстовый редактор: каждая строка «Название | /ссылка». */
function government_parse_link_text(string $text, int $max=24): array
{
    if (strlen($text)>12000) throw new RuntimeException('Слишком длинный список ссылок.');
    $result=[];
    foreach (preg_split('/\r\n|\r|\n/', $text) as $line) {
        $line=trim($line);
        if ($line==='')continue;
        if (count($result)>=$max)throw new RuntimeException('Слишком много ссылок в меню (максимум '.$max.').');
        $parts=explode('|',$line,2);
        if (count($parts)!==2) throw new RuntimeException('Укажите ссылки в формате «Название | /адрес».');
        $label=trim($parts[0]);$url=trim($parts[1]);
        if ($label==='' || mb_strlen($label)>85 || $url==='' ||
            mb_strlen($url)>300 || !safe_template_url($url)) {
            throw new RuntimeException('Неверная ссылка или название: '.$label);
        }
        $result[]=['label'=>$label,'url'=>$url];
    }
    return $result;
}

function government_links_as_text(array $links): string
{
    $lines=[];
    foreach ($links as $link) {
        if (isset($link['label'],$link['url']) && is_string($link['label']) && is_string($link['url']))
            $lines[]=$link['label'].' | '.$link['url'];
    }
    return implode("\n",$lines);
}

function government_layout(): array
{
    $defaults=government_defaults();
    $stored=json_decode(config_value('government_layout','{}'),true);
    if (!is_array($stored))return $defaults;
    foreach ($defaults as $key=>$value) {
        if (is_string($value) && isset($stored[$key]) && is_string($stored[$key])) {
            $defaults[$key]=mb_substr($stored[$key],0,5000);
        } elseif (is_array($value) && isset($stored[$key]) && is_array($stored[$key])) {
            $defaults[$key]=government_link_list($stored[$key],$key==='left_menu'?24:12);
        }
    }
    if (!in_array($defaults['layout'],['both','swap','left','right','none'],true))$defaults['layout']='both';
    foreach (['left_width','right_width'] as $key) {
        if (!in_array($defaults[$key],['200','220','240','260','280','300'],true))$defaults[$key]='240';
    }
    if (!preg_match('~^/assets/uploads/leader-[a-f0-9]{32}\\.(?:png|jpg|webp)$~D',$defaults['leader_image'])) {
        $defaults['leader_image']='';
    }
    return $defaults;
}

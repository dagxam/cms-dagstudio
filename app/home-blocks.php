<?php
declare(strict_types=1);
/** Конструктор блоков главной страницы для каждого шаблона. */
function cms_home_types():array {
 return ['text'=>'Текстовый блок','cards'=>'Карточки со ссылками','stats'=>'Показатели','banner'=>'Акцентный баннер'];
}
function cms_home_places():array {
 return ['top'=>'Перед основными разделами','bottom'=>'После основных разделов'];
}
function cms_home_validate_field(mixed $value,int $limit):string {
 if(!is_string($value)||mb_strlen($value)>$limit||preg_match('/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]/',$value))
   throw new RuntimeException('Недопустимый текст блока главной.');
 return trim($value);
}
function cms_home_normalize(array $raw,?array $old=null):array {
 $type=$raw['type']??null;$place=$raw['placement']??null;
 if(!is_string($type)||!isset(cms_home_types()[$type])||!is_string($place)||!isset(cms_home_places()[$place]))
   throw new RuntimeException('Укажите тип и место блока.');
 $title=cms_home_validate_field($raw['title']??null,140);
 $kicker=cms_home_validate_field($raw['kicker']??'',80);
 $text=cms_home_validate_field($raw['text']??'',1000);
 $button=cms_home_validate_field($raw['button']??'',70);
 $url=cms_home_validate_field($raw['url']??'',300);
 if($title===''||($button==='')!==($url==='')||!safe_template_url($url))
   throw new RuntimeException('Проверьте заголовок, кнопку и адрес блока.');
 $items=[];
 if(in_array($type,['cards','stats'],true)){
   $rawItems=$raw['items']??[];
   if(!is_array($rawItems)||count($rawItems)>3)throw new RuntimeException('Не больше 3 карточек.');
   for($i=0;$i<3;$i++){
     $item=$rawItems[$i]??[];
     if(!is_array($item))throw new RuntimeException('Ошибка данных карточки.');
     $name=cms_home_validate_field($item['title']??'',100);
     $desc=cms_home_validate_field($item['text']??'',300);
     $href=cms_home_validate_field($item['url']??'',300);
     if(!safe_template_url($href)||($type==='stats'&&$href!==''))throw new RuntimeException('Некорректная ссылка.');
     if($name===''){if($desc!==''||$href!=='')throw new RuntimeException('Укажите имя карточки.');continue;}
     $items[]=['title'=>$name,'text'=>$desc,'url'=>$href];
   }
   if(!$items)throw new RuntimeException('Добавьте хотя бы одну карточку.');
 }
 return ['id'=>$old['id']??bin2hex(random_bytes(8)),'type'=>$type,'placement'=>$place,
         'enabled'=>($raw['enabled']??'1')==='1','title'=>$title,'kicker'=>$kicker,
         'text'=>$text,'button'=>$button,'url'=>$url,'items'=>$items];
}
function cms_home_blocks(string $template):array {
 if(!isset(template_catalog()[$template]))return [];
 $maps=json_decode(config_value('cms_home_blocks_by_type','{}'),true);
 $blocks=is_array($maps)?($maps[$template]??[]):[];
 if(!is_array($blocks))return [];
 $list=[];
 foreach(array_slice($blocks,0,16) as $block) {
   if(!is_array($block)||!preg_match('/^[a-f0-9]{16}$/D',(string)($block['id']??'')))continue;
   try{$clean=cms_home_normalize([...$block,'enabled'=>!empty($block['enabled'])?'1':'0'],$block);}
   catch(RuntimeException $e){continue;}
   if(!in_array($clean['id'],array_column($list,'id'),true))$list[]=$clean;
 }
 return $list;
}
function cms_home_save(string $template,array $blocks):void {
 if(!isset(template_catalog()[$template])||count($blocks)>16)throw new RuntimeException('Максимум 16 блоков на шаблон.');
 $maps=json_decode(config_value('cms_home_blocks_by_type','{}'),true);
 if(!is_array($maps))$maps=[];
 $maps[$template]=array_values($blocks);
 $json=json_encode($maps,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
 if(strlen($json)>60000)throw new RuntimeException('Слишком много текста в блоках.');
 database()->prepare('INSERT INTO settings(name,value) VALUES(?,?) ON DUPLICATE KEY UPDATE value=VALUES(value)')
  ->execute(['cms_home_blocks_by_type',$json]);
}
function cms_home_index(array $blocks,string $id):int {
 foreach($blocks as $i=>$block)if($block['id']===$id)return $i;
 throw new RuntimeException('Блок не найден.');
}

/** Рендер главных блоков использует цвета и геометрию активной темы. */
function cms_home_render(string $template,string $placement,?string $onlyId=null):void {
 if(!isset(cms_home_places()[$placement]))return;
 foreach(cms_home_blocks($template) as $block){
   if(!$block['enabled']||($onlyId!==null ? $block['id']!==$onlyId : $block['placement']!==$placement))continue;
   $type=$block['type'];
   echo '<section class="cms-custom-home cms-custom-home-'.h($type).'" aria-labelledby="home-'.h($block['id']).'">';
   echo '<div class="cms-home-copy">';
   if($block['kicker']!=='')echo '<span class="cms-home-eyebrow">'.h($block['kicker']).'</span>';
   echo '<h2 id="home-'.h($block['id']).'">'.h($block['title']).'</h2>';
   if($block['text']!=='')echo '<p>'.nl2br(h($block['text'])).'</p>';
   if($block['button']!=='')echo '<a class="cms-home-cta" href="'.h($block['url']).'">'.h($block['button']).' <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>';
   echo '</div>';
   if(in_array($type,['cards','stats'],true)){
     echo '<div class="cms-home-cards">';
     foreach($block['items'] as $item){
       $href=$type==='cards'?$item['url']:'';
       echo $href!==''?'<a class="cms-home-card" href="'.h($href).'">':'<div class="cms-home-card">';
       if($type==='cards')echo '<i class="fa-solid fa-'.($template==='government'?'landmark':'layer-group').'" aria-hidden="true"></i>';
       echo '<strong>'.h($item['title']).'</strong>';
       if($item['text']!=='')echo '<span>'.h($item['text']).'</span>';
       if($href!=='')echo '<i class="fa-solid fa-arrow-up-right-from-square cms-home-card-arrow" aria-hidden="true"></i>';
       echo $href!==''?'</a>':'</div>';
     }
     echo '</div>';
   }
   echo '</section>';
 }
}

<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||getenv('DAG_CMS_TEST_MODE')!=='1')exit(1);
$config=[
 'site_type'=>'government','site_template'=>'government',
 'enabled_modules'=>'["page","news","service","product"]',
 'cms_module_layouts'=>'{}','cms_government_home_order'=>'',
 'cms_government_home_styles'=>'{}','cms_home_blocks_by_type'=>'{}'
];
function config_value(string $key,string $default=''):string{global $config;return $config[$key]??$default;}
function h(?string $s):string{return htmlspecialchars($s??'',ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function assert_home(bool $okay,string $label):void{
 if(!$okay){fwrite(STDERR,"[FAIL] $label\n");exit(1);}
 echo "[OK] $label\n";
}
function rejects_home(callable $work,string $label):void{
 try{$work();}catch(RuntimeException $e){assert_home(true,$label);return;}
 assert_home(false,$label);
}
require dirname(__DIR__).'/app/templates.php';
require dirname(__DIR__).'/app/modules.php';
require dirname(__DIR__).'/app/home-blocks.php';
require dirname(__DIR__).'/app/government-home.php';
assert_home(count(cms_modules())===9,'Поддержка всех 9 встроенных модулей');
assert_home(cms_government_home_order()===array_map(static fn($id)=>'m:'.$id,cms_module_ids('government','main')),
 'Настройки старых главных страниц не нарушены');
$block=cms_home_normalize(['type'=>'cards','placement'=>'bottom','title'=>'Полезные телефоны','text'=>'Тест',
 'enabled'=>'1','items'=>[['title'=>'112','text'=>'Единая служба','url'=>'']]]);
$config['cms_home_blocks_by_type']=json_encode(['government'=>[$block]],JSON_UNESCAPED_UNICODE);
$original=cms_government_home_order();
assert_home(end($original)==='b:'.$block['id'],'Старые собственные блоки сохраняют положение после модулей');
$items=['m:news','b:'.$block['id'],'m:page'];
$config['cms_government_home_order']=json_encode($items);
$result=cms_government_home_order();
assert_home($result[0]==='m:news'&&$result[1]==='b:'.$block['id'],
 'Собственный блок можно поставить между встроенными модулями');
assert_home(count(array_unique($result))===count($result),'Повторяющиеся секции не дублируются');
assert_home(!in_array('m:fake',cms_government_home_normalize_order(['m:fake'],cms_government_home_available()),true),
 'Неизвестные модули игнорируются');
$layout=cms_module_layout('government');
$source=[];
foreach($layout as $id=>$item)$source[$id]=['area'=>$item['area'],'order'=>(string)$item['order']];
$source['documents']['area']='main';
$source['news']['area']='right';
$parsed=cms_government_home_validate_layout($source);
assert_home($parsed['documents']['area']==='main'&&$parsed['news']['area']==='right',
 'Документы можно вывести в центр, а новости перенести вправо');
rejects_home(fn()=>cms_government_home_validate_layout(['news'=>['area'=>'main','order'=>'1']]),
 'Неполная форма модулей отклоняется');
$wrong=$source;$wrong['news']['area']='script';
rejects_home(fn()=>cms_government_home_validate_layout($wrong),'Недопустимая зона отклоняется');
$wrong=$source;$wrong['news']['order']='999';
rejects_home(fn()=>cms_government_home_validate_layout($wrong),'Недопустимый порядок отклоняется');
$styles=['news'=>'cards','documents'=>'accent'];
$titles=['news'=>'Новости муниципального округа','documents'=>'Официальные документы'];
$validated=cms_government_home_validate_styles($styles,$titles);
assert_home($validated['news']['style']==='cards'&&$validated['news']['title']===$titles['news'],
 'Стиль и название секции редактируются');
rejects_home(fn()=>cms_government_home_validate_styles(['news'=>'javascript'],[]),
 'Недопустимый стиль запрещён');
rejects_home(fn()=>cms_government_home_validate_styles([],['news'=>str_repeat('x',121)]),
 'Заголовок слишком большой отклоняется');
$config['cms_government_home_styles']=json_encode($validated,JSON_UNESCAPED_UNICODE);
assert_home(cms_government_home_resolve_title('news','Новости')===$titles['news'],
 'Индивидуальный заголовок выводится на главной');
$config['cms_module_layouts']=json_encode(['government'=>[
 'documents'=>['area'=>'main','order'=>5],
 'news'=>['area'=>'right','order'=>10]
]],JSON_UNESCAPED_UNICODE);
assert_home(in_array('documents',cms_module_ids('government','main'),true) &&
 !in_array('news',cms_module_ids('government','main'),true),
 'Расположение в главной согласуется с общим редактором модулей');
$config['cms_government_home_order']=json_encode(['m:news','m:documents','b:'.$block['id']]);
$reordered=cms_government_home_order();
assert_home(!in_array('m:news',$reordered,true)&&in_array('m:documents',$reordered,true),
 'Перенос модуля вправо убирает центральную карточку');
ob_start();cms_home_render('government','top',$block['id']);$html=ob_get_clean();
assert_home(str_contains($html,'Полезные телефоны'),'Собственный блок выводится по ID независимо от старой зоны top/bottom');
assert_home(!in_array('m:news',cms_government_home_order(),true),'Скрытый в центре модуль не остаётся в порядке');
echo "Конструктор главной администрации проверен.\n";

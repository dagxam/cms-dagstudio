<?php
declare(strict_types=1);
if(getenv('DAG_CMS_TEST_MODE')!=='1')exit(1);
$cfg=[
 'site_type'=>'government','site_template'=>'government',
 'enabled_modules'=>'["page","news","service","product"]',
 'cms_module_layouts'=>'{}'
];
function config_value(string $key,string $default=''):string{global $cfg;return $cfg[$key]??$default;}
function h(?string $value):string{return htmlspecialchars($value??'',ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function check_mod(bool $okay,string $text):void{if(!$okay){fwrite(STDERR,"[FAIL] $text\n");exit(1);}echo "[OK] $text\n";}
require dirname(__DIR__).'/app/templates.php';
require dirname(__DIR__).'/app/modules.php';
check_mod(count(cms_modules())===9,'Девять самостоятельных модулей зарегистрированы');
check_mod(cms_module_enabled('page')&&cms_module_enabled('contact')&&cms_module_enabled('documents')&&cms_module_enabled('photos')&&cms_module_enabled('videos'),'Старые сайты сохраняют настройки и медиатеку');
check_mod(!cms_module_enabled('unknown'),'Неизвестный модуль выключен');
foreach(array_keys(template_catalog()) as $type){
 $layout=cms_module_layout($type);
 check_mod(count($layout)===9,'Полная схема '.$type);
 check_mod(isset(cms_module_areas()[$layout['news']['area']]),'Допустимая зона по умолчанию '.$type);
 check_mod(in_array('photos',cms_module_ids($type,'nav'),true),'Медиатека доступна в меню '.$type);
}
$cfg['cms_module_layouts']=json_encode([
 'government'=>[
   'news'=>['area'=>'left','order'=>20],
   'page'=>['area'=>'left','order'=>10],
   'photos'=>['area'=>'right','order'=>25],
   'contact'=>['area'=>'footer','order'=>80],
   'features'=>['area'=>'hidden','order'=>99],
 ],
 'store'=>[
   'news'=>['area'=>'nav','order'=>20],
   'photos'=>['area'=>'main','order'=>10],
   'contact'=>['area'=>'right','order'=>50],
 ],
]);
check_mod(cms_module_ids('government','left')===['page','news'],'Настройка порядка левой колонки');
check_mod(in_array('photos',cms_module_ids('government','right'),true),'Медиатека справа у администрации');
check_mod(in_array('contact',cms_module_ids('government','footer'),true),'Контакты в подвале администрации');
check_mod(!in_array('features',cms_module_ids('government','main'),true),'Скрытие блока только в выбранной теме');
check_mod(in_array('news',cms_module_ids('store','nav'),true),'Новости магазина в меню');
check_mod(in_array('photos',cms_module_ids('store','main'),true),'Медиатека магазина в центре');
check_mod(!in_array('news',cms_module_ids('government','nav'),true),'Размещения не смешиваются между темами');
$cfg['cms_modules_enabled']='["page","photos","contact"]';
check_mod(!cms_module_enabled('news') && cms_module_ids('government','left')===['page'],'Выключенный модуль исчезает из всех зон');
check_mod(cms_module_enabled('media'),'Включённые модули продолжают работать');
check_mod(str_contains(cms_module_compact('photos'),'href="/media.php?type=photo"'),'Ссылка на модуль формируется безопасно');
check_mod(str_contains(cms_module_compact('contact'),'/?module=contact'),'Вынесенная форма контактов доступна как отдельная страница');
$cfg['cms_modules_enabled']='["photos","contact","news","page"]';
$cfg['cms_module_layouts']=json_encode(['government'=>['news'=>['area'=>'javascript','order'=>-999]]]);
$pos=cms_module_layout('government')['news'];
check_mod($pos['area']==='main' && $pos['order']>=1,'Некорректные позиции отклоняются');
echo "Размещение модулей проверено для всех шаблонов.\n";

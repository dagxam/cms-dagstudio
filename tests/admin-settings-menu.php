<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'||getenv('DAG_CMS_TEST_MODE')!=='1')exit(1);
$markup=file_get_contents(dirname(__DIR__).'/admin/index.php');
if(!is_string($markup))throw new RuntimeException('Не найдено меню.');
$sidebarStart=strpos($markup,'<aside class="sidebar">');
$sidebarEnd=strpos($markup,'<div class="sidebar-bottom">',$sidebarStart);
if($sidebarStart===false||$sidebarEnd===false)throw new RuntimeException('Нет бокового меню.');
$sidebar=substr($markup,$sidebarStart,$sidebarEnd-$sidebarStart);
foreach(['<details class="cms-settings-group"',
         '<summary class="nav-item cms-settings-summary',
         '<nav class="cms-settings-children"'] as $needle){
    if(!str_contains($sidebar,$needle))throw new RuntimeException('Нет вложенных настроек: '.$needle);
}
$values=[
'settings'=> 'Основные настройки',
'templates'=>'Выбор темы',
'menus'=>'Главное меню',
'modules'=>'Модули',
'accessibility'=>'Доступность и возраст',
'messages'=>'Обращения',
'users'=>'Пользователи',
];
foreach($values as $key=>$label){
    if(!str_contains($sidebar,"'$key'=>['$label'"))
        throw new RuntimeException('Не найден подраздел настроек '.$label);
}
if(!str_contains($sidebar,"if(!allowed(\$entry[1]))continue;"))
    throw new RuntimeException('Вложенные разделы не фильтруются по разрешениям.');
if(!str_contains($sidebar,'<?=$settingsOpen?\'open\':\'\'?>'))
    throw new RuntimeException('Подменю не раскрывается на внутренних страницах.');
echo "[OK] Раздел Настройки, вложенные подразделы и проверка доступа.\n";
